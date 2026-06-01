<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controller base do projeto.
 *
 * IMPORTANTE — extends BaseController:
 *   authorizeResource() internamente chama $this->middleware(...) para
 *   registrar a checagem de Gate como middleware do controller. Esse
 *   método vem de Illuminate\Routing\Controller. A `abstract class
 *   Controller {}` que Laravel 12 gera por padrão NÃO tem isso, então
 *   qualquer controller chamando $this->authorizeResource(...) quebra
 *   em runtime ("Call to undefined method middleware()") na primeira
 *   request HTTP que atinge o controller.
 *
 * Detectado pelos testes adversários cross-tenant na Fase 3.A.
 */
abstract class Controller extends BaseController
{
    // AuthorizesRequests permite usar $this->authorize() e
    // $this->authorizeResource() em qualquer controller filho.
    use AuthorizesRequests;
}
