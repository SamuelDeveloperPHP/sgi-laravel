<?php
$files = glob('app/Http/Controllers/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find catch (\Exception $e) { DB::rollBack(); Log::error($e->getMessage()); return back()->with('error', 'Erro interno ao realizar operação.'); }
    // We will do a robust regex
    $pattern = '/catch\s*\(\\\Exception\s+\$e\)\s*\{\s*DB::rollBack\(\);\s*Log::error\(\$e->getMessage\(\)\);\s*return\s+back\(\)->with\(\'error\',\s*\'Erro interno ao realizar operação\.\'\);\s*\}/s';
    
    $replacement = "catch (\Exception \$e) {\n            DB::rollBack();\n            if (\$e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {\n                throw \$e;\n            }\n            Log::error(\$e->getMessage());\n            return back()->with('error', 'Erro interno ao realizar operação.');\n        }";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Updated " . basename($file) . "\n";
    }
}
