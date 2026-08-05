<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CnpjLookupService
{
    public function lookup(string $cnpj): ?array
    {
        $clean = CnpjValidator::sanitize($cnpj);

        if (! CnpjValidator::isValid($clean)) {
            return null;
        }

        if (Cache::get("cnpj:lookup:miss:{$clean}") === true) {
            return null;
        }

        return Cache::remember("cnpj:lookup:{$clean}", now()->addDay(), function () use ($clean) {
            foreach (['cnpja', 'brasilapi'] as $provider) {
                $result = $this->requestProvider($provider, $clean);

                if ($result !== null) {
                    Cache::forget("cnpj:lookup:miss:{$clean}");

                    return $result;
                }
            }

            Cache::put("cnpj:lookup:miss:{$clean}", true, now()->addMinutes(5));

            return null;
        });
    }

    public function isActive(array $result): bool
    {
        $status = strtoupper(Str::ascii(trim((string) ($result['situacao'] ?? ''))));

        return in_array($status, ['ATIVA', 'ACTIVE', '2'], true);
    }

    public function canAutoApprove(array $result, string $submittedDomain): bool
    {
        $officialDomain = CorporateDomain::normalize((string) ($result['dominio_corporativo'] ?? ''));
        $normalizedSubmittedDomain = CorporateDomain::normalize($submittedDomain);

        return $this->isActive($result)
            && $officialDomain !== null
            && $normalizedSubmittedDomain !== null
            && hash_equals($officialDomain, $normalizedSubmittedDomain);
    }

    private function requestProvider(string $provider, string $cnpj): ?array
    {
        $baseUrl = rtrim((string) config("services.cnpj.{$provider}_url"), '/');
        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.cnpj.timeout', 8))
                ->get("{$baseUrl}/{$cnpj}");

            if (! $response->successful() || ! is_array($response->json())) {
                Log::warning('Consulta publica de CNPJ falhou', [
                    'provider' => $provider,
                    'cnpj_suffix' => substr($cnpj, -6),
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $provider === 'cnpja'
                ? $this->normalizeCnpja($response->json())
                : $this->normalizeBrasilApi($response->json());
        } catch (\Throwable $exception) {
            Log::warning('Provedor de CNPJ indisponivel', [
                'provider' => $provider,
                'cnpj_suffix' => substr($cnpj, -6),
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    private function normalizeCnpja(array $data): array
    {
        $phone = Arr::first((array) ($data['phones'] ?? []));
        $email = Arr::first((array) ($data['emails'] ?? []));
        $emailAddress = is_array($email) ? strtolower(trim((string) ($email['address'] ?? ''))) : '';

        return [
            'provider' => 'cnpja',
            'nome_fantasia' => $this->text($data, ['alias', 'tradeName']),
            'razao_social' => $this->text($data, ['company.name', 'name']),
            'cep' => $this->text($data, ['address.zip', 'address.zipCode']),
            'logradouro' => $this->text($data, ['address.street']),
            'numero' => $this->text($data, ['address.number']),
            'complemento' => $this->text($data, ['address.details', 'address.complement']),
            'bairro' => $this->text($data, ['address.district']),
            'cidade' => $this->text($data, ['address.city']),
            'estado' => strtoupper($this->text($data, ['address.state'])),
            'telefone' => is_array($phone)
                ? trim((string) ($phone['area'] ?? '').(string) ($phone['number'] ?? ''))
                : '',
            'email' => $emailAddress,
            'dominio_corporativo' => $this->corporateDomainFromEmail($emailAddress),
            'situacao' => $this->text($data, ['status.text', 'status.name', 'status.id', 'status']),
        ];
    }

    private function normalizeBrasilApi(array $data): array
    {
        $email = strtolower($this->text($data, ['email']));

        return [
            'provider' => 'brasilapi',
            'nome_fantasia' => $this->text($data, ['nome_fantasia']),
            'razao_social' => $this->text($data, ['razao_social']),
            'cep' => $this->text($data, ['cep']),
            'logradouro' => $this->text($data, ['logradouro']),
            'numero' => $this->text($data, ['numero']),
            'complemento' => $this->text($data, ['complemento']),
            'bairro' => $this->text($data, ['bairro']),
            'cidade' => $this->text($data, ['municipio']),
            'estado' => strtoupper($this->text($data, ['uf'])),
            'telefone' => $this->text($data, ['ddd_telefone_1', 'ddd_telefone_2']),
            'email' => $email,
            'dominio_corporativo' => $this->corporateDomainFromEmail($email),
            'situacao' => $this->text($data, ['descricao_situacao_cadastral', 'situacao_cadastral']),
        ];
    }

    private function text(array $data, array $paths): string
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);
            if (is_scalar($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function corporateDomainFromEmail(string $email): string
    {
        if ($email === '' || CorporateDomain::isPublicEmail($email)) {
            return '';
        }

        return CorporateDomain::emailDomain($email) ?? '';
    }
}
