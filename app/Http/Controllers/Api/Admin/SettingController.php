<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        if ($request->group) {
            $query->where('group', $request->group);
        }

        return $this->success($query->get()->groupBy('group'));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.group' => ['required', 'string'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['nullable', 'string'],
        ]);

        foreach ($request->settings as $item) {
            Setting::setValue(
                $item['group'],
                $item['key'],
                $item['value'] ?? '',
                $item['type'] ?? 'string'
            );
        }

        return $this->success(null, 'Settings updated.');
    }

    public function commission(): JsonResponse
    {
        return $this->success([
            'vendor_commission' => Setting::getValue('commission', 'vendor_commission', '10'),
            'provider_commission' => Setting::getValue('commission', 'provider_commission', '15'),
            'platform_charges' => Setting::getValue('commission', 'platform_charges', '2'),
        ]);
    }
}
