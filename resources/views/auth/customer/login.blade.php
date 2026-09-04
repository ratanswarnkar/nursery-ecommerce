<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Nursery E-Commerce</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 100%; max-width: 400px; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; text-align: center; color: #166534; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        input[type="text"] { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; box-sizing: border-box; }
        button { width: 100%; padding: 0.75rem; background: #16a34a; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #15803d; }
        .alert { padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Customer Login</h1>

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

        @if(session('otp_requested'))
            <form method="POST" action="{{ route('customer.otp.verify') }}">
                @csrf
                <input type="hidden" name="phone" value="{{ session('phone') }}">
                <div class="form-group">
                    <label for="otp">Enter 6-Digit Verification Code</label>
                    <input type="text" id="otp" name="otp" placeholder="123456" maxlength="6" required autofocus>
                </div>
                <button type="submit">Verify & Login</button>
            </form>
            <div style="margin-top: 1rem; text-align: center;">
                <form method="POST" action="{{ route('customer.otp.request') }}">
                    @csrf
                    <input type="hidden" name="phone" value="{{ session('phone') }}">
                    <button type="submit" style="background: transparent; color: #16a34a; border: none; padding: 0; font-size: 0.875rem; cursor: pointer;">Resend Code</button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('customer.otp.request') }}">
                @csrf
                <div class="form-group">
                    <label for="phone">Mobile Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="+91 9876543210" value="{{ old('phone') }}" required autofocus>
                </div>
                <button type="submit">Request OTP</button>
            </form>
        @endif
    </div>
</body>
</html>
