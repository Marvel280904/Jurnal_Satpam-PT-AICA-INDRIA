<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Jurnal Satpam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <img src="{{ asset('images/bg_login.jpg') }}" alt="Background" class="bg-img">
        <div class="login-box">
            <h2>Login</h2>
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="input-group-custom">
                    <i class="bi bi-person-fill icon-left"></i>
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </div>

                <div class="input-group-custom">
                    <i class="bi bi-lock-fill icon-left"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>

                <button type="submit">Submit</button>

                @error('username')
                    <span class="error">{{ $message }}</span>
                @enderror
            </form>
        </div>
    </div>
</body>
</html>
