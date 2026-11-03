<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود | hamareCRM</title>
    <style>
        body { font-family: Tahoma, sans-serif; background:#f4f6f8; margin:0; display:flex; min-height:100vh; align-items:center; justify-content:center; }
        .card { background:#fff; padding:1.5rem; border-radius:12px; width:min(100%, 380px); box-shadow:0 8px 24px rgba(0,0,0,.08); }
        label { display:block; margin-bottom:.35rem; font-size:.9rem; }
        input { width:100%; padding:.65rem .75rem; margin-bottom:1rem; border:1px solid #d0d7de; border-radius:8px; box-sizing:border-box; }
        button { width:100%; padding:.75rem; border:0; border-radius:8px; background:#0f766e; color:#fff; font-weight:bold; }
        .error { color:#b91c1c; font-size:.85rem; margin-bottom:1rem; }
        h1 { font-size:1.15rem; margin:0 0 1rem; text-align:center; }
    </style>
</head>
<body>
<div class="card">
    <h1>ورود به hamareCRM</h1>
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ url('/login') }}">
        @csrf
        <label for="username">نام کاربری</label>
        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required>
        <label for="password">رمز عبور</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>
        <label style="display:flex;gap:.5rem;align-items:center;margin-bottom:1rem;">
            <input type="checkbox" name="remember" value="1" style="width:auto;margin:0;">
            مرا به خاطر بسپار
        </label>
        <button type="submit">ورود</button>
    </form>
</div>
</body>
</html>
