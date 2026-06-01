<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class SGIModel extends Model
{
    /**
     * Nomes personalizados das colunas de data/hora no banco legado MyISAM.
     */
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    /**
     * Opcional: Se as tabelas legadas não tiverem as colunas created/modified,
     * isso pode ser desabilitado nos models filhos alterando para false.
     */
    public $timestamps = true;

    // TODO: Adicionar Global Scopes para company_id / obra_id caso aplicável
}
