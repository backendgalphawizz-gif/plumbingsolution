<?php

use App\Http\Controllers\Api\V1\AuthOtpController;
use App\Http\Controllers\Api\V1\AuthRegisterController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\BulkOrderController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
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
use App\Http\Controllers\Api\User\BulkOrderController as UserBulkOrderController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\CategoryController;
use App\Http\Controllers\Api\User\CheckoutController;
use App\Http\Controllers\Api\User\CouponController;
use App\Http\Controllers\Api\User\CmsController;
use App\Http\Controllers\Api\User\HomeController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\ProductController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\PromoCodeController;
use App\Http\Controllers\Api\User\ConfigController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\SearchController;
use App\Http\Controllers\Api\User\ServiceController;
use App\Http\Controllers\Api\User\ServiceProviderController as UserServiceProviderController;
use App\Http\Controllers\Api\User\TicketController;
use App\Http\Controllers\Api\Provider\AuthController as ProviderAuthController;
use App\Http\Controllers\Api\Provider\BookingController as ProviderBookingController;
use App\Http\Controllers\Api\Provider\ConfigController as ProviderConfigController;
use App\Http\Controllers\Api\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Api\Provider\EarningsController as ProviderEarningsController;
use App\Http\Controllers\Api\Provider\LookupController as ProviderLookupController;
use App\Http\Controllers\Api\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Api\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Api\Vendor\AuthController as VendorAuthController;
use App\Http\Controllers\Api\Vendor\ConfigController as VendorConfigController;
use App\Http\Controllers\Api\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Api\Vendor\EarningsController as VendorEarningsController;
use App\Http\Controllers\Api\Vendor\LookupController as VendorLookupController;
use App\Http\Controllers\Api\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\Vendor\ProfileController as VendorProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {
    Route::post('auth/send-otp', [UserAuthController::class, 'sendOtp']);
    Route::post('auth/verify-otp', [UserAuthController::class, 'verifyOtp']);
    Route::post('auth/login', [UserAuthController::class, 'login']);

    Route::get('home', [HomeController::class, 'index']);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/{product}/reviews', [ReviewController::class, 'index']);
    Route::get('config', [ConfigController::class, 'index']);
    Route::get('service-categories', [ServiceController::class, 'categories']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);
    Route::get('service-providers', [UserServiceProviderController::class, 'index']);
    Route::get('service-providers/{serviceProvider}', [UserServiceProviderController::class, 'show']);
    Route::get('service-providers/{serviceProvider}/reviews', [UserServiceProviderController::class, 'reviews']);
    Route::get('search', [SearchController::class, 'index']);
    Route::get('cms/{slug}', [CmsController::class, 'show']);
    Route::get('faqs', [CmsController::class, 'faqs']);
    Route::get('promo-codes', [PromoCodeController::class, 'index']);
    Route::post('promo-codes/apply', [PromoCodeController::class, 'apply']);

    Route::middleware(['auth:sanctum', 'user.auth'])->group(function () {
        Route::post('auth/logout', [UserAuthController::class, 'logout']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::post('profile', [ProfileController::class, 'update']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('products/{product}/reviews', [ReviewController::class, 'store']);
        Route::post('service-providers/{serviceProvider}/reviews', [UserServiceProviderController::class, 'storeReview']);

        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::put('addresses/{userAddress}', [AddressController::class, 'update']);
        Route::delete('addresses/{userAddress}', [AddressController::class, 'destroy']);

        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{cartItem}', [CartController::class, 'update']);
        Route::delete('cart/{cartItem}', [CartController::class, 'destroy']);
        Route::post('cart/apply-coupon', [CartController::class, 'applyCoupon']);
        Route::get('coupons', [CouponController::class, 'index']);

        Route::get('checkout/payment-methods', [CheckoutController::class, 'paymentMethods']);
        Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        Route::get('bulk-orders', [UserBulkOrderController::class, 'index']);
        Route::post('bulk-orders', [UserBulkOrderController::class, 'store']);
        Route::get('bulk-orders/{bulkOrder}', [UserBulkOrderController::class, 'show']);
        Route::post('bulk-orders/quotations/accept', [UserBulkOrderController::class, 'acceptQuotation']);
        Route::post('bulk-orders/quotations/reject', [UserBulkOrderController::class, 'rejectQuotation']);

        Route::get('bookings/payment-methods', [BookingController::class, 'paymentMethods']);
        Route::get('bookings/available-slots', [BookingController::class, 'availableSlots']);
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{serviceBooking}', [BookingController::class, 'show']);
        Route::post('bookings/{serviceBooking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{serviceBooking}/reschedule', [BookingController::class, 'reschedule']);
        Route::post('bookings/{serviceBooking}/review', [BookingController::class, 'review']);

        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{userNotification}/read', [NotificationController::class, 'markRead']);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('config', [ProviderConfigController::class, 'index']);

    Route::post('auth/send-otp', [AuthOtpController::class, 'sendOtp']);
    Route::post('auth/verify-otp', [AuthOtpController::class, 'verifyOtp']);
    Route::post('auth/login', [ProviderAuthController::class, 'login']);
    Route::post('auth/register', [AuthRegisterController::class, 'register']);

    Route::middleware(['auth:sanctum', 'provider.auth'])->group(function () {
        Route::post('auth/logout', [ProviderAuthController::class, 'logout']);

        Route::get('dashboard', [ProviderDashboardController::class, 'index']);

        Route::get('bookings', [ProviderBookingController::class, 'index']);
        Route::get('bookings/{booking}', [ProviderBookingController::class, 'show']);
        Route::post('bookings/{booking}/accept', [ProviderBookingController::class, 'accept']);
        Route::post('bookings/{booking}/reject', [ProviderBookingController::class, 'reject']);
        Route::post('bookings/{booking}/start', [ProviderBookingController::class, 'start']);
        Route::post('bookings/{booking}/complete', [ProviderBookingController::class, 'complete']);

        Route::get('services', [ProviderServiceController::class, 'index']);
        Route::post('services', [ProviderServiceController::class, 'store']);
        Route::get('services/{service}', [ProviderServiceController::class, 'show']);
        Route::put('services/{service}', [ProviderServiceController::class, 'update']);
        Route::post('services/{service}', [ProviderServiceController::class, 'update']);
        Route::post('services/{service}/availability', [ProviderServiceController::class, 'updateAvailability']);
        Route::post('services/{service}/available', [ProviderServiceController::class, 'available']);
        Route::post('services/{service}/unavailable', [ProviderServiceController::class, 'unavailable']);
        Route::delete('services/{service}', [ProviderServiceController::class, 'destroy']);

        Route::get('lookups/categories', [ProviderLookupController::class, 'categories']);

        Route::get('profile', [ProviderProfileController::class, 'show']);
        Route::post('profile', [ProviderProfileController::class, 'update']);
        Route::put('profile', [ProviderProfileController::class, 'update']);
        Route::get('profile/personal-details', [ProviderProfileController::class, 'personalDetails']);
        Route::put('profile/personal-details', [ProviderProfileController::class, 'updatePersonalDetails']);
        Route::post('profile/personal-details', [ProviderProfileController::class, 'updatePersonalDetails']);
        Route::get('profile/bank-details', [ProviderProfileController::class, 'bankDetails']);
        Route::put('profile/bank-details', [ProviderProfileController::class, 'updateBankDetails']);
        Route::post('profile/bank-details', [ProviderProfileController::class, 'updateBankDetails']);
        Route::get('profile/skills-details', [ProviderProfileController::class, 'skillsDetails']);
        Route::put('profile/skills-details', [ProviderProfileController::class, 'updateSkillsDetails']);
        Route::post('profile/skills-details', [ProviderProfileController::class, 'updateSkillsDetails']);

        Route::get('earnings', [ProviderEarningsController::class, 'index']);
        Route::post('earnings/withdraw', [ProviderEarningsController::class, 'withdraw']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{userNotification}/read', [NotificationController::class, 'markRead']);
    });
});

Route::prefix('v2')->group(function () {
    Route::get('config', [VendorConfigController::class, 'index']);

    Route::post('auth/send-otp', [VendorAuthController::class, 'sendOtp']);
    Route::post('auth/verify-otp', [VendorAuthController::class, 'verifyOtp']);
    Route::post('auth/login', [VendorAuthController::class, 'login']);
    Route::post('auth/register', [VendorAuthController::class, 'register']);

    Route::middleware(['auth:sanctum', 'vendor.auth'])->group(function () {
        Route::post('auth/logout', [VendorAuthController::class, 'logout']);

        Route::get('dashboard', [VendorDashboardController::class, 'index']);

        Route::get('orders', [VendorOrderController::class, 'index']);
        Route::get('orders/{order}', [VendorOrderController::class, 'show']);
        Route::post('orders/{order}/accept', [VendorOrderController::class, 'accept']);
        Route::post('orders/{order}/reject', [VendorOrderController::class, 'reject']);
        Route::post('orders/{order}/out-for-delivery', [VendorOrderController::class, 'outForDelivery']);
        Route::post('orders/{order}/deliver', [VendorOrderController::class, 'deliver']);

        Route::get('products', [VendorProductController::class, 'index']);
        Route::post('products', [VendorProductController::class, 'store']);
        Route::get('products/{product}', [VendorProductController::class, 'show']);
        Route::put('products/{product}', [VendorProductController::class, 'update']);
        Route::post('products/{product}', [VendorProductController::class, 'update']);
        Route::delete('products/{product}', [VendorProductController::class, 'destroy']);

        Route::get('lookups/categories', [VendorLookupController::class, 'categories']); 

        Route::get('profile', [VendorProfileController::class, 'show']);
        Route::post('profile', [VendorProfileController::class, 'update']);
        Route::put('profile', [VendorProfileController::class, 'update']);
        Route::get('profile/owner-details', [VendorProfileController::class, 'ownerDetails']);
        Route::put('profile/owner-details', [VendorProfileController::class, 'updateOwnerDetails']);
        Route::post('profile/owner-details', [VendorProfileController::class, 'updateOwnerDetails']);
        Route::get('profile/shop-details', [VendorProfileController::class, 'shopDetails']);
        Route::put('profile/shop-details', [VendorProfileController::class, 'updateShopDetails']);
        Route::post('profile/shop-details', [VendorProfileController::class, 'updateShopDetails']);

        Route::get('earnings', [VendorEarningsController::class, 'index']);
        Route::post('earnings/withdraw', [VendorEarningsController::class, 'withdraw']);

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

        Route::prefix('{appliesTo}-coupons')->where(['appliesTo' => 'order|booking'])->group(function () {
            Route::get('/', [AdminCouponController::class, 'index']);
            Route::post('/', [AdminCouponController::class, 'store']);
            Route::get('{coupon}', [AdminCouponController::class, 'show']);
            Route::put('{coupon}', [AdminCouponController::class, 'update']);
            Route::delete('{coupon}', [AdminCouponController::class, 'destroy']);
        });

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
