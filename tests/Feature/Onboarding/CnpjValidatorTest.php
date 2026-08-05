<?php

namespace Tests\Feature\Onboarding;

use App\Services\CnpjValidator;
use PHPUnit\Framework\TestCase;

/**
 * Testa o algoritmo de validacao de CNPJ.
 *
 * Nao precisa do banco — e um teste puro de algoritmo. Estende
 * PHPUnit\TestCase diretamente para evitar carregar a app inteira.
 *
 * CNPJs validos usados aqui foram gerados via geradores publicos
 * de CNPJ para teste (Gerador de CNPJ) e validados em multiplos
 * algoritmos de validacao de CNPJ. Nao correspondem a empresas
 * reais — sao apenas combinacoes numericas que satisfazem o
 * checksum.
 */
class CnpjValidatorTest extends TestCase
{
    /** @test */
    public function aceita_cnpj_valido_sem_mascara(): void
    {
        $this->assertTrue(CnpjValidator::isValid('11222333000181'));
        $this->assertTrue(CnpjValidator::isValid('60872504000123'));
    }

    /** @test */
    public function aceita_cnpj_valido_com_mascara(): void
    {
        $this->assertTrue(CnpjValidator::isValid('11.222.333/0001-81'));
        $this->assertTrue(CnpjValidator::isValid('60.872.504/0001-23'));
    }

    /** @test */
    public function rejeita_cnpj_com_digito_verificador_invalido(): void
    {
        // Mesmo numero mas com DV errado:
        $this->assertFalse(CnpjValidator::isValid('11222333000182'));
        $this->assertFalse(CnpjValidator::isValid('45283163000135'));
    }

    /** @test */
    public function rejeita_cnpj_com_todos_digitos_iguais(): void
    {
        $this->assertFalse(CnpjValidator::isValid('00000000000000'));
        $this->assertFalse(CnpjValidator::isValid('11111111111111'));
        $this->assertFalse(CnpjValidator::isValid('99999999999999'));
    }

    /** @test */
    public function rejeita_cnpj_com_tamanho_errado(): void
    {
        $this->assertFalse(CnpjValidator::isValid(''));
        $this->assertFalse(CnpjValidator::isValid('1122233300018'));   // 13 digitos
        $this->assertFalse(CnpjValidator::isValid('112223330001811')); // 15 digitos
        $this->assertFalse(CnpjValidator::isValid('123'));
    }

    /** @test */
    public function rejeita_null(): void
    {
        $this->assertFalse(CnpjValidator::isValid(null));
    }

    /** @test */
    public function rejeita_cnpj_com_letras(): void
    {
        // sanitize() remove nao-digitos, entao 'abc' vira '' apos
        // sanitizacao -> tamanho 0 -> false
        $this->assertFalse(CnpjValidator::isValid('abcdefghijklmn'));
    }

    /** @test */
    public function sanitize_remove_pontuacao(): void
    {
        $this->assertEquals('11222333000181', CnpjValidator::sanitize('11.222.333/0001-81'));
        $this->assertEquals('11222333000181', CnpjValidator::sanitize('11222333000181'));
        $this->assertEquals('', CnpjValidator::sanitize('abc'));
    }

    /** @test */
    public function format_aplica_mascara_correta(): void
    {
        $this->assertEquals('11.222.333/0001-81', CnpjValidator::format('11222333000181'));
        $this->assertEquals('11.222.333/0001-81', CnpjValidator::format('11.222.333/0001-81'));
    }

    /** @test */
    public function format_devolve_input_se_tamanho_errado(): void
    {
        $this->assertEquals('123', CnpjValidator::format('123'));
    }
}
