@extends('layouts.admin')

@section('title', 'System & Operational Settings')
@section('header_title', 'Settings')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>System</span>
    <span class="breadcrumbs-sep">/</span>
    <span>Settings Overview</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Store & Operational Settings</h1>
        <p class="page-subtitle">Centralized operational configuration, approved commercial shipping rules, and platform parameters.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">
    <!-- Commercial Rules Card -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0;">Approved Commercial Rules</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.875rem;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Delivery Coverage Zone</div>
                <div style="font-size: 0.9375rem; font-weight: 600; color: #0f172a; margin-top: 0.125rem;">
                    {{ $storeSettings['shipping_zone'] }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Fulfillment Timeline</div>
                <div style="font-size: 0.9375rem; font-weight: 600; color: #0f172a; margin-top: 0.125rem;">
                    {{ $storeSettings['shipping_timeline'] }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Free Shipping Threshold</div>
                <div style="font-size: 0.9375rem; font-weight: 600; color: #059669; margin-top: 0.125rem;">
                    {{ $storeSettings['free_shipping_threshold'] }}
                </div>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                    Note: Orders ≤ ₹1,000 do not qualify for free delivery.
                </div>
            </div>
        </div>
    </div>

    <!-- Verified Business Profile -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #0284c7;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0;">Verified Business Details</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.875rem;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Store Name</div>
                <div style="font-size: 0.9375rem; font-weight: 600; color: #0f172a; margin-top: 0.125rem;">
                    {{ $storeSettings['store_name'] }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Headquarters Address</div>
                <div style="font-size: 0.875rem; color: #334155; margin-top: 0.125rem;">
                    {{ $storeSettings['registered_address'] }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Customer Care Line</div>
                <div style="font-size: 0.9375rem; font-family: monospace; font-weight: 600; color: #0f172a; margin-top: 0.125rem;">
                    {{ $storeSettings['support_phone'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Gateway Integration -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #8b5cf6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0;">Payment Integrations</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.875rem;">
            @foreach($storeSettings['payment_gateways'] as $gateway => $status)
                <div style="padding: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                    <div style="font-weight: 600; font-size: 0.875rem; color: #0f172a;">{{ $gateway }}</div>
                    <div style="font-size: 0.75rem; color: #059669; font-weight: 500; margin-top: 0.125rem;">{{ $status }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Technical Platform Environment -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0;">Platform Environment</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.875rem;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Environment Mode</div>
                <div style="font-size: 0.875rem; font-family: monospace; font-weight: 600; color: #0284c7; margin-top: 0.125rem;">
                    {{ strtoupper($storeSettings['environment']) }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">PHP Engine Version</div>
                <div style="font-size: 0.875rem; font-family: monospace; color: #0f172a; margin-top: 0.125rem;">
                    v{{ $storeSettings['php_version'] }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Laravel Framework Version</div>
                <div style="font-size: 0.875rem; font-family: monospace; color: #0f172a; margin-top: 0.125rem;">
                    v{{ $storeSettings['laravel_version'] }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
