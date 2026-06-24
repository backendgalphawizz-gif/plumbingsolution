<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BulkOrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderReturnController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceBookingController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceProviderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('admin.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('categories/export', [CategoryController::class, 'exportCategories'])->name('categories.export');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('subcategories/export', [CategoryController::class, 'exportSubcategories'])->name('subcategories.export');
        Route::get('subcategories', [CategoryController::class, 'subcategoriesIndex'])->name('subcategories.index');
        Route::post('categories/{category}/subcategories', [CategoryController::class, 'storeSubcategory'])->name('categories.subcategories.store');
        Route::put('subcategories/{subcategory}', [CategoryController::class, 'updateSubcategory'])->name('subcategories.update');
        Route::delete('subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');

        Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('vendors/export', [VendorController::class, 'export'])->name('vendors.export');
        Route::get('vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
        Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve'])->name('vendors.approve');
        Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject'])->name('vendors.reject');
        Route::post('vendors/{vendor}/suspend', [VendorController::class, 'suspend'])->name('vendors.suspend');

        Route::get('service-providers/export', [ServiceProviderController::class, 'export'])->name('service-providers.export');
        Route::get('service-providers/create', [ServiceProviderController::class, 'create'])->name('service-providers.create');
        Route::post('service-providers', [ServiceProviderController::class, 'store'])->name('service-providers.store');
        Route::get('service-providers', [ServiceProviderController::class, 'index'])->name('service-providers.index');
        Route::get('service-providers/{serviceProvider}/edit', [ServiceProviderController::class, 'edit'])->name('service-providers.edit');
        Route::put('service-providers/{serviceProvider}', [ServiceProviderController::class, 'update'])->name('service-providers.update');
        Route::get('service-providers/{serviceProvider}', [ServiceProviderController::class, 'show'])->name('service-providers.show');
        Route::post('service-providers/{serviceProvider}/approve', [ServiceProviderController::class, 'approve'])->name('service-providers.approve');
        Route::post('service-providers/{serviceProvider}/reject', [ServiceProviderController::class, 'reject'])->name('service-providers.reject');
        Route::post('service-providers/{serviceProvider}/suspend', [ServiceProviderController::class, 'suspend'])->name('service-providers.suspend');

        Route::get('services/export', [ServiceController::class, 'export'])->name('services.export');
        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('customers/{customer}/block', [CustomerController::class, 'block'])->name('customers.block');
        Route::post('customers/{customer}/unblock', [CustomerController::class, 'unblock'])->name('customers.unblock');

        Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('order-returns', [OrderReturnController::class, 'index'])->name('order-returns.index');
        Route::get('order-returns/{orderReturn}', [OrderReturnController::class, 'show'])->name('order-returns.show');
        Route::post('order-returns/{orderReturn}/approve', [OrderReturnController::class, 'approve'])->name('order-returns.approve');
        Route::post('order-returns/{orderReturn}/reject', [OrderReturnController::class, 'reject'])->name('order-returns.reject');

        Route::get('coupons/order', [CouponController::class, 'orderIndex'])->name('coupons.order.index');
        Route::post('coupons/order', [CouponController::class, 'storeOrder'])->name('coupons.order.store');
        Route::put('coupons/order/{coupon}', [CouponController::class, 'updateOrder'])->name('coupons.order.update');
        Route::delete('coupons/order/{coupon}', [CouponController::class, 'destroyOrder'])->name('coupons.order.destroy');

        Route::get('coupons/booking', [CouponController::class, 'bookingIndex'])->name('coupons.booking.index');
        Route::post('coupons/booking', [CouponController::class, 'storeBooking'])->name('coupons.booking.store');
        Route::put('coupons/booking/{coupon}', [CouponController::class, 'updateBooking'])->name('coupons.booking.update');
        Route::delete('coupons/booking/{coupon}', [CouponController::class, 'destroyBooking'])->name('coupons.booking.destroy');

        Route::get('service-bookings/export', [ServiceBookingController::class, 'export'])->name('service-bookings.export');
        Route::get('service-bookings', [ServiceBookingController::class, 'index'])->name('service-bookings.index');
        Route::get('service-bookings/{serviceBooking}', [ServiceBookingController::class, 'show'])->name('service-bookings.show');
        Route::post('service-bookings/{serviceBooking}/assign', [ServiceBookingController::class, 'assign'])->name('service-bookings.assign');
        Route::put('service-bookings/{serviceBooking}/status', [ServiceBookingController::class, 'updateStatus'])->name('service-bookings.update-status');

        Route::get('bulk-orders/export', [BulkOrderController::class, 'export'])->name('bulk-orders.export');
        Route::get('bulk-orders', [BulkOrderController::class, 'index'])->name('bulk-orders.index');
        Route::get('bulk-orders/{bulkOrder}', [BulkOrderController::class, 'show'])->name('bulk-orders.show');
        Route::post('bulk-orders/{bulkOrder}/review', [BulkOrderController::class, 'review'])->name('bulk-orders.review');
        Route::post('bulk-orders/{bulkOrder}/quotations', [BulkOrderController::class, 'createQuotation'])->name('bulk-orders.quotations.store');
        Route::post('bulk-orders/{bulkOrder}/quotations/{quotation}/send', [BulkOrderController::class, 'sendQuotation'])->name('bulk-orders.quotations.send');

        Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

        Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/{type}/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show')->where(['type' => 'vendor|provider|user']);
        Route::post('withdrawals/{type}/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve')->where(['type' => 'vendor|provider|user']);
        Route::post('withdrawals/{type}/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject')->where(['type' => 'vendor|provider|user']);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('settings/cms/{cmsPage}', [SettingController::class, 'updateCms'])->name('settings.cms.update');
        Route::post('settings/faqs', [SettingController::class, 'storeFaq'])->name('settings.faqs.store');
        Route::put('settings/faqs/{faq}', [SettingController::class, 'updateFaq'])->name('settings.faqs.update');
        Route::delete('settings/faqs/{faq}', [SettingController::class, 'destroyFaq'])->name('settings.faqs.destroy');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    });
});
