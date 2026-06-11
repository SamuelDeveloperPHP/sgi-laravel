<?php

namespace App\Services;

/**
 * Validador de CNPJ — algoritmo oficial dos 14 dígitos com 2 dígitos
 * verificadores. Independente de bibliotecas externas para que a
 * validação funcione mesmo offline / sem rate limit.
 *
 * NUNCA confie apenas em validação client-side: um atacante pode
 * sempre submeter dados arbitrários por curl/Postman. Por isso o
 * Form Request usa este validator no servidor.
 *
 * Algoritmo:
 *   1. Sanitiza para 14 dígitos (remove pontos, barra, traço)
 *   2. Rejeita CNPJ com todos dígitos iguais (11111111111111 etc)
 *   3. Calcula DV1 pela multiplicação dos 12 primeiros dígitos pelos
 *      pesos [5,4,3,2,9,8,7,6,5,4,3,2], soma, mod 11
 *   4. Calcula DV2 pela multiplicação dos 13 primeiros pelos pesos
 *      [6,5,4,3,2,9,8,7,6,5,4,3,2]
 *   5. DV deve ser 0 se soma % 11 < 2, senão 11 - (soma % 11)
 *
 * @see https://www.gov.br/receitafederal/pt-br
 */
class CnpjValidator
{
    /**
     * Verifica se o CNPJ é válido (formato + checksum).
     * Aceita CNPJ com ou sem máscara (XX.XXX.XXX/XXXX-XX ou apenas dígitos).
     */
    public static function isValid(?string $cnpj): bool
    {
        if ($cnpj === null) {
            return false;
        }

        $cnpj = self::sanitize($cnpj);

        // CNPJ deve ter exatamente 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Rejeita sequências como 00000000000000, 11111111111111, etc
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }

        // Calcula e compara os 2 dígitos verificadores
        for ($t = 12; $t < 14; $t++) {
            $sum = 0;
            $pos = $t - 7;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cnpj[$i] * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $expectedDigit = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
            if ((int) $cnpj[$t] !== $expectedDigit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove tudo que não for dígito. Retorna apenas os 14 caracteres
     * numéricos para uso em queries (storage uniforme no DB).
     */
    public static function sanitize(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }

    /**
     * Formata CNPJ com máscara para exibição: XX.XXX.XXX/XXXX-XX
     * Retorna o valor original se não conseguir formatar.
     */
    public static function format(string $cnpj): string
    {
        $clean = self::sanitize($cnpj);
        if (strlen($clean) !== 14) {
            return $cnpj;
        }
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($clean, 0, 2),
            substr($clean, 2, 3),
            substr($clean, 5, 3),
            substr($clean, 8, 4),
            substr($clean, 12, 2)
        );
    }
}
