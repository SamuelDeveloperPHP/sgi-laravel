<?php

namespace Database\Factories;

use App\Models\Company;
use App\Services\CnpjValidator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para gerar Company em testes. Gera CNPJ valido aleatorio
 * via algoritmo oficial (mesmo do CnpjValidator) para que companies
 * criadas em teste passem em validacoes de checksum.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'nome_fantasia' => $this->faker->company(),
            'razao_social' => $this->faker->company() . ' ' . $this->faker->companySuffix(),
            'cnpj' => self::generateValidCnpj(),
            'status' => true,
        ];
    }

    /**
     * Gera um CNPJ valido aleatorio respeitando os 2 digitos
     * verificadores. Usado para nao depender de listas hardcoded
     * de CNPJ validos.
     */
    public static function generateValidCnpj(): string
    {
        // Sorteia os primeiros 12 digitos garantindo que nao sejam
        // todos iguais (CNPJ tipo 11111111111111 e rejeitado)
        do {
            $digits = '';
            for ($i = 0; $i < 12; $i++) {
                $digits .= mt_rand(0, 9);
            }
        } while (preg_match('/^(\d)\1+$/', $digits));

        // Calcula DV1
        $sum = 0;
        $pos = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        $dv1 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        $digits .= $dv1;

        // Calcula DV2
        $sum = 0;
        $pos = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $digits[$i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        $dv2 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        $digits .= $dv2;

        return $digits; // 14 digitos sem mascara
    }
}
