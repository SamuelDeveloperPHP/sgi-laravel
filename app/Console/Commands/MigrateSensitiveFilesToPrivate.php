<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'security:migrate-sensitive-files {--dry-run : Apenas lista, sem mover arquivos}';

    protected $description = 'Move anexos sensiveis referenciados no banco do disco publico para o privado';

    public function handle(): int
    {
        $paths = collect();

        DB::table('controle_calibracaos')
            ->whereNotNull('arquivo_certificado')
            ->pluck('arquivo_certificado')
            ->each(fn ($path) => $paths->push($path));

        DB::table('fornecedor_documentos')
            ->whereNotNull('arquivo')
            ->pluck('arquivo')
            ->each(fn ($path) => $paths->push($path));

        DB::table('sts_tarefas_anexos')
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->each(fn ($path) => $paths->push($path));

        DB::table('sts_projetos')
            ->select(['imagem_capa', 'arquivos_anexos'])
            ->where(function ($query) {
                $query->whereNotNull('imagem_capa')->orWhereNotNull('arquivos_anexos');
            })
            ->orderBy('id')
            ->each(function ($project) use ($paths) {
                $paths->push($project->imagem_capa);
                $attachments = is_string($project->arquivos_anexos)
                    ? json_decode($project->arquivos_anexos, true)
                    : $project->arquivos_anexos;
                foreach (is_array($attachments) ? $attachments : [] as $path) {
                    $paths->push($path);
                }
            });

        DB::table('sts_naoconforme')
            ->whereNotNull('evidencias')
            ->pluck('evidencias')
            ->each(function ($evidencias) use ($paths) {
                $items = is_string($evidencias) ? json_decode($evidencias, true) : $evidencias;
                foreach (is_array($items) ? $items : [] as $item) {
                    if (is_array($item) && isset($item['foto'])) {
                        $paths->push($item['foto']);
                    }
                }
            });

        $moved = 0;
        $alreadyPrivate = 0;
        $missing = 0;
        $invalid = 0;

        foreach ($paths->filter()->unique() as $path) {
            if (!$this->isSafeRelativePath($path)) {
                $invalid++;
                $this->warn("Ignorado caminho invalido: {$path}");
                continue;
            }

            if (Storage::disk('local')->exists($path)) {
                $alreadyPrivate++;
                continue;
            }

            if (!Storage::disk('public')->exists($path)) {
                $missing++;
                $this->warn("Arquivo nao encontrado: {$path}");
                continue;
            }

            $moved++;
            if ($this->option('dry-run')) {
                $this->line("Moveria: {$path}");
                continue;
            }

            $stream = Storage::disk('public')->readStream($path);
            if ($stream === null || !Storage::disk('local')->writeStream($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->error("Falha ao copiar: {$path}");
                return self::FAILURE;
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            Storage::disk('public')->delete($path);
            $this->info("Movido: {$path}");
        }

        $this->newLine();
        $this->info("Candidatos: {$paths->filter()->unique()->count()}; movidos: {$moved}; ja privados: {$alreadyPrivate}; ausentes: {$missing}; invalidos: {$invalid}.");

        return self::SUCCESS;
    }

    private function isSafeRelativePath(mixed $path): bool
    {
        return is_string($path)
            && $path !== ''
            && !str_starts_with($path, '/')
            && !str_starts_with($path, '\\')
            && !str_contains($path, '..')
            && !str_contains($path, "\0");
    }
}
