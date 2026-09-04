<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - Nursery Admin</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); width: 100%; max-width: 420px; border: 1px solid #334155; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; color: #38bdf8; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #cbd5e1; }
        input[type="text"] { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #475569; border-radius: 0.375rem; color: #f8fafc; box-sizing: border-box; text-align: center; font-size: 1.25rem; letter-spacing: 0.25rem; }
        input:focus { outline: 2px solid #38bdf8; border-color: transparent; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0369a1; }
        .alert { padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; }
        .toggle-section { margin-top: 1.5rem; text-align: center; }
        .toggle-btn { background: transparent; border: none; color: #38bdf8; font-size: 0.875rem; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Two-Factor Verification</h1>
        <p class="subtitle">Enter the code from your authenticator app</p>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div id="totp-form">
            <form method="POST" action="{{ route('admin.2fa.verify') }}">
                @csrf
                <div class="form-group">
                    <label for="code">6-Digit Authenticator Code</label>
                    <input type="text" id="code" name="code" maxlength="6" autofocus placeholder="••••••" autocomplete="one-time-code">
                </div>
                <button type="submit">Verify & Access Portal</button>
            </form>
            <div class="toggle-section">
                <button class="toggle-btn" onclick="document.getElementById('totp-form').style.display='none'; document.getElementById('recovery-form').style.display='block';">Use an Emergency Recovery Code</button>
            </div>
        </div>

        <div id="recovery-form" style="display: none;">
            <form method="POST" action="{{ route('admin.2fa.verify') }}">
                @csrf
                <div class="form-group">
                    <label for="recovery_code">Single-Use Recovery Code</label>
                    <input type="text" id="recovery_code" name="recovery_code" maxlength="25" placeholder="XXXXXXXX-XXXXXXXX" style="letter-spacing: 0.1rem; font-size: 1rem;">
                </div>
                <button type="submit">Consume Recovery Code & Login</button>
            </form>
            <div class="toggle-section">
                <button class="toggle-btn" onclick="document.getElementById('recovery-form').style.display='none'; document.getElementById('totp-form').style.display='block';">Back to Authenticator App</button>
            </div>
        </div>
    </div>
</body>
</html>
