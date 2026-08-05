<?php

namespace App\Console\Commands;

use App\Services\HtmlSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SanitizeStoredHtml extends Command
{
    protected $signature = 'security:sanitize-stored-html {--dry-run}';
    protected $description = 'Sanitiza campos HTML ricos já armazenados no banco';

    private const TARGETS = [
        'sts_naoconforme' => ['descOcorrencia'],
        'sts_auditoriainternaqualidade' => ['relatorio'],
        'sts_politica_qualidade' => ['conteudo'],
        'sts_escopo_sgi' => ['conteudo'],
        'sts_missao_visao_valores' => ['conteudo'],
        'sts_nossa_historia' => ['conteudo'],
        'sts_objetivos_qualidade' => ['descricao'],
        'sts_atas' => ['pautas', 'registro'],
        'sts_projetos' => ['descricao'],
    ];

    public function handle(HtmlSanitizer $sanitizer): int
    {
        $changed = 0;

        foreach (self::TARGETS as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $available = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
            if ($available === []) {
                continue;
            }

            DB::table($table)->select(array_merge(['id'], $available))->orderBy('id')->chunkById(200, function ($rows) use ($table, $available, $sanitizer, &$changed) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($available as $column) {
                        $clean = $sanitizer->sanitize($row->{$column});
                        if ($clean !== $row->{$column}) {
                            $updates[$column] = $clean;
                        }
                    }

                    if ($updates !== []) {
                        $changed++;
                        if (!$this->option('dry-run')) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                }
            });
        }

        $this->info(($this->option('dry-run') ? 'Registros que seriam alterados: ' : 'Registros sanitizados: ').$changed);
        return self::SUCCESS;
    }
}
