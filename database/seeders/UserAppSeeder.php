<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Service;
use App\Models\ServiceCategory;
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

        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_amount' => 100,
                'status' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'FLAT50'],
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
            ],
            [
                'question' => 'Can I cancel my order or booking?',
                'answer' => 'Yes. Orders in processing can be cancelled from Order History. Bookings can be cancelled or rescheduled from Booking History.',
            ],
            [
                'question' => 'What payment methods are supported?',
                'answer' => 'We support Razorpay online payments and Cash on Delivery for product orders.',
            ],
            [
                'question' => 'How long does delivery take?',
                'answer' => 'Standard product delivery typically takes 2–5 business days depending on your location.',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                ['answer' => $faq['answer'], 'status' => true, 'sort_order' => $i + 1]
            );
        }
    }
}
