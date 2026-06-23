<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'user.auth' => \App\Http\Middleware\UserAuthenticate::class,
            'vendor.auth' => \App\Http\Middleware\VendorAuthenticate::class,
            'provider.auth' => \App\Http\Middleware\ProviderAuthenticate::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if (! $request->is('admin') && ! $request->is('admin/*')) {
                return null;
            }

            $message = 'Something went wrong while saving. Please check your input and try again.';

            if (str_contains($e->getMessage(), 'Data too long')) {
                $message = 'One or more fields contain too much text. Please shorten your input and try again.';
                if (preg_match("/column '([^']+)'/", $e->getMessage(), $matches)) {
                    $field = str_replace('_', ' ', $matches[1]);
                    $message = 'The '.ucfirst($field).' field is too long. Please shorten your input.';
                }
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $message = 'This value already exists. Please use a different one.';
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $message);
        });
    })->create();
