<?php

namespace App\Services;

final class CorporateDomain
{
    /**
     * Provedores de e-mail pessoal que nunca podem iniciar um cadastro.
     * O onboarding ainda exige correspondencia exata com o dominio informado.
     */
    private const PUBLIC_EMAIL_DOMAINS = [
        'gmail.com', 'googlemail.com', 'hotmail.com', 'hotmail.com.br',
        'outlook.com', 'outlook.com.br', 'live.com', 'msn.com',
        'yahoo.com', 'yahoo.com.br', 'ymail.com', 'icloud.com',
        'me.com', 'mac.com', 'aol.com', 'proton.me', 'protonmail.com',
        'gmx.com', 'mail.com', 'uol.com.br', 'bol.com.br', 'terra.com.br',
    ];

    public static function normalize(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        $url = str_contains($value, '://') ? $value : 'https://' . $value;
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return null;
        }

        $host = rtrim(strtolower($host), '.');
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $host = strtolower($ascii);
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP)
            || !preg_match('/^(?=.{4,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host)) {
            return null;
        }

        return $host;
    }

    public static function emailDomain(string $email): ?string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);

        return self::normalize($domain);
    }

    public static function isPublicEmail(string $email): bool
    {
        $domain = self::emailDomain($email);

        return $domain === null || in_array($domain, self::PUBLIC_EMAIL_DOMAINS, true);
    }

    public static function emailMatches(string $email, string $corporateDomain): bool
    {
        $emailDomain = self::emailDomain($email);
        $normalizedCorporateDomain = self::normalize($corporateDomain);

        return $emailDomain !== null
            && $normalizedCorporateDomain !== null
            && hash_equals($normalizedCorporateDomain, $emailDomain)
            && !self::isPublicEmail($email);
    }
}
