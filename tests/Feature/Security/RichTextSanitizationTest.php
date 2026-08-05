<?php

namespace Tests\Feature\Security;

use App\Services\HtmlSanitizer;
use Tests\TestCase;

class RichTextSanitizationTest extends TestCase
{
    public function test_sanitizer_removes_executable_html_and_preserves_safe_formatting(): void
    {
        $payload = '<p onclick="alert(1)"><strong>Permitido</strong>'
            .'<script>alert(2)</script>'
            .'<img src=x onerror="alert(3)">'
            .'<a href="javascript:alert(4)" target="_blank">link</a></p>';

        $clean = app(HtmlSanitizer::class)->sanitize($payload);

        $this->assertStringContainsString('<strong>Permitido</strong>', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $clean);
        $this->assertStringNotContainsStringIgnoringCase('<script', $clean);
        $this->assertStringNotContainsStringIgnoringCase('<img', $clean);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $clean);
    }

    public function test_sanitizer_rejects_encoded_and_mixed_case_javascript_urls(): void
    {
        $clean = app(HtmlSanitizer::class)->sanitize(
            '<a href="JaVaScRiPt&#58;alert(1)">ataque</a><iframe srcdoc="<script>alert(2)</script>"></iframe>'
        );

        $this->assertSame('<a>ataque</a>', $clean);
    }

    public function test_sanitizer_preserves_editor_formatting_used_for_printing(): void
    {
        $clean = app(HtmlSanitizer::class)->sanitize(
            '<h1>Titulo</h1><p><strong>Texto</strong></p><hr><blockquote>Citacao</blockquote><pre><code>codigo</code></pre>'
        );

        $this->assertStringContainsString('<h1>Titulo</h1>', $clean);
        $this->assertStringContainsString('<strong>Texto</strong>', $clean);
        $this->assertStringContainsString('<hr>', $clean);
        $this->assertStringContainsString('<blockquote>Citacao</blockquote>', $clean);
        $this->assertStringContainsString('<pre><code>codigo</code></pre>', $clean);
    }
}
