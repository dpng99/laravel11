<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Include Bootstrap CSS -->
    <link rel="icon" href="{{ asset('gambar/kejaksaan.png') }}" type="image/png">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa; /* Light background for elegance */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('{{ asset('gambar/background.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .login-container {
            max-width: 400px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .login-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
        }
        .login-button {
            background-color: #007bff;
            color: #fff;
            border-radius: 25px;
            font-weight: bold;
            padding: 10px;
            width: 100%;
        }
        .login-button:hover {
            background-color: #0056b3;
        }
        .forgot-password {
            font-size: 14px;
            text-align: center;
            display: block;
            margin-top: 10px;
        }
        .forgot-password a {
            color: #007bff;
        }
        /* Custom Button Styles */
.btn-yellow {
    background-color: #f0bb49; /* Warna kuning */
    color: #fff; /* Warna teks putih */
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-yellow:hover {
    background-color: #e0a842; /* Warna kuning yang sedikit lebih gelap untuk efek hover */
}

.btn-yellow:focus {
    outline: none;
    box-shadow: 0 0 5px rgba(240, 187, 73, 0.8);
}
    </style>
</head>
<body>

    <div class="login-container">
        <h2 class="login-title">E-SAKIP <br>KEJAKSAAN AGUNG REPUBLIK INDONESIA</h2>
        <center><img src="{{ asset('gambar/kejaksaan.png') }}" alt="kejaksaan" class="login-pic"></center>
<br>
        <!-- Display Validation Errors -->
        @if ($errors->any())
            <div class="alert alert-danger">
                
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Kode Satker</label>
                <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-yellow login-button">Login</button>
        </form>

        <br>
        <center><p>Panev BiroCana Kejaksaan RI @2025</p></center>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
</body>
</html>
