<?php

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('address');
        });

        ServiceProvider::query()->with('user')->each(function (ServiceProvider $provider) {
            if (! $provider->user) {
                return;
            }

            $earnings = (float) $provider->bookings()
                ->where('status', BookingStatus::Completed)
                ->sum('amount');

            $locked = (float) $provider->withdrawals()
                ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Paid])
                ->sum('amount');

            $provider->user->update([
                'wallet_balance' => max(0, $earnings - $locked),
            ]);
        });

        Vendor::query()->with('user')->each(function (Vendor $vendor) {
            if (! $vendor->user) {
                return;
            }

            $earnings = (float) $vendor->orders()
                ->where('status', OrderStatus::Delivered)
                ->sum('total_amount');

            $locked = (float) $vendor->withdrawals()
                ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Paid])
                ->sum('amount');

            $vendor->user->update([
                'wallet_balance' => max(0, $earnings - $locked),
            ]);
        });

        User::query()
            ->whereHas('withdrawals', fn ($q) => $q->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Paid]))
            ->whereDoesntHave('serviceProvider')
            ->whereDoesntHave('vendor')
            ->each(function (User $user) {
                $locked = (float) $user->withdrawals()
                    ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Paid])
                    ->sum('amount');

                $user->update([
                    'wallet_balance' => max(0, 0 - $locked),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });
    }
};
