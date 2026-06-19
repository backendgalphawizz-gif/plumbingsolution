<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $banners = Banner::where('status', true)->orderBy('sort_order')->get()
            ->map(fn ($b) => UserApiFormatter::banner($b));

        $productCategories = Category::where('status', true)
            ->withCount(['products' => fn ($q) => $q->where('status', true)])
            ->orderBy('sort_order')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? asset('storage/'.$c->image) : null,
                'items_count' => $c->products_count,
                'type' => 'product',
            ]);

        $serviceCategories = ServiceCategory::where('status', true)
            ->withCount(['services' => fn ($q) => $q->where('status', true)])
            ->orderBy('sort_order')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? asset('storage/'.$c->image) : null,
                'providers_count' => $c->services_count,
                'type' => 'service',
            ]);

        $featuredServices = Service::where('status', true)
            ->with('category')
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(fn ($s) => UserApiFormatter::service($s));

        $featuredProducts = Product::where('status', true)
            ->with(['vendor', 'images', 'category'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($p) => UserApiFormatter::product($p));

        return $this->success([
            'location' => $request->get('location', 'Indore, India'),
            'banners' => $banners,
            'promo_cards' => [
                [
                    'title' => 'Emergency Plumbing',
                    'subtitle' => '15 mins away',
                    'type' => 'emergency',
                    'color' => '#f97316',
                ],
                [
                    'title' => 'Bulk Ordering',
                    'subtitle' => 'For Professionals',
                    'type' => 'bulk',
                    'color' => '#2563eb',
                ],
            ],
            'product_categories' => $productCategories,
            'service_categories' => $serviceCategories,
            'featured_services' => $featuredServices,
            'featured_products' => $featuredProducts,
        ]);
    }
}
