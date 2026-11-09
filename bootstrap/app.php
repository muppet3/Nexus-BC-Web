<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Los códigos de barras se guardan tal cual se escanean (con sus espacios):
        // Laravel no debe recortarlos al llegar del celular. Solo estos campos;
        // todo lo demás se sigue recortando como siempre. (La web con Livewire
        // ya se salta este recorte por su cuenta.)
        $middleware->trimStrings(except: ['codigo_barras', 'codigo', 'codigo_impreso']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
