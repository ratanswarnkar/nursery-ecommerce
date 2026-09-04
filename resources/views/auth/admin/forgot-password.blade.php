<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Nursery Admin</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); width: 100%; max-width: 420px; border: 1px solid #334155; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; color: #38bdf8; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #cbd5e1; }
        input[type="email"] { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #475569; border-radius: 0.375rem; color: #f8fafc; box-sizing: border-box; }
        input:focus { outline: 2px solid #38bdf8; border-color: transparent; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0369a1; }
        .alert { padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; }
        .alert-success { background: #064e3b; color: #6ee7b7; border: 1px solid #065f46; }
        .links { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; }
        .links a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Password Reset Request</h1>
        <p class="subtitle">Enter your administrator email to receive a password reset link.</p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Administrator Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
            </div>

            <button type="submit">Email Password Reset Link</button>
        </form>

        <div class="links">
            <a href="{{ route('admin.login') }}">Back to Admin Login</a>
        </div>
    </div>
</body>
</html>
