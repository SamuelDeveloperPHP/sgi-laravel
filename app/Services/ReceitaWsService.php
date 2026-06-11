<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para a API gratuita da ReceitaWS — consulta dados públicos
 * de CNPJ na Receita Federal.
 *
 * NOTAS DE PRODUÇÃO:
 *
 * 1. Rate limit gratuito: ~3 requests/min. Por isso fazemos CACHE de
 *    24h por CNPJ consultado. Mesmo assim, em produção alta-carga
 *    considere migrar para tier pago ou para outra API
 *    (BrasilAPI, CNPJa, etc).
 *
 * 2. Falha graciosa: se a API estiver fora do ar, rate-limited, ou
 *    retornar erro, devolvemos null. O frontend deve permitir
 *    preenchimento manual nesses casos — NUNCA bloquear o onboarding
 *    por causa de uma API externa.
 *
 * 3. Não é fonte de verdade: usamos só para pré-preencher os campos.
 *    A validação efetiva continua sendo o checksum + unicidade no
 *    nosso banco. Não confiamos na ReceitaWS para autorizar nada.
 *
 * 4. LGPD: dados retornados são públicos (Receita Federal), mas
 *    armazenamos apenas o que o usuário confirmar ao salvar a
 *    empresa. O cache é local, não compartilhado entre tenants.
 */
class ReceitaWsService
{
    private const BASE_URL = 'https://receitaws.com.br/v1/cnpj';
    private const TIMEOUT = 5; // segundos — não bloquear UX
    private const CACHE_TTL = 86400; // 24h

    /**
     * Consulta a API e retorna dados sanitizados ou null em falha.
     *
     * @param string $cnpj Aceita com ou sem máscara
     * @return array|null  Array com nome_fantasia, razao_social,
     *                     endereco, telefone, email, ou null se falhar
     */
    public function lookup(string $cnpj): ?array
    {
        $clean = CnpjValidator::sanitize($cnpj);
        if (strlen($clean) !== 14) {
            return null;
        }

        $cacheKey = "receitaws:cnpj:{$clean}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($clean) {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->acceptJson()
                    ->get(self::BASE_URL . '/' . $clean);

                if (!$response->successful()) {
                    Log::warning('ReceitaWS lookup falhou', [
                        'cnpj' => $clean,
                        'status' => $response->status(),
                    ]);
                    return null;
                }

                $data = $response->json();

                // ReceitaWS retorna status=ERROR no JSON quando CNPJ não existe
                if (!is_array($data) || ($data['status'] ?? null) === 'ERROR') {
                    return null;
                }

                return $this->normalize($data);
            } catch (\Throwable $e) {
                Log::warning('ReceitaWS lookup exception', [
                    'cnpj' => $clean,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Padroniza a resposta da API para os campos que a aplicação usa.
     * Não exporta dados sensíveis (situação cadastral interna, QSA, etc).
     */
    private function normalize(array $data): array
    {
        return [
            'nome_fantasia' => trim($data['fantasia'] ?? $data['nome'] ?? ''),
            'razao_social' => trim($data['nome'] ?? ''),
            'endereco' => trim(implode(', ', array_filter([
                $data['logradouro'] ?? null,
                $data['numero'] ?? null,
                $data['bairro'] ?? null,
                $data['municipio'] ?? null,
                $data['uf'] ?? null,
            ]))),
            'cep' => trim($data['cep'] ?? ''),
            'telefone' => trim($data['telefone'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'situacao' => trim($data['situacao'] ?? ''),
            'data_abertura' => trim($data['abertura'] ?? ''),
        ];
    }
}
