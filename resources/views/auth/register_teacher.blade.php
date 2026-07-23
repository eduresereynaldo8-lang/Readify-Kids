<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Readify Kids — Teacher Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f6ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 30px 0; }
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .btn-primary { background: #185FA5; border: none; }
        .btn-primary:hover { background: #0f4a8a; }
    </style>
</head>
<body>
<div class="container" style="max-width: 480px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold">Readify Kids</h4>
        <p class="text-muted small">Create your teacher account</p>
    </div>

    <div class="card p-4">
        <h5 class="fw-bold mb-1">Teacher Registration</h5>
        <p class="text-muted small mb-3">Fill in your details to get started</p>

        @if($errors->any())
            <div class="alert alert-danger small py-2">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">First Name</label>
                    <input type="text" name="firstname" class="form-control" placeholder="First name" value="{{ old('firstname') }}" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">Last Name</label>
                    <input type="text" name="lastname" class="form-control" placeholder="Last name" value="{{ old('lastname') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">School Name</label>
                <input type="text" name="school_name" class="form-control" placeholder="e.g. Sagana Elementary School" value="{{ old('school_name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" value="{{ old('username') }}" required>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>

        <p class="text-center small mt-3 mb-0">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </p>
    </div>
</div>
</body>
</html>