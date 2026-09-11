<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Sugandha Farms and Nursery</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest-900: #1b382b;
            --forest-800: #264735;
            --forest-700: #2e5941;
            --forest-50: #f2f7f4;
            --terracotta-500: #cc5a36;
            --terracotta-600: #bc4726;
            --stone-900: #0f172a;
            --stone-600: #475569;
            --stone-400: #94a3b8;
            --stone-200: #e2e8f0;
            --stone-100: #f1f5f9;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: var(--stone-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
        }
        .error-card {
            background: #ffffff;
            border: 1px solid var(--stone-200);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            max-width: 32rem;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background-color: var(--forest-50);
            color: var(--forest-800);
            font-size: 0.8125rem;
            font-weight: 700;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(46, 89, 65, 0.2);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .icon-wrap {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1rem;
            background-color: var(--forest-50);
            color: var(--forest-700);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .icon-wrap svg {
            width: 2.5rem;
            height: 2.5rem;
        }
        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.75rem;
            color: var(--stone-900);
            margin-bottom: 0.75rem;
            line-height: 1.25;
        }
        p {
            font-size: 0.9375rem;
            color: var(--stone-600);
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }
        .btn-primary {
            background-color: var(--forest-800);
            color: #ffffff;
            border: 1px solid var(--forest-800);
        }
        .btn-primary:hover {
            background-color: var(--forest-900);
        }
        .btn-secondary {
            background-color: #ffffff;
            color: var(--stone-600);
            border: 1px solid var(--stone-200);
        }
        .btn-secondary:hover {
            background-color: var(--stone-100);
            color: var(--stone-900);
        }
        .brand-footer {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--stone-400);
        }
    </style>
</head>
<body>
    <div class="error-card">
        @yield('content')
    </div>
    <div class="brand-footer">
        Sugandha Farms and Nursery &bull; Delhi NCR Botanical Care
    </div>
</body>
</html>
