<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\BulkOrderController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CmsController as AdminCmsController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ServiceBookingController;
use App\Http\Controllers\Api\Admin\ServiceProviderController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\Admin\VendorController;
use App\Http\Controllers\Api\User\AddressController;
use App\Http\Controllers\Api\User\AuthController as UserAuthController;
use App\Http\Controllers\Api\User\BannerController;
use App\Http\Controllers\Api\User\BookingController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\CategoryController;
use App\Http\Controllers\Api\User\CheckoutController;
use App\Http\Controllers\Api\User\CmsController;
use App\Http\Controllers\Api\User\HomeController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\ProductController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\SearchController;
use App\Http\Controllers\Api\User\ServiceController;
use App\Http\Controllers\Api\User\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {
    Route::post('auth/send-otp', [UserAuthController::class, 'sendOtp']);
    Route::post('auth/login', [UserAuthController::class, 'login']);
    Route::post('auth/register', [UserAuthController::class, 'register']);

    Route::get('home', [HomeController::class, 'index']);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('service-categories', [ServiceController::class, 'categories']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);
    Route::get('search', [SearchController::class, 'index']);
    Route::get('cms/{slug}', [CmsController::class, 'show']);
    Route::get('faqs', [CmsController::class, 'faqs']);

    Route::middleware(['auth:sanctum', 'user.auth'])->group(function () {
        Route::post('auth/logout', [UserAuthController::class, 'logout']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::put('addresses/{userAddress}', [AddressController::class, 'update']);
        Route::delete('addresses/{userAddress}', [AddressController::class, 'destroy']);

        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{cartItem}', [CartController::class, 'update']);
        Route::delete('cart/{cartItem}', [CartController::class, 'destroy']);
        Route::post('cart/apply-coupon', [CartController::class, 'applyCoupon']);

        Route::get('checkout/payment-methods', [CheckoutController::class, 'paymentMethods']);
        Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        Route::get('bookings/available-slots', [BookingController::class, 'availableSlots']);
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{serviceBooking}', [BookingController::class, 'show']);
        Route::post('bookings/{serviceBooking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{serviceBooking}/reschedule', [BookingController::class, 'reschedule']);

        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{userNotification}/read', [NotificationController::class, 'markRead']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AdminAuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', 'admin.auth'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('profile', [AdminAuthController::class, 'profile']);
        Route::put('profile', [AdminAuthController::class, 'updateProfile']);
        Route::post('change-password', [AdminAuthController::class, 'changePassword']);

        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/charts', [DashboardController::class, 'charts']);
        Route::get('dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

        Route::apiResource('categories', AdminCategoryController::class);
        Route::get('subcategories', [AdminCategoryController::class, 'subcategories']);
        Route::post('subcategories', [AdminCategoryController::class, 'storeSubcategory']);
        Route::put('subcategories/{subcategory}', [AdminCategoryController::class, 'updateSubcategory']);
        Route::delete('subcategories/{subcategory}', [AdminCategoryController::class, 'destroySubcategory']);

        Route::apiResource('products', AdminProductController::class);

        Route::get('vendors', [VendorController::class, 'index']);
        Route::get('vendors/{vendor}', [VendorController::class, 'show']);
        Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve']);
        Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject']);
        Route::post('vendors/{vendor}/suspend', [VendorController::class, 'suspend']);
        Route::post('vendor-documents/{document}/verify', [VendorController::class, 'verifyDocument']);

        Route::get('service-providers', [ServiceProviderController::class, 'index']);
        Route::get('service-providers/{serviceProvider}', [ServiceProviderController::class, 'show']);
        Route::post('service-providers/{serviceProvider}/approve', [ServiceProviderController::class, 'approve']);
        Route::post('service-providers/{serviceProvider}/reject', [ServiceProviderController::class, 'reject']);
        Route::post('service-providers/{serviceProvider}/suspend', [ServiceProviderController::class, 'suspend']);
        Route::post('provider-documents/{document}/verify', [ServiceProviderController::class, 'verifyDocument']);

        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::post('customers/{customer}/block', [CustomerController::class, 'block']);
        Route::post('customers/{customer}/unblock', [CustomerController::class, 'unblock']);
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orderHistory']);
        Route::get('customers/{customer}/bookings', [CustomerController::class, 'bookingHistory']);

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel']);
        Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund']);

        Route::get('service-bookings', [ServiceBookingController::class, 'index']);
        Route::get('service-bookings/{serviceBooking}', [ServiceBookingController::class, 'show']);
        Route::post('service-bookings/{serviceBooking}/assign', [ServiceBookingController::class, 'assignProvider']);
        Route::put('service-bookings/{serviceBooking}/status', [ServiceBookingController::class, 'updateStatus']);

        Route::get('bulk-orders', [BulkOrderController::class, 'index']);
        Route::get('bulk-orders/{bulkOrder}', [BulkOrderController::class, 'show']);
        Route::post('bulk-orders/{bulkOrder}/review', [BulkOrderController::class, 'review']);
        Route::post('bulk-orders/{bulkOrder}/quotations', [BulkOrderController::class, 'createQuotation']);
        Route::post('bulk-orders/{bulkOrder}/quotations/{quotation}/send', [BulkOrderController::class, 'sendQuotation']);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
        Route::post('payments/{payment}/refund', [PaymentController::class, 'processRefund']);

        Route::get('reports', [ReportController::class, 'index']);

        Route::get('notifications', [AdminNotificationController::class, 'index']);
        Route::post('notifications', [AdminNotificationController::class, 'store']);
        Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy']);

        Route::apiResource('banners', AdminBannerController::class);

        Route::get('cms', [AdminCmsController::class, 'index']);
        Route::get('cms/{slug}', [AdminCmsController::class, 'show']);
        Route::post('cms', [AdminCmsController::class, 'store']);
        Route::put('cms/{cmsPage}', [AdminCmsController::class, 'update']);

        Route::get('tickets', [AdminTicketController::class, 'index']);
        Route::get('tickets/{ticket}', [AdminTicketController::class, 'show']);
        Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply']);
        Route::post('tickets/{ticket}/close', [AdminTicketController::class, 'close']);

        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::get('settings/commission', [SettingController::class, 'commission']);
    });
});
