<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Readify Kids — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f6ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .logo-icon { width: 56px; height: 56px; background: #185FA5; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
        .btn-primary { background: #185FA5; border: none; }
        .btn-primary:hover { background: #0f4a8a; }
    </style>
</head>
<body>
<div class="container" style="max-width: 420px;">
    <div class="text-center mb-4">
        <div class="logo-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <h4 class="fw-bold">Readify Kids</h4>
        <p class="text-muted small">Gamified Reading Assistance System</p>
    </div>

    <div class="card p-4">
        <h5 class="fw-bold mb-1">Welcome back!</h5>
        <p class="text-muted small mb-3">Sign in to your account</p>

        @if($errors->any())
            <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" value="{{ old('username') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>

       
    </div>
</div>
</body>
</html>