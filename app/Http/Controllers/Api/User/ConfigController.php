<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\AppConfigBuilder;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(AppConfigBuilder::combined());
    }
}
