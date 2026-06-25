<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
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
        $imageTooLargeMessage = 'Image is too large. Maximum upload size is '.\App\Support\AdminValidation::imageMaxMb().' MB.';

        $exceptions->render(function (PostTooLargeException $e, \Illuminate\Http\Request $request) use ($imageTooLargeMessage) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $imageTooLargeMessage,
                    'errors' => null,
                ], 413);
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $imageTooLargeMessage);
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            $isIntegrityViolation = (string) $e->getCode() === '23000'
                || str_contains($e->getMessage(), 'Integrity constraint violation');

            if ($request->is('api/*') || $request->expectsJson()) {
                if (str_contains($e->getMessage(), 'Data too long')) {
                    $message = 'One or more fields contain too much text. Please shorten your input.';
                    if (preg_match("/column '([^']+)'/", $e->getMessage(), $matches)) {
                        $field = str_replace('_', ' ', $matches[1]);
                        $message = 'The '.ucfirst($field).' field is too long. Please shorten your input.';
                    }

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'errors' => null,
                    ], 422);
                }

                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This value already exists. Please use a different one.',
                        'errors' => null,
                    ], 422);
                }

                if ($isIntegrityViolation) {
                    $message = 'This action cannot be completed because the record is linked to other data.';

                    if (str_contains($e->getMessage(), 'order_items')) {
                        $message = 'This product cannot be deleted because it is linked to existing orders. You can deactivate it instead.';
                    }

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'errors' => null,
                    ], 422);
                }

                return null;
            }

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
            } elseif ($isIntegrityViolation) {
                $message = str_contains($e->getMessage(), 'order_items')
                    ? 'Cannot delete product linked to existing orders. Deactivate it instead.'
                    : 'This record cannot be deleted because it is linked to other data.';
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $message);
        });
    })->create();
