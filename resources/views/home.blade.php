<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Dashboard</title>
    <!-- Include your CSS file -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .login-button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background-color: #007bff; /* Bootstrap Primary Button Color */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .login-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container text-center" style="margin-top: 100px;">
        <h1>Welcome to the Dashboard</h1>
        <p>Please login to access the full features.</p>

        <!-- Login Button -->
        {{-- <a href="{{ route('login') }}" class="login-button">Login</a> --}}
    </div>

    <!-- Include your JavaScript file -->
    {{-- <script src="{{ asset('js/app.js') }}"></script> --}}
</body>
</html>

