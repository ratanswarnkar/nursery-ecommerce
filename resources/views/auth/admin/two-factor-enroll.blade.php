<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Two-Factor Authentication - Nursery Admin</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem 1rem; box-sizing: border-box; }
        .card { background: #1e293b; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); width: 100%; max-width: 480px; border: 1px solid #334155; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; color: #38bdf8; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.4; }
        .qr-container { background: #ffffff; padding: 1rem; border-radius: 0.5rem; display: flex; justify-content: center; align-items: center; margin: 0 auto 1.5rem auto; width: 200px; height: 200px; }
        .manual-secret { background: #0f172a; border: 1px dashed #475569; padding: 0.75rem; border-radius: 0.375rem; text-align: center; font-family: monospace; font-size: 1rem; color: #38bdf8; letter-spacing: 0.1rem; margin-bottom: 1.5rem; word-break: break-all; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #cbd5e1; }
        input[type="text"] { width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #475569; border-radius: 0.375rem; color: #f8fafc; box-sizing: border-box; text-align: center; font-size: 1.25rem; letter-spacing: 0.25rem; }
        input:focus { outline: 2px solid #38bdf8; border-color: transparent; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0369a1; }
        .alert-error { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Set Up Two-Factor Authentication</h1>
        <p class="subtitle">Two-factor authentication is mandatory for all administrators. Scan the QR code below using Google Authenticator, Microsoft Authenticator, or Authy.</p>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="qr-container">
            {!! $qrSvg !!}
        </div>

        <p style="font-size: 0.8125rem; color: #94a3b8; text-align: center; margin-bottom: 0.5rem;">Or enter this setup key manually:</p>
        <div class="manual-secret">{{ $secret }}</div>

        <form method="POST" action="{{ route('admin.2fa.enroll.confirm') }}">
            @csrf
            <div class="form-group">
                <label for="code">Enter 6-Digit Code from Authenticator to Confirm</label>
                <input type="text" id="code" name="code" maxlength="6" required autofocus placeholder="••••••" autocomplete="one-time-code">
            </div>
            <button type="submit">Confirm & Complete Enrollment</button>
        </form>
    </div>
</body>
</html>
