<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $banners = Banner::where('status', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($b) => UserApiFormatter::banner($b));

        return $this->success($banners);
    }
}
