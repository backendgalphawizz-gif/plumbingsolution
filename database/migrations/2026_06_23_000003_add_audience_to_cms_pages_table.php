<?php

use App\Models\CmsPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('audience', 20)->default('user')->after('slug');
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'audience']);
        });

        foreach (['privacy-policy', 'terms-and-conditions'] as $slug) {
            $source = CmsPage::where('slug', $slug)->where('audience', 'user')->first()
                ?? CmsPage::where('slug', $slug)->first();

            if (! $source) {
                continue;
            }

            $source->update(['audience' => 'user']);

            foreach (['vendor', 'provider'] as $audience) {
                CmsPage::firstOrCreate(
                    ['slug' => $slug, 'audience' => $audience],
                    [
                        'title' => $source->title,
                        'content' => $source->content,
                        'is_active' => $source->is_active,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        CmsPage::whereIn('slug', ['privacy-policy', 'terms-and-conditions'])
            ->whereIn('audience', ['vendor', 'provider'])
            ->delete();

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropUnique(['slug', 'audience']);
            $table->unique(['slug']);
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
