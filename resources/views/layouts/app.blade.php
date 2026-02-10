<style>
    .min-vh-100 {
        min-height: 100vh;
    }

    .custom-card {
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background: #f8f9fa;
        padding: 20px;
        max-width: 100%;
    }

    .custom-card .card-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }

    .custom-card .card-text {
        font-size: 1rem;
        color: #555;
        margin-bottom: 20px;
    }

    .custom-card .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: bold;
    }

    .custom-card .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }
    /* Awal card berada di bawah dan tersembunyi */
    .card {
        opacity: 0;
        transform: translateY(50px);
        transition: all 0.6s ease-out;
    }

    /* Setelah halaman dimuat, card akan muncul ke posisi semula */
    .card.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>
    {{-- @if (!auth()->check())
    
<div class="container mt-5">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card custom-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Akses Ditolak</h5>
                    <p class="card-text">Silakan login terlebih dahulu untuk mengakses halaman ini.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                </div>
            </div>
        </div>
    @else --}}
<!DOCTYPE html>
<html lang="en">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


@stack('scripts')

@include('layouts.head')
<div id="loadingOverlay" class="loading-overlay">
    <div class="three-lines-spinner">
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
    </div>
</div>

<style>
/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* Three Lines Spinner */
.three-lines-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 60px;
    height: 60px;
}

.line {
    width: 8px;
    height: 40px;
    background-color: #2c3e50;
    margin: 0 4px;
    border-radius: 4px;
    animation: line-move 1s ease-in-out infinite;
}

.line:nth-child(1) {
    animation-delay: -0.2s;
}

.line:nth-child(2) {
    animation-delay: -0.4s;
}

.line:nth-child(3) {
    animation-delay: -0.6s;
}

@keyframes line-move {
    0%, 100% {
        transform: scaleY(1);
    }
    50% {
        transform: scaleY(0.5);
    }
}
</style>
<body class="section-with-background" style="background-image: url('{{ asset('gambar/backgrounds.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 400px;">
    
    {{-- <div class="container-fluid"> --}}
        {{-- <div class="row"> --}}
            @include('layouts.sidebar')
            {{-- <main class="col-md-9 ms-sm-auto col-lg-10 px-4"> --}}
                @yield('content')
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            {{-- </main> --}}
        {{-- </div> --}}
    {{-- </div> --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@stack('scripts')
    @include('layouts.footer')
</body>
    <script>
    window.addEventListener("load", function() {
            document.getElementById("loadingOverlay").style.display = "none";
        });
</script>
{{-- @endif --}}
