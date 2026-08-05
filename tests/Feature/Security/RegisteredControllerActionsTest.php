<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegisteredControllerActionsTest extends TestCase
{
    public function test_every_registered_controller_action_exists(): void
    {
        $missingActions = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            if (! method_exists($controller, $method)) {
                $missingActions[] = sprintf(
                    '%s %s -> %s',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $action,
                );
            }
        }

        $this->assertSame(
            [],
            $missingActions,
            "Existem rotas apontando para metodos inexistentes:\n".implode("\n", $missingActions),
        );
    }
}
