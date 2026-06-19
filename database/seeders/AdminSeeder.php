<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\CmsPage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'group' => 'catalog'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'group' => 'catalog'],
            ['name' => 'Manage Vendors', 'slug' => 'vendors.manage', 'group' => 'users'],
            ['name' => 'Manage Providers', 'slug' => 'providers.manage', 'group' => 'users'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'group' => 'users'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'group' => 'orders'],
            ['name' => 'Manage Bookings', 'slug' => 'bookings.manage', 'group' => 'orders'],
            ['name' => 'Manage Bulk Orders', 'slug' => 'bulk_orders.manage', 'group' => 'orders'],
            ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'group' => 'finance'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'finance'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'system'],
            ['name' => 'Manage CMS', 'slug' => 'cms.manage', 'group' => 'system'],
            ['name' => 'Manage Banners', 'slug' => 'banners.manage', 'group' => 'system'],
            ['name' => 'Manage Notifications', 'slug' => 'notifications.manage', 'group' => 'system'],
            ['name' => 'Manage Tickets', 'slug' => 'tickets.manage', 'group' => 'support'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full system access']
        );

        $superAdminRole->permissions()->sync(Permission::pluck('id'));

        $admin = Admin::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'James Wilson',
                'mobile' => '9876543210',
                'password' => Hash::make('password'),
                'role_title' => 'Senior Admin',
                'is_active' => true,
            ]
        );

        // Migrate legacy admin email if present
        Admin::where('email', 'admin@gmail.com')->update(['email' => 'admin@gmail.com']);

        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $cmsPages = [
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
            ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions'],
            ['slug' => 'faqs', 'title' => 'FAQs'],
            ['slug' => 'about-us', 'title' => 'About Us'],
            ['slug' => 'contact-us', 'title' => 'Contact Us'],
        ];

        foreach ($cmsPages as $page) {
            CmsPage::firstOrCreate(['slug' => $page['slug']], [
                'title' => $page['title'],
                'content' => '<p>Content for '.$page['title'].'</p>',
                'is_active' => true,
            ]);
        }

        $defaultSettings = [
            ['group' => 'app', 'key' => 'app_name', 'value' => 'PlumbManager', 'type' => 'string'],
            ['group' => 'app', 'key' => 'support_email', 'value' => 'support@plumbmanager.com', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'razorpay_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'phonepe_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'cod_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'commission', 'key' => 'vendor_commission', 'value' => '10', 'type' => 'decimal'],
            ['group' => 'commission', 'key' => 'provider_commission', 'value' => '15', 'type' => 'decimal'],
            ['group' => 'commission', 'key' => 'platform_charges', 'value' => '2', 'type' => 'decimal'],
            ['group' => 'tax', 'key' => 'gst_rate', 'value' => '18', 'type' => 'decimal'],
            ['group' => 'notification', 'key' => 'push_enabled', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }
    }
}
