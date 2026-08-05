<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyRegistrationReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyRegistrationReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $master;

    private User $companyAdmin;

    private Company $pendingCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->master = User::factory()->create(['email_verified_at' => now()]);
        $this->master->forceFill(['is_master_admin' => true])->save();

        $this->pendingCompany = Company::factory()->create([
            'status' => false,
            'registration_status' => 'pending',
            'dominio_corporativo' => 'empresa.test',
            'email_corporativo' => 'contato@empresa.test',
            'nome_administrador' => 'Administrador Empresa',
            'email_administrador' => 'admin@empresa.test',
            'email_recuperacao_secundario' => 'recuperacao@empresa.test',
            'cnpj_verificado_em' => null,
        ]);

        $this->companyAdmin = User::factory()->create([
            'name' => 'Administrador Empresa',
            'email' => 'admin@empresa.test',
            'email_verified_at' => now(),
        ]);
        $this->companyAdmin->forceFill(['company_id' => $this->pendingCompany->id])->save();
        $this->companyAdmin->companies()->attach($this->pendingCompany->id);
    }

    public function test_only_master_admin_can_access_registration_queue(): void
    {
        $this->get(route('admin.company-registrations.index'))->assertRedirect(route('login'));

        $ordinaryCompany = Company::factory()->create(['registration_status' => 'approved']);
        $ordinary = User::factory()->create(['email_verified_at' => now()]);
        $ordinary->forceFill(['company_id' => $ordinaryCompany->id, 'is_master_admin' => false])->save();

        $response = $this->actingAs($ordinary)->get(route('admin.company-registrations.index'));
        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_queue_lists_only_pending_companies(): void
    {
        $approved = Company::factory()->create(['registration_status' => 'approved']);

        $this->actingAs($this->master)
            ->get(route('admin.company-registrations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CompanyRegistrations/Index')
                ->where('pendingCount', 1)
                ->has('companies.data', 1)
                ->where('companies.data.0.id', $this->pendingCompany->id)
                ->missing('companies.data.1')
            );

        $this->assertNotEquals($approved->id, $this->pendingCompany->id);
    }

    public function test_master_can_approve_and_audit_pending_registration(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->master)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10', 'HTTP_USER_AGENT' => 'SecurityTest/1.0'])
            ->post(route('admin.company-registrations.approve', $this->pendingCompany), [
                'reason' => 'CNPJ e domínio conferidos manualmente.',
            ]);

        $response->assertRedirect(route('admin.company-registrations.index'));

        $this->pendingCompany->refresh();
        $this->assertTrue($this->pendingCompany->status);
        $this->assertSame('approved', $this->pendingCompany->registration_status);
        $this->assertSame($this->master->id, $this->pendingCompany->registration_reviewed_by);
        $this->assertNotNull($this->pendingCompany->registration_reviewed_at);
        $this->assertNotNull($this->pendingCompany->cnpj_verificado_em);

        $this->assertDatabaseHas('company_registration_reviews', [
            'company_id' => $this->pendingCompany->id,
            'reviewer_id' => $this->master->id,
            'decision' => 'approved',
            'reason' => 'CNPJ e domínio conferidos manualmente.',
            'ip_address' => '203.0.113.10',
            'user_agent' => 'SecurityTest/1.0',
        ]);
        $this->assertDatabaseHas('master_admin_audit_log', [
            'user_id' => $this->master->id,
            'company_id_target' => $this->pendingCompany->id,
            'ability' => 'updated',
            'model_type' => Company::class,
            'model_id' => $this->pendingCompany->id,
        ]);
        Notification::assertSentTo($this->companyAdmin, CompanyRegistrationReviewed::class);
    }

    public function test_rejection_requires_reason_and_cannot_be_forged(): void
    {
        $response = $this->actingAs($this->master)
            ->post(route('admin.company-registrations.reject', $this->pendingCompany), [
                'reason' => '',
                'status' => true,
                'registration_reviewed_by' => 999,
            ]);

        $response->assertSessionHasErrors(['reason', 'status', 'registration_reviewed_by']);
        $this->assertSame('pending', $this->pendingCompany->fresh()->registration_status);
        $this->assertDatabaseCount('company_registration_reviews', 0);
    }

    public function test_master_can_reject_and_company_admin_sees_reason_but_remains_blocked(): void
    {
        Notification::fake();

        $this->actingAs($this->master)
            ->post(route('admin.company-registrations.reject', $this->pendingCompany), [
                'reason' => 'O domínio informado não pertence ao CNPJ.',
            ])
            ->assertRedirect(route('admin.company-registrations.index'));

        $this->pendingCompany->refresh();
        $this->assertFalse($this->pendingCompany->status);
        $this->assertSame('rejected', $this->pendingCompany->registration_status);
        $this->assertSame('O domínio informado não pertence ao CNPJ.', $this->pendingCompany->registration_review_reason);
        Notification::assertSentTo($this->companyAdmin, CompanyRegistrationReviewed::class);

        $this->actingAs($this->companyAdmin)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.pending'));

        $this->get(route('onboarding.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Onboarding/Pending')
                ->where('registrationStatus', 'rejected')
                ->where('reviewReason', 'O domínio informado não pertence ao CNPJ.')
            );
    }

    public function test_decision_is_single_use_and_protected_against_race_or_replay(): void
    {
        Notification::fake();

        $this->actingAs($this->master)
            ->post(route('admin.company-registrations.approve', $this->pendingCompany), ['reason' => 'Aprovado'])
            ->assertRedirect(route('admin.company-registrations.index'));

        $this->post(route('admin.company-registrations.reject', $this->pendingCompany), ['reason' => 'Tentativa posterior'])
            ->assertSessionHasErrors('company');

        $this->assertSame('approved', $this->pendingCompany->fresh()->registration_status);
        $this->assertDatabaseCount('company_registration_reviews', 1);
    }
}
