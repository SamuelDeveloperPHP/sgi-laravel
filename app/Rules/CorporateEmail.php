<?php

namespace App\Rules;

use App\Services\CorporateDomain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CorporateEmail implements ValidationRule
{
    public function __construct(private readonly ?string $domain = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = is_string($value) ? strtolower(trim($value)) : '';

        if (CorporateDomain::isPublicEmail($email)) {
            $fail('Use um e-mail corporativo. Provedores de e-mail pessoal nao sao aceitos.');
            return;
        }

        if ($this->domain !== null && !CorporateDomain::emailMatches($email, $this->domain)) {
            $fail('O e-mail deve pertencer exatamente ao dominio corporativo informado.');
        }
    }
}
