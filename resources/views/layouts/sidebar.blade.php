<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <br>
    @php
        $satkernama = session('satkernama', 'Nama Satker');
        $idSatker = session('id_satker', 'ID Satker');
        $levelSakip = session('id_sakip_level', 0);
        $canUseScopedSatkerPages = in_array((int) $levelSakip, [99, 2, 1, 0], true);
        $adminSatkerIds = ['admin', '999999', 'menpanrb', 'Pengawasan', 'Panev'];
        $isAdmin = (int) $levelSakip === 99 || in_array((string) $idSatker, $adminSatkerIds, true);

        // Cek apakah submenu harus dibuka
        $submenuActive =
            request()->is('kep') ||
            request()->is('perencanaan') ||
            request()->is('pengukuran') ||
            request()->is('pelaporan') ||
            request()->is('evaluasi') ||
            request()->is('dataLke');
        $kewilayahanActive = request()->is('sakipwil') || request()->is('was-lke');
        $administratorActive =
            request()->is('admin/dashboard-analitik') ||
            request()->is('keloladata') ||
            request()->is('diagnostik') ||
            request()->is('monitoring') ||
            request()->is('admin/download-dokumen');
    @endphp
    <div id="user-info">
        <img src="{{ asset('gambar/kejaksaan.png') }}" alt="Profile Picture" class="profile-pic">
        <h5 class="text-center text-dark">Selamat Datang<br>{{ $satkernama }}<br>ID Satker: {{ $idSatker }}</h5>
    </div>

    <a href="{{ route('dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
        <i class="fas fa-home"></i> <span class="sidebar-text">Beranda</span>
    </a>

   @if ($levelSakip == 99 || $levelSakip == 1 || $levelSakip == 2 || $levelSakip == 3 || $levelSakip == 4)
        <a href="#" id="toggle-submenu" class="toggle-btn {{ $submenuActive ? 'active' : '' }}">
            <i class="fas fa-tasks"></i> <span class="sidebar-text">Tata Kelola AKIP</span>
            <i class="fas fa-chevron-right arrow-icon"></i>
        </a>

        <div class="submenu" id="submenu" style="display: {{ $submenuActive ? 'block' : 'none' }};">

            <a href="{{ route('perencanaan') }}" class="{{ request()->is('perencanaan') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Perencanaan
            </a>
            {{-- @if ($levelSakip == 99) --}}
          <!----  @if ($tahun != 2024)---->
                <a href="{{ route('pengukuran') }}" class="{{ request()->is('pengukuran') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Pengukuran
                </a>
           <!---- @endif---->
            {{-- @endif --}}
            @if ($levelSakip == 99 || $levelSakip == 1 || $levelSakip == 2 || $levelSakip == 3 || $levelSakip == 4)
                <a href="{{ route('pelaporan') }}" class="{{ request()->is('pelaporan') ? 'active' : '' }}">
                    <i class="fas fa-file-upload"></i> Pelaporan
                </a>
            @endif
            {{-- @if ($levelSakip == 99) --}}
            <a href="{{ route('evaluasi') }}" class="{{ request()->is('evaluasi') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i> Evaluasi
            </a>
            {{-- @endif --}}
        </div>
    @endif

    @if ($canUseScopedSatkerPages)
        <a href="#" id="toggle-kewilayahan" class="toggle-btn {{ $kewilayahanActive ? 'active' : '' }}">
            <i class="fas fa-globe"></i> <span class="sidebar-text">Kewilayahan</span>
            <i class="fas fa-chevron-right arrow-icon"></i>
        </a>

        <div class="submenu" id="submenu-kewilayahan" style="display: {{ $kewilayahanActive ? 'block' : 'none' }};">
            <a href="{{ route('sakipwil') }}" class="{{ request()->is('sakipwil') ? 'active' : '' }}">
                <i class="fas fa-globe"></i> SAKIP Wilayah
            </a>
            <a href="{{ route('lke_was') }}" class="{{ request()->is('was-lke') ? 'active' : '' }}">
                <i class="far fa-eye"></i> Evaluasi Wilayah
            </a>
        </div>
    @endif

    @if ($isAdmin)
        <a href="#" id="toggle-administrator" class="toggle-btn {{ $administratorActive ? 'active' : '' }}">
            <i class="fas fa-user-shield"></i> <span class="sidebar-text">Administrator</span>
            <i class="fas fa-chevron-right arrow-icon"></i>
        </a>

        <div class="submenu" id="submenu-administrator" style="display: {{ $administratorActive ? 'block' : 'none' }};">
            <a href="{{ route('admin.dashboard-analytic') }}" class="{{ request()->is('admin/dashboard-analitik') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Dashboard Analitik
            </a>
            <a href="{{ route('keloladata') }}" class="{{ request()->is('keloladata') ? 'active' : '' }}">
                <i class="fas fa-database"></i> Kelola Data
            </a>
            <a href="{{ route('diagnostik') }}" class="{{ request()->is('diagnostik') ? 'active' : '' }}">
                <i class="fas fa-search"></i> Pengecekan Sistem
            </a>
            <a href="{{ route('monitoring') }}" class="{{ request()->is('monitoring') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Monitoring
            </a>
            <a href="{{ route('admin.download.index') }}" class="{{ request()->is('admin/download-dokumen') ? 'active' : '' }}">
                <i class="fas fa-download"></i> Download Arsip Wilayah
            </a>
        </div>
    @endif
    @php
        use Illuminate\Support\Str;
    @endphp
    @if ($levelSakip == 99 || $levelSakip == 1|| $levelSakip == 2 || $levelSakip == 3 || $levelSakip == 4  || Str::startsWith($idSatker, 'Pengawasan')  || Str::startsWith($idSatker, 'menpanrb') || Str::startsWith($idSatker, 'Panev'))
        <a href="{{ route('aturan') }}" class="{{ request()->is('aturan') ? 'active' : '' }}">
            <i class="fas fa-gavel"></i> <span class="sidebar-text">Sumber Aturan</span>
        </a>
        <a href="{{ route('faq') }}" class="{{ request()->is('faq') ? 'active' : '' }}">
            <i class="fas fa-question-circle"></i> <span class="sidebar-text">FAQ</span>
        </a>
        @if ($levelSakip == 99)
        <a href="{{ route('ubahpassword') }}" class="{{ request()->is('ubahpassword') ? 'active' : '' }}">
            <i class="fas fa-key"></i> <span class="sidebar-text">Ubah Password</span>
        </a>
        @endif
    @endif

</div>


<!-- Sidebar Toggler Button -->
{{-- <button class="btn btn-yellow toggler-btn" id="toggler-btn">
    <i class="fas fa-chevron-left"></i>
</button> --}}

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light" id="navbar"
    style="background-color: #ffffff; border-bottom: 1px solid #e0e0e0;">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-dark">
                        <form action="{{ route('pilih.tahun') }}" method="POST" id="tahunForm"
                            class="d-flex align-items-center">
                            @csrf
                            <label for="tahun" class="me-2 mb-0">{{ $satkernama }} Tahun: </label>

                            <select name="tahun" id="tahun" class="form-select w-auto"
                                onchange="document.getElementById('tahunForm').submit();">
                                @for ($i = 2024; $i <= date('Y'); $i++)
                                    <option value="{{ $i }}" {{ $i == $tahun ? 'selected' : '' }}>
                                        {{ $i }}</option>
                                @endfor
                            </select>
                        </form>
                    </span>
                </li>
                <li class="nav-item">
                    <span class="nav-link text-dark">
                        <form action="{{ url('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">Kembali</button>
                        </form>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    $(document).ready(function () {
        $('#toggle-submenu').on('click', function (e) {
            e.preventDefault();
            $('#submenu').slideToggle();
            $(this).find('.arrow-icon').toggleClass('fa-chevron-right fa-chevron-down');
        });

        $('#toggle-kewilayahan').on('click', function (e) {
            e.preventDefault();
            $('#submenu-kewilayahan').slideToggle();
            $(this).find('.arrow-icon').toggleClass('fa-chevron-right fa-chevron-down');
        });

        $('#toggle-administrator').on('click', function (e) {
            e.preventDefault();
            $('#submenu-administrator').slideToggle();
            $(this).find('.arrow-icon').toggleClass('fa-chevron-right fa-chevron-down');
        });

        $('#toggler-btn').on('click', function () {
            $('.sidebar').toggleClass('collapsed');
            $('.navbar').toggleClass('collapsed');
            $('#user-info').toggle();
        });
    });
</script>

<style>
    /* Sidebar Style */
    .sidebar {
        background-color: #ffffff;
        color: #343a40;
        height: 100vh;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        transition: width 0.3s ease;
        width: 250px;
        /* Lebar sidebar normal */
        position: fixed;
        /* Sidebar tetap di sisi kiri */
        z-index: 1000;
        /* Pastikan di atas konten lain */
        overflow-y: auto;
        /* Scroll jika konten lebih dari tinggi */
    }

    .sidebar a {
        display: flex;
        align-items: center;
        padding: 10px;
        color: #343a40;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .sidebar a:hover {
        background-color: #e2df8b;
        /* Warna lebih terang saat hover */
    }

    .sidebar a.active {
        background-color: #e6bf3e;
        /* Warna untuk link aktif */
        color: #ffffff;
        /* Teks putih untuk link aktif */
    }

    .submenu {
        transition: max-height 0.3s ease, opacity 0.3s ease;
        overflow: hidden;
    }

    .submenu a {
        padding-left: 30px;
        background-color: #f8f9fa;
        /* Warna submenu */
    }

    .submenu a:hover {
        background-color: #e2df8b;
        /* Warna lebih terang saat hover pada submenu */
    }

    .arrow-icon {
        margin-left: auto;
        transition: transform 0.3s;
    }

    /* Media Queries untuk Responsivitas */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            position: relative;
            /* Ubah posisi sidebar di perangkat kecil */
            height: auto;
            /* Tinggi otomatis */
        }

        .sidebar a {
            justify-content: space-between;
            /* Sesuaikan tampilan link */
        }

        .navbar {
            margin-left: 0;
            /* Menghilangkan margin di navbar */
        }

        .toggle-btn {
            display: block;
            /* Tampilkan tombol toggle di perangkat kecil */
        }
    }

    .toggler-btn {
        position: fixed;
        left: 250px;
        /* Posisi di samping sidebar */
        top: 10px;
        z-index: 1100;
        transition: left 0.3s ease;
    }

    /* Styling untuk tombol toggler */
    .btn-yellow {
        background-color: #ffc107;
        /* Sesuaikan dengan warna tema */
        color: #ffffff;
    }

    /* Mengatur navbar di samping sidebar */
    .navbar {
        margin-left: 250px;
        transition: margin-left 0.3s ease;
    }

    .collapsed+.navbar {
        margin-left: 80px;
    }

    .footer {
        margin-top: 20px;
    }
</style>
