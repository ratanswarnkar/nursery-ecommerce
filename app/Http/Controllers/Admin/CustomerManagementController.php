<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerManagementController extends Controller
{
    /**
     * Display a listing of registered storefront customers.
     */
    public function index(Request $request): View
    {
        $query = Customer::withCount('orders')
            ->withSum('orders', 'grand_total')
            ->latest();

        // Search by name, email, or phone
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active') && $request->query('is_active') !== '') {
            $query->where('is_active', (bool) $request->query('is_active'));
        }

        $metrics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'inactive_customers' => Customer::where('is_active', false)->count(),
        ];

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers', 'metrics'));
    }

    /**
     * Display detailed profile, addresses, and order history for a customer.
     */
    public function show(Customer $customer): View
    {
        $customer->load([
            'addresses',
            'orders' => function ($q) {
                $q->latest()->withCount('items');
            },
        ]);

        $orderStats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $customer->orders()->sum('grand_total'),
            'last_order' => $customer->orders()->latest()->first(),
        ];

        return view('admin.customers.show', compact('customer', 'orderStats'));
    }

    /**
     * Toggle a customer account active / suspended state.
     */
    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $customer->update([
            'is_active' => ! $customer->is_active,
            // Bump auth token version if deactivating so any existing session/token is invalidated
            'auth_token_version' => $customer->is_active ? $customer->auth_token_version + 1 : $customer->auth_token_version,
        ]);

        $statusText = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer account {$customer->name} has been {$statusText}.");
    }
}
