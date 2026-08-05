<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FmArquivo extends Model
{
    use SoftDeletes;

    protected $table = 'fm_arquivos';

    protected $fillable = [
        'company_id', 'pasta_id', 'nome_original', 'nome_disco',
        'tipo_mime', 'tamanho', 'caminho', 'is_starred', 'created_by',
    ];

    protected $casts = [
        'is_starred' => 'boolean',
        'tamanho'    => 'integer',
    ];

    protected $appends = ['tamanho_formatado', 'extensao'];

    public function pasta()
    {
        return $this->belongsTo(FmPasta::class, 'pasta_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho ?? 0;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getExtensaoAttribute(): string
    {
        return strtolower(pathinfo($this->nome_original, PATHINFO_EXTENSION));
    }
}
