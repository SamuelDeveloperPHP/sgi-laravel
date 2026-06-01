<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // AuthorizesRequests permite usar $this->authorize() e
    // $this->authorizeResource() em qualquer controller filho.
    // Laravel 12 removeu este trait da base por padrão; reintroduzimos
    // aqui para suportar as Policies do projeto.
    use AuthorizesRequests;
}
