<header class="admin-header">
    <div class="admin-header-title">
        @yield('header_title', 'Management Portal')
    </div>

    <div class="admin-header-actions">
        <!-- Disabled Storefront Indicator (strictly placeholder, no live link) -->
        <span class="badge badge-neutral" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Customer storefront is scheduled for Phase 5">
            Storefront (Coming in Phase 5)
        </span>

        @auth('admin')
            <div style="display: flex; align-items: center; gap: 0.75rem; border-left: 1px solid var(--border-color); padding-left: 1rem;">
                <div style="text-align: right;">
                    <div style="font-size: 0.875rem; font-weight: 600;">{{ auth('admin')->user()->name }}</div>
                    <div style="font-size: 0.75rem; color: var(--primary-light);">
                        {{ auth('admin')->user()->roles->pluck('name')->first() ?? 'Administrator' }}
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" title="Log out">
                        <svg class="nav-icon" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        @endauth
    </div>
</header>
