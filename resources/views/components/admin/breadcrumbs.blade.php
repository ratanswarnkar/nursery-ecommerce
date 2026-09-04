@hasSection('breadcrumbs')
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        @yield('breadcrumbs')
    </nav>
@endif
