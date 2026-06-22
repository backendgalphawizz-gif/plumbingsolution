<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function index(Product $product): JsonResponse
    {
        if (! $product->status) {
            return $this->error('Product not available.', 404);
        }

        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->success([
            'product_id' => $product->id,
            'rating' => round((float) $product->reviews()->avg('rating'), 1),
            'reviews_count' => $product->reviews()->count(),
            'items' => collect($reviews->items())->map(fn ($r) => UserApiFormatter::review($r)),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        if (! $product->status) {
            return $this->error('Product not available.', 404);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'order_id' => ['nullable', 'exists:orders,id'],
        ]);

        if (! empty($data['order_id'])) {
            $ownsOrder = $request->user()->orders()->where('id', $data['order_id'])->exists();
            if (! $ownsOrder) {
                return $this->error('Invalid order for review.', 422);
            }
        }

        if (ProductReview::where('user_id', $request->user()->id)->where('product_id', $product->id)->exists()) {
            return $this->error('You have already reviewed this product.', 422);
        }

        $review = ProductReview::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return $this->success(UserApiFormatter::review($review), 'Review submitted.', 201);
    }
}
