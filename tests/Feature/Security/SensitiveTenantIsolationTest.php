<?php

namespace Tests\Feature\Security;

use App\Models\AtaReuniao;
use App\Models\Company;
use App\Models\ControleCalibracao;
use App\Models\Fornecedor;
use App\Models\FornecedorDocumento;
use App\Models\NaoConformidade;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

class SensitiveTenantIsolationTest extends SecurityTestCase
{
    private Company $companyA;
    private Company $companyB;
    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();
        $this->userA = User::factory()->create(['email_verified_at' => now()]);
        $this->userA->forceFill(['company_id' => $this->companyA->id])->save();

        foreach ([
            'view-fornecedores',
            'manage-fornecedores',
            'view-atas-reuniao',
            'view-controle-calibracoes',
            'manage-controle-calibracoes',
            'view-naoconformidades',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->userA->givePermissionTo([
            'view-fornecedores',
            'manage-fornecedores',
            'view-atas-reuniao',
            'view-controle-calibracoes',
            'manage-controle-calibracoes',
            'view-naoconformidades',
        ]);
    }

    public function test_supplier_from_another_company_is_not_visible_by_id(): void
    {
        $supplier = Fornecedor::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'razao_social' => 'Fornecedor B',
        ]);

        $this->actingAs($this->userA)
            ->get("/fornecedores/{$supplier->id}")
            ->assertNotFound();
    }

    public function test_supplier_document_from_another_company_cannot_be_downloaded(): void
    {
        $supplier = Fornecedor::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'razao_social' => 'Fornecedor B',
        ]);
        $document = FornecedorDocumento::forceCreate([
            'fornecedor_id' => $supplier->id,
            'nome_documento' => 'segredo.pdf',
            'arquivo' => 'segredo.pdf',
        ]);

        $this->actingAs($this->userA)
            ->get("/fornecedor-documentos/{$document->id}/download")
            ->assertNotFound();
    }

    public function test_meeting_record_from_another_company_is_not_visible_by_route_binding(): void
    {
        $meeting = AtaReuniao::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'data' => now()->toDateString(),
            'hora_inicio' => '09:00',
            'hora_termino' => '10:00',
            'local' => 'Sala',
            'assunto' => 'Segredo',
            'pautas' => 'Segredo',
            'responsavel_id' => $this->userA->id,
            'status' => 'rascunho',
        ]);

        $this->actingAs($this->userA)
            ->get("/atas-reuniao/{$meeting->id}")
            ->assertNotFound();
    }

    public function test_calibration_record_from_another_company_is_not_visible_by_route_binding(): void
    {
        $calibration = ControleCalibracao::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'equipamento' => 'Equipamento secreto',
        ]);

        $this->actingAs($this->userA)
            ->get("/controle-calibracoes/{$calibration->id}/edit")
            ->assertNotFound();
    }

    public function test_supplier_documents_are_stored_privately(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $supplier = Fornecedor::forceCreate([
            'company_id' => $this->companyA->id,
            'razao_social' => 'Fornecedor A',
        ]);

        $this->actingAs($this->userA)
            ->post("/fornecedores/{$supplier->id}/documentos", [
                'nome_documento' => 'Certificado privado',
                'arquivo' => UploadedFile::fake()->create('certificado.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document = FornecedorDocumento::where('fornecedor_id', $supplier->id)->latest('id')->firstOrFail();
        Storage::disk('local')->assertExists($document->arquivo);
        Storage::disk('public')->assertMissing($document->arquivo);
    }

    public function test_nonconformity_evidence_from_another_company_cannot_be_read(): void
    {
        Storage::fake('local');
        $path = 'companies/' . $this->companyB->id . '/nao-conformidades/segredo.png';
        Storage::disk('local')->put($path, 'segredo');

        $nonconformity = NaoConformidade::withoutGlobalScopes()->forceCreate([
            'descOcorrencia' => 'NC confidencial B',
            'dataAbertura' => now(),
            'company_id' => $this->companyB->id,
            'user_create' => $this->userA->id,
            'evidencias' => [['foto' => $path, 'descricao' => 'Segredo']],
        ]);

        $this->actingAs($this->userA)
            ->get("/nao-conformidades/{$nonconformity->id}/evidencias/0")
            ->assertNotFound();
    }
}
