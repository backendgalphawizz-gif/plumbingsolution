<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\Service;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => array_merge(['required'], V::searchRules())]);
        $q = $data['q'];

        $products = Product::where('status', true)
            ->with(['vendor', 'images', 'category'])
            ->where(function ($query) use ($q) {
                $query->where('product_name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($p) => UserApiFormatter::product($p));

        $services = Service::where('status', true)
            ->with('category')
            ->where('name', 'like', "%{$q}%")
            ->limit(10)
            ->get()
            ->map(fn ($s) => UserApiFormatter::service($s));

        return $this->success([
            'products' => $products,
            'services' => $services,
        ]);
    }
}
