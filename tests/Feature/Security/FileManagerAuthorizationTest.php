<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\FmArquivo;
use App\Models\FmGrupo;
use App\Models\FmPasta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FileManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $ordinaryUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();
        $this->ordinaryUser = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email_verified_at' => now(),
        ]);

        DB::table('fm_empresa_acesso')->insert([
            'company_id' => $this->companyA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_ordinary_user_cannot_manage_file_groups(): void
    {
        $this->actingAs($this->ordinaryUser)
            ->post(route('file-manager.grupos.store'), ['nome' => 'Privilegiado'])
            ->assertForbidden();

        $this->assertDatabaseMissing('fm_grupos', ['nome' => 'Privilegiado']);
    }

    public function test_company_admin_cannot_add_user_from_another_tenant_to_group(): void
    {
        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $this->ordinaryUser->assignRole('Administrador');
        $foreignUser = User::factory()->create(['company_id' => $this->companyB->id]);
        $group = FmGrupo::create([
            'company_id' => $this->companyA->id,
            'nome' => 'Gestores',
            'created_by' => $this->ordinaryUser->id,
        ]);

        $this->actingAs($this->ordinaryUser)
            ->post(route('file-manager.grupos.users.add', $group->id), ['user_id' => $foreignUser->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('fm_grupo_users', [
            'grupo_id' => $group->id,
            'user_id' => $foreignUser->id,
        ]);
    }

    public function test_user_without_folder_permission_cannot_restore_or_force_delete_file(): void
    {
        $folder = FmPasta::create([
            'company_id' => $this->companyA->id,
            'nome' => 'Diretoria',
            'is_root' => false,
            'created_by' => $this->ordinaryUser->id,
        ]);
        $file = FmArquivo::create([
            'company_id' => $this->companyA->id,
            'pasta_id' => $folder->id,
            'nome_original' => 'sigiloso.pdf',
            'nome_disco' => Str::uuid().'.pdf',
            'caminho' => 'file-manager/'.$this->companyA->id.'/sigiloso.pdf',
            'created_by' => $this->ordinaryUser->id,
        ]);
        $file->delete();

        $this->actingAs($this->ordinaryUser)
            ->patch(route('file-manager.arquivos.restore', $file->id))
            ->assertForbidden();

        $this->actingAs($this->ordinaryUser)
            ->delete(route('file-manager.arquivos.force-delete', $file->id))
            ->assertForbidden();

        $this->assertSoftDeleted('fm_arquivos', ['id' => $file->id]);
    }

    public function test_user_cannot_download_file_from_another_tenant(): void
    {
        $folder = FmPasta::create([
            'company_id' => $this->companyB->id,
            'nome' => 'Outra empresa',
            'is_root' => true,
        ]);
        $file = FmArquivo::create([
            'company_id' => $this->companyB->id,
            'pasta_id' => $folder->id,
            'nome_original' => 'outro-tenant.pdf',
            'nome_disco' => Str::uuid().'.pdf',
            'caminho' => 'file-manager/'.$this->companyB->id.'/outro-tenant.pdf',
        ]);

        $this->actingAs($this->ordinaryUser)
            ->get(route('file-manager.download', $file->id))
            ->assertNotFound();
    }

    public function test_upload_rejects_executable_web_content(): void
    {
        $root = FmPasta::create([
            'company_id' => $this->companyA->id,
            'nome' => 'Raiz',
            'is_root' => true,
            'created_by' => $this->ordinaryUser->id,
        ]);
        $payload = UploadedFile::fake()->createWithContent('ataque.html', '<script>alert(1)</script>');

        $this->actingAs($this->ordinaryUser)
            ->post(route('file-manager.upload'), ['file' => $payload, 'pasta_id' => $root->id])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('fm_arquivos', 0);
    }
}
