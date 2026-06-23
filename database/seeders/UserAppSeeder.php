<?php

namespace Database\Seeders;

use App\Enums\ProviderStatus;
use App\Enums\CouponAppliesTo;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserAppSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pipe Repair', 'slug' => 'pipe-repair'],
            ['name' => 'Installation', 'slug' => 'installation'],
            ['name' => 'Drain Cleaning', 'slug' => 'drain-cleaning'],
            ['name' => 'Water Heater', 'slug' => 'water-heater'],
        ];

        foreach ($categories as $i => $cat) {
            $category = ServiceCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name'], 'status' => true, 'sort_order' => $i + 1]
            );

            $services = match ($cat['slug']) {
                'pipe-repair' => [
                    ['name' => 'Leak Repair', 'price' => 89],
                    ['name' => 'Burst Pipe Repair', 'price' => 1200],
                ],
                'installation' => [
                    ['name' => 'Shower Installation', 'price' => 150],
                    ['name' => 'Tap Installation', 'price' => 99],
                ],
                'drain-cleaning' => [
                    ['name' => 'Kitchen Drain Cleaning', 'price' => 199],
                    ['name' => 'Bathroom Drain Cleaning', 'price' => 179],
                ],
                default => [
                    ['name' => 'Geyser Installation', 'price' => 499],
                    ['name' => 'Geyser Repair', 'price' => 349],
                ],
            };

            foreach ($services as $j => $svc) {
                Service::firstOrCreate(
                    ['slug' => Str::slug($svc['name'])],
                    [
                        'service_category_id' => $category->id,
                        'name' => $svc['name'],
                        'description' => 'Professional '.$svc['name'].' service by verified plumbers.',
                        'starting_price' => $svc['price'],
                        'rating' => 4.8,
                        'providers_count' => random_int(20, 60),
                        'status' => true,
                        'sort_order' => $j + 1,
                    ]
                );
            }
        }

        Coupon::updateOrCreate(
            ['code' => 'WELCOME10', 'applies_to' => CouponAppliesTo::Order],
            [
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_amount' => 100,
                'status' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'FLAT50', 'applies_to' => CouponAppliesTo::Order],
            [
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_order_amount' => 200,
                'status' => true,
                'expires_at' => now()->addMonths(3),
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'SERVICE10', 'applies_to' => CouponAppliesTo::Booking],
            [
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_amount' => 100,
                'status' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'BOOK50', 'applies_to' => CouponAppliesTo::Booking],
            [
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_order_amount' => 200,
                'status' => true,
                'expires_at' => now()->addMonths(3),
            ]
        );

        $faqs = [
            [
                'question' => 'How do I book a plumbing service?',
                'answer' => 'Browse services on the home screen, select a service, choose a date and time, and confirm your booking.',
                'audience' => 'user',
            ],
            [
                'question' => 'Can I cancel my order or booking?',
                'answer' => 'Yes. Orders in processing can be cancelled from Order History. Bookings can be cancelled or rescheduled from Booking History.',
                'audience' => 'user',
            ],
            [
                'question' => 'What payment methods are supported?',
                'answer' => 'We support Razorpay online payments and Cash on Delivery for product orders.',
                'audience' => 'user',
            ],
            [
                'question' => 'How long does delivery take?',
                'answer' => 'Standard product delivery typically takes 2–5 business days depending on your location.',
                'audience' => 'user',
            ],
            [
                'question' => 'How do I add or update my products?',
                'answer' => 'Open the Products section in the vendor app, tap Add Product, and fill in details, images, and pricing.',
                'audience' => 'vendor',
            ],
            [
                'question' => 'When do I receive my vendor payouts?',
                'answer' => 'Payouts are processed after order delivery is confirmed. Check the Wallet section for pending and completed withdrawals.',
                'audience' => 'vendor',
            ],
            [
                'question' => 'What commission does the platform charge vendors?',
                'answer' => 'Commission rates are configured by the admin and shown in your vendor dashboard before you confirm each order.',
                'audience' => 'vendor',
            ],
            [
                'question' => 'How do I accept a new service booking?',
                'answer' => 'Open the Bookings tab, review new requests, and tap Accept to confirm the job.',
                'audience' => 'provider',
            ],
            [
                'question' => 'When do service providers receive payouts?',
                'answer' => 'Earnings are added to your wallet after a booking is marked completed. Request a withdrawal from the Earnings section.',
                'audience' => 'provider',
            ],
            [
                'question' => 'How do I update my skills and service area?',
                'answer' => 'Go to Profile and update your skills, experience, address, and service area at any time.',
                'audience' => 'provider',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'audience' => $faq['audience'],
                    'status' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }

        $this->seedProviderServices();
    }

    private function seedProviderServices(): void
    {
        $providers = ServiceProvider::where('status', ProviderStatus::Approved)->get();
        $services = Service::where('status', true)->get();

        if ($providers->isEmpty() || $services->isEmpty()) {
            return;
        }

        $serviceMap = [
            'Arjun Plumber' => ['leak-repair', 'burst-pipe-repair'],
            'Sunil Pipe Expert' => ['leak-repair', 'burst-pipe-repair', 'shower-installation'],
            'Ravi Heater Tech' => ['geyser-installation', 'geyser-repair'],
            'Mohammed Drain Pro' => ['kitchen-drain-cleaning', 'bathroom-drain-cleaning'],
        ];

        foreach ($providers as $provider) {
            $slugs = $serviceMap[$provider->name] ?? $services->random(min(2, $services->count()))->pluck('slug')->all();
            $ids = $services->whereIn('slug', $slugs)->pluck('id');
            $provider->services()->syncWithoutDetaching($ids);
        }

        $user = User::first();
        if (! $user) {
            return;
        }

        $sampleReviews = [
            ['provider' => 'Arjun Plumber', 'rating' => 5, 'comment' => 'Very professional and quick service.'],
            ['provider' => 'Sunil Pipe Expert', 'rating' => 4, 'comment' => 'Good work, arrived on time.'],
            ['provider' => 'Mohammed Drain Pro', 'rating' => 5, 'comment' => 'Excellent drain cleaning service.'],
        ];

        foreach ($sampleReviews as $review) {
            $provider = $providers->firstWhere('name', $review['provider']);
            if (! $provider) {
                continue;
            }

            ServiceProviderReview::firstOrCreate(
                ['user_id' => $user->id, 'service_provider_id' => $provider->id],
                ['rating' => $review['rating'], 'comment' => $review['comment']]
            );
        }
    }
}
