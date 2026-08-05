<?php

namespace App\Http\Middleware;

use App\Services\HtmlSanitizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeRichText
{
    private const RICH_TEXT_FIELDS = [
        'conteudo',
        'descOcorrencia',
        'relatorio',
        'pautas',
        'registro',
        'descricao',
    ];

    public function __construct(private readonly HtmlSanitizer $sanitizer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->sanitizeArray($request->all()));

        return $next($request);
    }

    private function sanitizeArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value) && in_array($key, self::RICH_TEXT_FIELDS, true)) {
                $input[$key] = $this->sanitizer->sanitize($value);
            }
        }

        return $input;
    }
}
