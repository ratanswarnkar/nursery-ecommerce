<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Nursery Admin</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); width: 100%; max-width: 420px; border: 1px solid #334155; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; color: #38bdf8; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #cbd5e1; }
        input[type="email"], input[type="password"] { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #475569; border-radius: 0.375rem; color: #f8fafc; box-sizing: border-box; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0369a1; }
        .alert-error { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Set New Password</h1>
        <p class="subtitle">Enter your new administrator password.</p>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Administrator Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••">
            </div>

            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
