<?php

namespace Tests\Feature\Onboarding;

use App\Services\CnpjLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CnpjLookupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.cnpj.cnpja_url' => 'https://open.cnpja.test/office',
            'services.cnpj.brasilapi_url' => 'https://brasilapi.test/api/cnpj/v1',
        ]);
    }

    public function test_uses_cnpja_first_and_normalizes_response(): void
    {
        Http::fake([
            'open.cnpja.test/*' => Http::response([
                'alias' => 'Empresa Teste',
                'company' => ['name' => 'Empresa Teste LTDA'],
                'status' => ['id' => 2, 'text' => 'Ativa'],
                'address' => [
                    'street' => 'Avenida Teste', 'number' => '100', 'district' => 'Centro',
                    'city' => 'São Paulo', 'state' => 'SP', 'zip' => '01310100',
                ],
                'phones' => [['area' => '11', 'number' => '33334444']],
                'emails' => [['address' => 'CONTATO@EMPRESA.COM.BR']],
            ]),
        ]);

        $result = app(CnpjLookupService::class)->lookup('11.222.333/0001-81');

        $this->assertSame('cnpja', $result['provider']);
        $this->assertSame('Empresa Teste LTDA', $result['razao_social']);
        $this->assertSame('Avenida Teste', $result['logradouro']);
        $this->assertSame('contato@empresa.com.br', $result['email']);
        $this->assertSame('empresa.com.br', $result['dominio_corporativo']);
        $this->assertTrue(app(CnpjLookupService::class)->isActive($result));
        $this->assertTrue(app(CnpjLookupService::class)->canAutoApprove($result, 'https://www.empresa.com.br'));
        $this->assertFalse(app(CnpjLookupService::class)->canAutoApprove($result, 'outraempresa.com.br'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://open.cnpja.test/office/11222333000181');
    }

    public function test_falls_back_to_brasilapi(): void
    {
        Http::fake([
            'open.cnpja.test/*' => Http::response([], 503),
            'brasilapi.test/*' => Http::response([
                'nome_fantasia' => 'Fallback',
                'razao_social' => 'Fallback LTDA',
                'descricao_situacao_cadastral' => 'ATIVA',
                'municipio' => 'Recife',
                'uf' => 'pe',
            ]),
        ]);

        $result = app(CnpjLookupService::class)->lookup('11222333000181');

        $this->assertSame('brasilapi', $result['provider']);
        $this->assertSame('Recife', $result['cidade']);
        $this->assertSame('PE', $result['estado']);
        Http::assertSentCount(2);
    }

    public function test_returns_null_when_both_providers_fail(): void
    {
        Http::fake(fn () => Http::response([], 503));

        $this->assertNull(app(CnpjLookupService::class)->lookup('11222333000181'));
        Http::assertSentCount(2);
    }

    public function test_does_not_call_providers_for_cpf_or_invalid_cnpj(): void
    {
        Http::fake();

        $this->assertNull(app(CnpjLookupService::class)->lookup('52998224725'));
        $this->assertNull(app(CnpjLookupService::class)->lookup('00000000000000'));
        Http::assertNothingSent();
    }
}
