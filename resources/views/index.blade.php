<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include head content -->
    <!-- You can use server-side includes, templating engines, or manual insertion -->
    <!-- For example, using PHP include -->
    <?php include 'head.html'; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <br>
        <img src="https://reformasibirokrasi.kejaksaan.go.id/assets/media/svg/logos/logo-rb-64.svg" alt="Profile Picture" class="profile-pic">
        <h4 class="text-center text-black">Selamat Datang<br>Nama Satker<br>ID Satker</h4>
        <a href="#" id="toggle-submenu"><i class="fas fa-tasks"></i> <span class="sidebar-text">Tata Kelola AKIP</span>
            <i class="fas fa-chevron-right arrow-icon"></i>
        </a>
        <div class="submenu" id="submenu">
            <a href="dashboard.html"><i class="fas fa-users"></i> Kep Tim SAKIP</a>
            <!-- Other submenu items -->
        </div>
        <!-- Other sidebar links -->
    </div>

    <!-- Sidebar Toggler Button -->
    <button class="btn btn-primary toggler-btn" id="toggler-btn">
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light" id="navbar">
        <div class="container-fluid">
            <span class="navbar-brand"><br><br>Halaman Saat Ini</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Satker 123</span>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="container-fluid">
            <!-- Content will be loaded dynamically -->
            <div id="dynamic-content">
                <!-- Default content or loading indication -->
            </div>
        </div>
    </div>

    <!-- Include footer content -->
    <?php include 'footer.html'; ?>
</body>
</html>
