<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginActivity;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityAuditController extends Controller
{
    /**
     * Display a listing of system audit logs.
     */
    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with(['admin', 'customer'])->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $logs = $query->paginate(20)->withQueryString();
        $distinctActions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.security.audit_logs', compact('logs', 'distinctActions'));
    }

    /**
     * Display administrator login activity history.
     */
    public function loginActivity(Request $request): View
    {
        $query = AdminLoginActivity::with('admin')->latest('login_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('admin', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_successful', (bool) $request->query('status'));
        }

        $activities = $query->paginate(20)->withQueryString();

        return view('admin.security.login_activity', compact('activities'));
    }

    /**
     * Display operational settings, store constants, and system configurations.
     */
    public function settings(): View
    {
        $storeSettings = [
            'store_name' => 'SUGANDHA FARMS AND NURSERY',
            'registered_address' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082',
            'support_phone' => '098111 14365',
            'country' => 'India',
            'currency' => 'INR (₹)',
            'shipping_zone' => 'Delhi NCR Exclusively (Delhi, Gurugram, Noida, Faridabad, Ghaziabad)',
            'shipping_timeline' => 'Standard Delivery within 3 business days',
            'free_shipping_threshold' => 'Orders strictly greater than ₹1,000 (₹1,000.01+)',
            'payment_gateways' => [
                'Razorpay' => 'Enabled (Standard Cards, UPI, NetBanking)',
                'Cash on Delivery' => 'Enabled (Delhi NCR delivery zone only)',
            ],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
        ];

        return view('admin.security.settings', compact('storeSettings'));
    }
}
