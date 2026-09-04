<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Portal') - Nursery E-Commerce</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <!-- Sidebar Navigation -->
    @include('components.admin.sidebar')

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Header -->
        @include('components.admin.header')

        <!-- Content Body -->
        <main class="admin-content">
            @include('components.admin.breadcrumbs')
            @include('components.admin.flash-messages')

            @yield('content')
        </main>
    </div>

    <!-- Confirm Modal Helper -->
    <div id="confirm-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 50; align-items: center; justify-content: center;">
        <div class="card" style="max-width: 440px; width: 90%; margin: auto;">
            <div class="card-title" id="confirm-modal-title" style="margin-bottom: 0.75rem;">Confirm Action</div>
            <p id="confirm-modal-message" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Are you sure you want to proceed?</p>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <form id="confirm-modal-form" method="POST" style="display: inline;">
                    @csrf
                    <div id="confirm-modal-method"></div>
                    <button type="submit" id="confirm-modal-btn" class="btn btn-danger">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openConfirmModal(actionUrl, message, title = 'Confirm Action', isDelete = true) {
            document.getElementById('confirm-modal-form').action = actionUrl;
            document.getElementById('confirm-modal-title').innerText = title;
            document.getElementById('confirm-modal-message').innerText = message;

            const methodContainer = document.getElementById('confirm-modal-method');
            if (isDelete) {
                methodContainer.innerHTML = '<input type="hidden" name="_method" value="DELETE">';
                document.getElementById('confirm-modal-btn').className = 'btn btn-danger';
                document.getElementById('confirm-modal-btn').innerText = 'Delete';
            } else {
                methodContainer.innerHTML = '';
                document.getElementById('confirm-modal-btn').className = 'btn btn-primary';
                document.getElementById('confirm-modal-btn').innerText = 'Proceed';
            }

            document.getElementById('confirm-modal').style.display = 'flex';
        }

        function closeConfirmModal() {
            document.getElementById('confirm-modal').style.display = 'none';
        }
    </script>
    @stack('scripts')
</body>
</html>
