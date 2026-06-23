<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $customers = $this->filteredCustomers($request)->paginate(15)->withQueryString();

        $stats = [
            'total' => User::where('role', UserRole::Customer)->count(),
            'active' => User::where('role', UserRole::Customer)->where('is_blocked', false)->count(),
            'blocked' => User::where('role', UserRole::Customer)->where('is_blocked', true)->count(),
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
            User::where('role', UserRole::Customer)
                ->withCount(['orders', 'serviceBookings'])
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
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::emailRules(required: false, uniqueTable: 'users'),
            'password' => V::passwordRules(),
            'address' => V::addressRules(),
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = collect($data)->except(['avatar', 'password'])->filter(fn ($v) => $v !== null && $v !== '')->toArray();
        $payload['password'] = Hash::make($data['password']);
        $payload['role'] = UserRole::Customer;

        if ($request->hasFile('avatar')) {
            $payload['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        User::create($payload);

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(User $customer): View
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        return view('admin.customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $data = $request->validate([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), [Rule::unique('users', 'mobile')->ignore($customer)]),
            'email' => V::emailRules(required: false, uniqueTable: 'users', ignoreId: $customer->id),
            'password' => V::passwordRules(required: false),
            'address' => V::addressRules(),
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $update = collect($data)->except(['password', 'avatar'])->toArray();

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($customer->avatar) {
                Storage::disk('public')->delete($customer->avatar);
            }
            $update['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $customer->update($update);

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function show(User $customer): View
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->loadCount(['orders', 'serviceBookings', 'bulkOrders']);
        $orders = $customer->orders()->latest()->limit(10)->get();
        $bookings = $customer->serviceBookings()->with('serviceProvider')->latest()->limit(10)->get();

        return view('admin.customers.show', compact('customer', 'orders', 'bookings'));
    }

    public function block(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

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
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->update(['is_blocked' => false, 'blocked_at' => null, 'block_reason' => null]);

        return back()->with('success', 'Customer unblocked.');
    }
}
