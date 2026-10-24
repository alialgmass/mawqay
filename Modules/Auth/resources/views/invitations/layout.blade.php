<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #faf7f9; color: #1f1a1d; padding: 16px; }
        main { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e6dee2; border-radius: 12px; padding: 24px; }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        label { display: block; margin: .75rem 0 .25rem; font-size: .9rem; }
        input { width: 100%; box-sizing: border-box; padding: .65rem; border: 1px solid #cfc6ca; border-radius: 8px; font-size: 1rem; }
        button { margin-top: 1rem; width: 100%; padding: .75rem; border: 0; border-radius: 8px; background: #7a3e65; color: #fff; font-size: 1rem; cursor: pointer; min-height: 44px; }
        .error { color: #b3261e; font-size: .85rem; margin: .25rem 0 0; }
    </style>
</head>
<body>
    <main>@yield('content')</main>
</body>
</html>
