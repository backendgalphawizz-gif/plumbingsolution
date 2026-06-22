<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Enums\TicketStatus;
use App\Enums\VendorStatus;
use App\Models\AppNotification;
use App\Models\Banner;
use App\Models\BookingLog;
use App\Models\BulkOrder;
use App\Models\BulkOrderFile;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\Subcategory;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\ProviderDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Category::exists()) {
            return;
        }

        $adminId = 1;

        // ── Categories & Subcategories ──────────────────────────────
        $catalog = [
            'Plumbing Tools' => ['Pipe Wrenches', 'Cutting Tools', 'Measuring Tools'],
            'Pipes & Fittings' => ['PVC Pipes', 'Copper Pipes', 'Fittings & Connectors'],
            'Fixtures' => ['Faucets & Taps', 'Showers', 'Toilets & Seats'],
            'Water Heaters' => ['Electric Heaters', 'Gas Heaters', 'Solar Heaters'],
            'Drainage' => ['Drain Cleaners', 'Sewage Pumps', 'Gutters'],
        ];

        $categories = collect();
        $subcategories = collect();

        foreach ($catalog as $catName => $subs) {
            $category = Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
                'status' => true,
                'sort_order' => $categories->count(),
            ]);
            $categories->push($category);

            foreach ($subs as $i => $subName) {
                $subcategories->push(Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $subName,
                    'slug' => Str::slug($subName),
                    'status' => true,
                    'sort_order' => $i,
                ]));
            }
        }

        // ── Customers ───────────────────────────────────────────────
        $customers = collect([
            ['name' => 'Rahul Sharma', 'email' => 'rahul@gmail.com', 'mobile' => '9876500001'],
            ['name' => 'Priya Patel', 'email' => 'priya@gmail.com', 'mobile' => '9876500002'],
            ['name' => 'Amit Kumar', 'email' => 'amit@gmail.com', 'mobile' => '9876500003'],
            ['name' => 'Sneha Reddy', 'email' => 'sneha@gmail.com', 'mobile' => '9876500004'],
            ['name' => 'Vikram Singh', 'email' => 'vikram@gmail.com', 'mobile' => '9876500005'],
            ['name' => 'Anita Desai', 'email' => 'anita@gmail.com', 'mobile' => '9876500006'],
            ['name' => 'Karan Mehta', 'email' => 'karan@gmail.com', 'mobile' => '9876500007'],
            ['name' => 'Deepa Nair', 'email' => 'deepa@gmail.com', 'mobile' => '9876500008'],
            ['name' => 'Rohit Gupta', 'email' => 'rohit@gmail.com', 'mobile' => '9876500009'],
            ['name' => 'Meera Joshi', 'email' => 'meera@gmail.com', 'mobile' => '9876500010'],
            ['name' => 'Blocked User', 'email' => 'blocked@gmail.com', 'mobile' => '9876500011', 'is_blocked' => true],
        ])->map(function ($data) {
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'role' => UserRole::Customer,
                'password' => Hash::make('password'),
                'address' => fake()->address(),
                'is_blocked' => $data['is_blocked'] ?? false,
                'blocked_at' => isset($data['is_blocked']) ? now()->subDays(3) : null,
                'block_reason' => isset($data['is_blocked']) ? 'Policy violation' : null,
            ]);
        });

        // ── Vendors ─────────────────────────────────────────────────
        $vendorData = [
            ['shop_name' => 'AquaFlow Supplies', 'owner_name' => 'Rajesh Verma', 'mobile' => '9123400001', 'status' => VendorStatus::Approved, 'gst' => '27AABCA1234A1Z5'],
            ['shop_name' => 'PipeMaster Store', 'owner_name' => 'Suresh Iyer', 'mobile' => '9123400002', 'status' => VendorStatus::Approved, 'gst' => '29AABCP5678B2Z6'],
            ['shop_name' => 'FixIt Plumbing Mart', 'owner_name' => 'Manoj Pillai', 'mobile' => '9123400003', 'status' => VendorStatus::Pending, 'gst' => '32AABCF9012C3Z7'],
            ['shop_name' => 'HydroTech Solutions', 'owner_name' => 'Arun Nambiar', 'mobile' => '9123400004', 'status' => VendorStatus::Pending, 'gst' => '33AABCH3456D4Z8'],
            ['shop_name' => 'OldTown Hardware', 'owner_name' => 'Gopal Das', 'mobile' => '9123400005', 'status' => VendorStatus::Suspended, 'gst' => '19AABCO7890E5Z9'],
        ];

        $vendors = collect($vendorData)->map(fn ($v) => Vendor::create([
            'shop_name' => $v['shop_name'],
            'owner_name' => $v['owner_name'],
            'mobile' => $v['mobile'],
            'address' => fake()->streetAddress().', '.fake()->city(),
            'gst_number' => $v['gst'],
            'status' => $v['status'],
            'approved_at' => $v['status'] === VendorStatus::Approved ? now()->subMonths(2) : null,
        ]));

        foreach ($vendors as $vendor) {
            VendorDocument::create([
                'vendor_id' => $vendor->id,
                'document_type' => 'GST Certificate',
                'file_path' => 'documents/vendors/gst-'.$vendor->id.'.pdf',
                'is_verified' => $vendor->status === VendorStatus::Approved,
                'verified_at' => $vendor->status === VendorStatus::Approved ? now()->subMonth() : null,
                'verified_by' => $vendor->status === VendorStatus::Approved ? $adminId : null,
            ]);
            VendorDocument::create([
                'vendor_id' => $vendor->id,
                'document_type' => 'Shop License',
                'file_path' => 'documents/vendors/license-'.$vendor->id.'.pdf',
                'is_verified' => $vendor->status === VendorStatus::Approved,
            ]);
        }

        // ── Service Providers ───────────────────────────────────────
        $providerData = [
            ['name' => 'Arjun Plumber', 'mobile' => '9988700001', 'skills' => ['Leak Repair', 'Pipe Fitting'], 'years' => 8, 'status' => ProviderStatus::Approved],
            ['name' => 'Sunil Pipe Expert', 'mobile' => '9988700002', 'skills' => ['Industrial', 'Residential'], 'years' => 12, 'status' => ProviderStatus::Approved],
            ['name' => 'Ravi Heater Tech', 'mobile' => '9988700003', 'skills' => ['Heater Installation', 'Gas Fitting'], 'years' => 6, 'status' => ProviderStatus::Approved],
            ['name' => 'Mohammed Drain Pro', 'mobile' => '9988700004', 'skills' => ['Drain Cleaning', 'Sewage'], 'years' => 10, 'status' => ProviderStatus::Approved],
            ['name' => 'Kiran Bathroom Fix', 'mobile' => '9988700005', 'skills' => ['Fixture Install', 'Tile Work'], 'years' => 5, 'status' => ProviderStatus::Pending],
            ['name' => 'Naveen Emergency', 'mobile' => '9988700006', 'skills' => ['Emergency Leak Repair'], 'years' => 7, 'status' => ProviderStatus::Pending],
            ['name' => 'Prakash Water Pro', 'mobile' => '9988700007', 'skills' => ['Pump Install', 'Borewell'], 'years' => 9, 'status' => ProviderStatus::Pending],
            ['name' => 'Dinesh Rejected', 'mobile' => '9988700008', 'skills' => ['General Plumbing'], 'years' => 2, 'status' => ProviderStatus::Rejected],
        ];

        $providers = collect($providerData)->map(fn ($p) => ServiceProvider::create([
            'name' => $p['name'],
            'mobile' => $p['mobile'],
            'skills' => $p['skills'],
            'experience_years' => $p['years'],
            'service_area' => fake()->city().', '.fake()->state(),
            'status' => $p['status'],
            'approved_at' => $p['status'] === ProviderStatus::Approved ? now()->subMonths(1) : null,
            'rejection_reason' => $p['status'] === ProviderStatus::Rejected ? 'Incomplete documentation' : null,
        ]));

        foreach ($providers->where('status', ProviderStatus::Pending) as $provider) {
            ProviderDocument::create([
                'service_provider_id' => $provider->id,
                'document_type' => 'ID Proof',
                'file_path' => 'documents/providers/id-'.$provider->id.'.pdf',
                'is_verified' => false,
            ]);
        }

        // ── Products ────────────────────────────────────────────────
        $approvedVendors = $vendors->where('status', VendorStatus::Approved);

        $productList = [
            ['name' => 'Heavy Duty Pipe Wrench 14"', 'cat' => 'Plumbing Tools', 'sub' => 'Pipe Wrenches', 'price' => 1299, 'sale' => 1099, 'stock' => 45],
            ['name' => 'Adjustable Basin Wrench', 'cat' => 'Plumbing Tools', 'sub' => 'Pipe Wrenches', 'price' => 599, 'sale' => null, 'stock' => 80],
            ['name' => 'PVC Pipe Cutter Pro', 'cat' => 'Plumbing Tools', 'sub' => 'Cutting Tools', 'price' => 849, 'sale' => 749, 'stock' => 30],
            ['name' => 'Digital Water Pressure Gauge', 'cat' => 'Plumbing Tools', 'sub' => 'Measuring Tools', 'price' => 2199, 'sale' => null, 'stock' => 15],
            ['name' => 'PVC Pipe 1 inch (3m)', 'cat' => 'Pipes & Fittings', 'sub' => 'PVC Pipes', 'price' => 189, 'sale' => null, 'stock' => 500],
            ['name' => 'PVC Pipe 2 inch (3m)', 'cat' => 'Pipes & Fittings', 'sub' => 'PVC Pipes', 'price' => 349, 'sale' => 299, 'stock' => 320],
            ['name' => 'Copper Pipe 15mm (2m)', 'cat' => 'Pipes & Fittings', 'sub' => 'Copper Pipes', 'price' => 899, 'sale' => null, 'stock' => 60],
            ['name' => 'Elbow Connector 1 inch', 'cat' => 'Pipes & Fittings', 'sub' => 'Fittings & Connectors', 'price' => 45, 'sale' => null, 'stock' => 1000],
            ['name' => 'T-Connector PVC 2 inch', 'cat' => 'Pipes & Fittings', 'sub' => 'Fittings & Connectors', 'price' => 89, 'sale' => 75, 'stock' => 800],
            ['name' => 'Chrome Kitchen Faucet', 'cat' => 'Fixtures', 'sub' => 'Faucets & Taps', 'price' => 3499, 'sale' => 2999, 'stock' => 25],
            ['name' => 'Wall Mixer Tap Set', 'cat' => 'Fixtures', 'sub' => 'Faucets & Taps', 'price' => 4299, 'sale' => null, 'stock' => 18],
            ['name' => 'Rain Shower Head Deluxe', 'cat' => 'Fixtures', 'sub' => 'Showers', 'price' => 1899, 'sale' => 1599, 'stock' => 40],
            ['name' => 'Dual Flush Toilet Seat', 'cat' => 'Fixtures', 'sub' => 'Toilets & Seats', 'price' => 5999, 'sale' => null, 'stock' => 12],
            ['name' => '25L Electric Water Heater', 'cat' => 'Water Heaters', 'sub' => 'Electric Heaters', 'price' => 8999, 'sale' => 7999, 'stock' => 20],
            ['name' => '50L Electric Water Heater', 'cat' => 'Water Heaters', 'sub' => 'Electric Heaters', 'price' => 12999, 'sale' => null, 'stock' => 10],
            ['name' => '6L Gas Water Heater', 'cat' => 'Water Heaters', 'sub' => 'Gas Heaters', 'price' => 7499, 'sale' => 6999, 'stock' => 15],
            ['name' => '100L Solar Water Heater', 'cat' => 'Water Heaters', 'sub' => 'Solar Heaters', 'price' => 24999, 'sale' => null, 'stock' => 5],
            ['name' => 'Drain Cleaner Liquid 1L', 'cat' => 'Drainage', 'sub' => 'Drain Cleaners', 'price' => 249, 'sale' => 199, 'stock' => 200],
            ['name' => 'Submersible Sewage Pump 1HP', 'cat' => 'Drainage', 'sub' => 'Sewage Pumps', 'price' => 8999, 'sale' => null, 'stock' => 8],
            ['name' => 'Gutter Guard Mesh 10ft', 'cat' => 'Drainage', 'sub' => 'Gutters', 'price' => 599, 'sale' => null, 'stock' => 50],
        ];

        $products = collect();
        $skuCounter = 1000;

        foreach ($productList as $item) {
            $category = $categories->firstWhere('name', $item['cat']);
            $subcategory = $subcategories->first(fn ($s) => $s->name === $item['sub'] && $s->category_id === $category->id);
            $vendor = $approvedVendors->random();

            $products->push(Product::create([
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'vendor_id' => $vendor->id,
                'product_name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'description' => 'High quality '.$item['name'].' for professional and home plumbing use.',
                'price' => $item['price'],
                'sale_price' => $item['sale'],
                'stock' => $item['stock'],
                'sku' => 'PM-'.($skuCounter++),
                'status' => true,
            ]));
        }

        // ── Orders ──────────────────────────────────────────────────
        $orderStatuses = [
            OrderStatus::Pending,
            OrderStatus::Accepted,
            OrderStatus::Packed,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Delivered,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ];

        $orders = collect();
        for ($i = 1; $i <= 25; $i++) {
            $customer = $customers->random();
            $status = $orderStatuses[array_rand($orderStatuses)];
            $orderProducts = $products->random(rand(1, 3));
            $subtotal = 0;

            $order = Order::create([
                'order_number' => 'ORD-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'user_id' => $customer->id,
                'vendor_id' => $orderProducts->first()->vendor_id,
                'status' => $status,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 99,
                'discount_amount' => 0,
                'total_amount' => 0,
                'shipping_address' => $customer->address ?? fake()->address(),
                'billing_address' => $customer->address ?? fake()->address(),
                'cancelled_at' => $status === OrderStatus::Cancelled ? now()->subDays(rand(1, 5)) : null,
                'cancellation_reason' => $status === OrderStatus::Cancelled ? 'Customer requested cancellation' : null,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            foreach ($orderProducts as $product) {
                $qty = rand(1, 3);
                $unitPrice = $product->sale_price ?? $product->price;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);
            }

            $tax = round($subtotal * 0.18, 2);
            $total = $subtotal + $tax + 99;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => OrderStatus::Pending->value,
                'notes' => 'Order placed',
                'created_at' => $order->created_at,
            ]);

            if ($status !== OrderStatus::Pending) {
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $status->value,
                    'notes' => 'Status updated',
                    'changed_by' => $adminId,
                    'created_at' => $order->created_at->addHours(rand(1, 48)),
                ]);
            }

            $orders->push($order);
        }

        // ── Service Bookings ────────────────────────────────────────
        $bookingStatuses = [
            BookingStatus::Pending,
            BookingStatus::Assigned,
            BookingStatus::Accepted,
            BookingStatus::Started,
            BookingStatus::Completed,
            BookingStatus::Completed,
            BookingStatus::Cancelled,
        ];

        $services = [
            'Emergency Leak Repair',
            'Heater Installation',
            'Bathroom Pipe Fitting',
            'Drain Cleaning',
            'Water Pump Installation',
            'Toilet Repair',
            'Kitchen Sink Unclogging',
            'Gas Geyser Servicing',
        ];

        $approvedProviders = $providers->where('status', ProviderStatus::Approved);

        for ($i = 1; $i <= 18; $i++) {
            $status = $bookingStatuses[array_rand($bookingStatuses)];
            $customer = $customers->random();
            $provider = in_array($status, [BookingStatus::Pending], true)
                ? null
                : $approvedProviders->random();

            $booking = ServiceBooking::create([
                'booking_number' => 'BK-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'user_id' => $customer->id,
                'service_provider_id' => $provider?->id,
                'service_name' => $services[array_rand($services)],
                'description' => 'Customer requested service at home.',
                'address' => $customer->address ?? fake()->address(),
                'scheduled_at' => now()->addDays(rand(-10, 14)),
                'status' => $status,
                'amount' => rand(500, 5000),
                'completed_at' => $status === BookingStatus::Completed ? now()->subDays(rand(1, 7)) : null,
                'created_at' => now()->subDays(rand(0, 20)),
            ]);

            BookingLog::create([
                'service_booking_id' => $booking->id,
                'status' => BookingStatus::Pending->value,
                'notes' => 'Booking created',
                'created_at' => $booking->created_at,
            ]);

            if ($status !== BookingStatus::Pending) {
                BookingLog::create([
                    'service_booking_id' => $booking->id,
                    'status' => $status->value,
                    'notes' => 'Status updated by admin',
                    'changed_by' => $adminId,
                ]);
            }
        }

        // ── Bulk Orders ─────────────────────────────────────────────
        $bulkStatuses = [
            'requirement_submitted',
            'admin_review',
            'quotation_generated',
            'quotation_sent',
            'customer_approved',
        ];

        foreach ($bulkStatuses as $idx => $bulkStatus) {
            $bulkOrder = BulkOrder::create([
                'reference_number' => 'BULK-'.str_pad((string) ($idx + 1), 5, '0', STR_PAD_LEFT),
                'user_id' => $customers->random()->id,
                'requirement_description' => 'Bulk plumbing materials required for construction project phase '.($idx + 1).'.',
                'status' => $bulkStatus,
                'admin_notes' => $bulkStatus !== 'requirement_submitted' ? 'Under review by procurement team.' : null,
                'created_at' => now()->subDays(10 - $idx),
            ]);

            BulkOrderFile::create([
                'bulk_order_id' => $bulkOrder->id,
                'file_path' => 'bulk-orders/requirement-'.$bulkOrder->id.'.pdf',
                'file_type' => 'pdf',
                'original_name' => 'requirements.pdf',
            ]);

            if (in_array($bulkStatus, ['quotation_generated', 'quotation_sent', 'customer_approved'], true)) {
                Quotation::create([
                    'bulk_order_id' => $bulkOrder->id,
                    'quotation_number' => 'QT-'.Str::upper(Str::random(6)),
                    'amount' => rand(50000, 200000),
                    'details' => 'Quotation for bulk plumbing supplies including pipes, fittings, and fixtures.',
                    'status' => $bulkStatus === 'quotation_generated' ? 'draft' : 'sent',
                    'created_by' => $adminId,
                    'sent_at' => in_array($bulkStatus, ['quotation_sent', 'customer_approved'], true) ? now()->subDays(2) : null,
                ]);
            }
        }

        // ── Payments ────────────────────────────────────────────────
        $methods = [PaymentMethod::Razorpay, PaymentMethod::PhonePe, PaymentMethod::Cod];
        $payCounter = 1;

        foreach ($orders->whereIn('status', [OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Packed]) as $order) {
            Payment::create([
                'payment_id' => 'PAY-'.str_pad((string) $payCounter++, 8, '0', STR_PAD_LEFT),
                'user_id' => $order->user_id,
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'method' => $methods[array_rand($methods)],
                'status' => PaymentStatus::Completed,
                'amount' => $order->total_amount,
                'currency' => 'INR',
                'gateway_payment_id' => 'gw_'.Str::random(14),
                'created_at' => $order->created_at->addMinutes(5),
            ]);
        }

        foreach ($orders->where('status', OrderStatus::Pending)->take(3) as $order) {
            Payment::create([
                'payment_id' => 'PAY-'.str_pad((string) $payCounter++, 8, '0', STR_PAD_LEFT),
                'user_id' => $order->user_id,
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'method' => PaymentMethod::Razorpay,
                'status' => PaymentStatus::Pending,
                'amount' => $order->total_amount,
                'currency' => 'INR',
            ]);
        }

        Payment::all()->each(function (Payment $payment) {
            if ($payment->status === PaymentStatus::Completed) {
                Transaction::create([
                    'payment_id' => $payment->id,
                    'transaction_id' => 'TXN-'.Str::upper(Str::random(10)),
                    'type' => 'payment',
                    'amount' => $payment->amount,
                    'status' => 'completed',
                    'description' => 'Order payment received',
                ]);
            }
        });

        // ── Banners ─────────────────────────────────────────────────
        Banner::insert([
            [
                'title' => 'Summer Plumbing Sale',
                'image' => 'banners/summer-sale.jpg',
                'redirect_type' => 'category',
                'redirect_id' => $categories->first()->id,
                'redirect_url' => null,
                'status' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'New Water Heaters',
                'image' => 'banners/water-heaters.jpg',
                'redirect_type' => 'category',
                'redirect_id' => $categories->firstWhere('name', 'Water Heaters')->id,
                'redirect_url' => null,
                'status' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Book a Plumber',
                'image' => 'banners/book-plumber.jpg',
                'redirect_type' => 'url',
                'redirect_id' => null,
                'redirect_url' => '/book-service',
                'status' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ── Notifications ───────────────────────────────────────────
        AppNotification::create([
            'title' => 'Welcome to PlumbManager',
            'message' => 'Your one-stop shop for plumbing products and services.',
            'type' => NotificationType::System,
            'sent_at' => now()->subDays(7),
            'sent_by' => $adminId,
        ]);

        AppNotification::create([
            'title' => 'Summer Sale Live!',
            'message' => 'Get up to 30% off on selected plumbing tools and fixtures.',
            'type' => NotificationType::Promotion,
            'sent_at' => now()->subDays(2),
            'sent_by' => $adminId,
        ]);

        // ── Support Tickets ─────────────────────────────────────────
        $ticketStatuses = [TicketStatus::Open, TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed];

        for ($i = 1; $i <= 6; $i++) {
            $customer = $customers->random();
            $status = $ticketStatuses[min($i - 1, 3)];

            $ticket = Ticket::create([
                'ticket_number' => 'TKT-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'user_id' => $customer->id,
                'subject' => ['Order not delivered', 'Wrong product received', 'Refund request', 'Booking reschedule', 'Payment failed', 'Account blocked'][($i - 1) % 6],
                'status' => $status,
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'assigned_to' => in_array($status, [TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed], true) ? $adminId : null,
                'created_at' => now()->subDays(rand(1, 14)),
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => User::class,
                'sender_id' => $customer->id,
                'message' => 'I need help with my issue. Please assist.',
                'created_at' => $ticket->created_at,
            ]);

            if ($status !== TicketStatus::Open) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'sender_type' => \App\Models\Admin::class,
                    'sender_id' => $adminId,
                    'message' => 'Thank you for contacting us. We are looking into your request.',
                    'created_at' => $ticket->created_at->addHours(2),
                ]);
            }
        }
    }
}
