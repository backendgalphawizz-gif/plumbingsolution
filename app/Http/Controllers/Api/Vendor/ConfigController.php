<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Setting;
use App\Support\AppConfigBuilder;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(AppConfigBuilder::build('vendor', [
            'commission_percent' => (float) Setting::getValue('commission', 'vendor_commission', 10),
        ]));
    }
}
