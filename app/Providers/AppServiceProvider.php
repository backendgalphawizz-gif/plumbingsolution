<?php

namespace App\Providers;

use App\Support\AdminValidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $imageMaxKb = (string) AdminValidation::limit('image_kb');

        Validator::replacer('max', function (string $message, string $attribute, string $rule, array $parameters) use ($imageMaxKb) {
            if (($parameters[0] ?? null) === $imageMaxKb) {
                return 'Image is too large. Maximum upload size is '.AdminValidation::imageMaxMb().' MB.';
            }

            return $message;
        });
    }
}
