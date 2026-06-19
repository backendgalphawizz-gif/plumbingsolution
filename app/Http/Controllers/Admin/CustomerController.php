<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $customers = $this->filteredCustomers($request)->paginate(15)->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_blocked', false)->count(),
            'blocked' => User::where('is_blocked', true)->count(),
        ];

        return view('admin.customers.index', compact('customers', 'stats'));
    }

    public function export(Request $request)
    {
        $customers = $this->filteredCustomers($request)->get();

        return $this->exportResponse(
            $request,
            'customers',
            'Customer List',
            ['Name', 'Email', 'Mobile', 'Orders', 'Bookings', 'Status', 'Created Date'],
            $customers->map(fn (User $c) => [
                $c->name,
                $c->email,
                $c->mobile ?? '',
                $c->orders_count,
                $c->service_bookings_count,
                $c->is_blocked ? 'Blocked' : 'Active',
                $c->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredCustomers(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            User::withCount(['orders', 'serviceBookings'])
                ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('mobile', 'like', "%{$s}%");
                }))
                ->when($request->filled('is_blocked'), fn ($q) => $q->where('is_blocked', $request->boolean('is_blocked')))
                ->latest(),
            $request
        );
    }

    public function create(): View
    {
        return view('admin.customers.form', ['customer' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => V::nameRules(),
            'email' => V::emailRules(uniqueTable: 'users'),
            'mobile' => V::mobileRules(),
            'password' => V::passwordRules(),
            'address' => V::addressRules(),
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(User $customer): View
    {
        return view('admin.customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => V::nameRules(),
            'email' => V::emailRules(uniqueTable: 'users', ignoreId: $customer->id),
            'mobile' => V::mobileRules(),
            'password' => V::passwordRules(required: false),
            'address' => V::addressRules(),
        ]);

        $update = collect($data)->except('password')->toArray();
        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $customer->update($update);

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function show(User $customer): View
    {
        $customer->loadCount(['orders', 'serviceBookings']);
        $orders = $customer->orders()->with('items')->latest()->limit(10)->get();
        $bookings = $customer->serviceBookings()->with('serviceProvider')->latest()->limit(10)->get();

        return view('admin.customers.show', compact('customer', 'orders', 'bookings'));
    }

    public function block(Request $request, User $customer): RedirectResponse
    {
        $request->validate(['reason' => V::reasonRules()]);

        $customer->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'block_reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Customer blocked.');
    }

    public function unblock(User $customer): RedirectResponse
    {
        $customer->update(['is_blocked' => false, 'blocked_at' => null, 'block_reason' => null]);

        return back()->with('success', 'Customer unblocked.');
    }
}
