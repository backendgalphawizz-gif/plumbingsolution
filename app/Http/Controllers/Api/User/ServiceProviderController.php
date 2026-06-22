<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => V::searchRules(),
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $providers = ServiceProvider::where('status', ProviderStatus::Approved)
            ->with('images')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->category_id, function ($q, $categoryId) {
                $q->whereHas('services', fn ($sq) => $sq->where('service_category_id', $categoryId)->where('status', true));
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('service_area', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($providers->items())->map(fn ($p) => UserApiFormatter::serviceProvider($p)),
            'pagination' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'total' => $providers->total(),
            ],
        ]);
    }

    public function show(ServiceProvider $serviceProvider): JsonResponse
    {
        if ($serviceProvider->status !== ProviderStatus::Approved) {
            return $this->error('Service provider not available.', 404);
        }

        $serviceProvider->load([
            'images',
            'services' => fn ($q) => $q->where('status', true)->with('category'),
            'reviews' => fn ($q) => $q->with('user')->latest()->limit(10),
        ]);
        $serviceProvider->loadAvg('reviews', 'rating');
        $serviceProvider->loadCount('reviews');

        return $this->success(UserApiFormatter::serviceProvider($serviceProvider, detailed: true));
    }

    public function reviews(ServiceProvider $serviceProvider): JsonResponse
    {
        if ($serviceProvider->status !== ProviderStatus::Approved) {
            return $this->error('Service provider not available.', 404);
        }

        $reviews = $serviceProvider->reviews()
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->success([
            'service_provider_id' => $serviceProvider->id,
            'rating' => round((float) $serviceProvider->reviews()->avg('rating'), 1),
            'reviews_count' => $serviceProvider->reviews()->count(),
            'items' => collect($reviews->items())->map(fn ($r) => UserApiFormatter::serviceProviderReview($r)),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function storeReview(Request $request, ServiceProvider $serviceProvider): JsonResponse
    {
        if ($serviceProvider->status !== ProviderStatus::Approved) {
            return $this->error('Service provider not available.', 404);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'service_booking_id' => ['nullable', 'exists:service_bookings,id'],
        ]);

        if (! empty($data['service_booking_id'])) {
            $ownsBooking = $request->user()->serviceBookings()
                ->where('id', $data['service_booking_id'])
                ->where('service_provider_id', $serviceProvider->id)
                ->exists();

            if (! $ownsBooking) {
                return $this->error('Invalid booking for review.', 422);
            }
        }

        if (ServiceProviderReview::where('user_id', $request->user()->id)
            ->where('service_provider_id', $serviceProvider->id)
            ->exists()) {
            return $this->error('You have already reviewed this service provider.', 422);
        }

        $review = ServiceProviderReview::create([
            'user_id' => $request->user()->id,
            'service_provider_id' => $serviceProvider->id,
            'service_booking_id' => $data['service_booking_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return $this->success(UserApiFormatter::serviceProviderReview($review), 'Review submitted.', 201);
    }
}
