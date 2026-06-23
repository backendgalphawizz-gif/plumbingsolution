<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::where('group', 'commission')->where('key', 'platform_charges')->delete();
    }

    public function down(): void
    {
        Setting::setValue('commission', 'platform_charges', '2', 'decimal');
    }
};
