<?php
$files = glob('app/Http/Controllers/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $newContent = preg_replace('/[ \t]*private function authorizePermission\(\$permission\)[ \t\n\r]*{[ \t\n\r]*if \(\!auth\(\)->user\(\)->hasPermissionTo\(\$permission\)\) {[ \t\n\r]*abort\(403, \'Acesso não autorizado\.\'\);[ \t\n\r]*}[ \t\n\r]*}/', '', $content);
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Updated " . basename($file) . "\n";
    }
}
