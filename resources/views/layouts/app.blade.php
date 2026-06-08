<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Sekolah')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-bg shadow-lg">
            <div class="d-flex flex-column h-100">
                <!-- User Profile Section -->
                <div class="user-section p-4 border-bottom border-white border-opacity-25">
                    <div class="d-flex flex-column align-items-center text-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=ffffff&color=2563eb&size=100&bold=true" alt="User Avatar" class="user-avatar mb-3">
                        <div class="user-info">
                            <h4 class="fw-bold text-white">{{ Auth::user()->name }}</h4>
                            <p class="text-white-50 small mt-1 d-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-grow-1 py-3">
                    <ul class="list-unstyled">
                        <li class="mb-1">
                            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-home me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">HOME</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('dashboard.siswa') }}" class="sidebar-link {{ request()->routeIs('dashboard.siswa') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-users me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">DATA SISWA</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('dashboard.keuangan') }}" class="sidebar-link {{ request()->routeIs('dashboard.keuangan') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-money-bill-wave me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">KEUANGAN</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('dashboard.informasi') }}" class="sidebar-link {{ request()->routeIs('dashboard.informasi') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-info-circle me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">INFORMASI SEKOLAH</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Container with Navbar and Content -->
        <div id="main-container" class="flex-grow-1">
            <!-- Top Navbar -->
            <nav id="navbar-container" class="navbar-bg shadow-lg">
                <div class="d-flex align-items-center justify-content-between px-3 px-lg-4 py-2">
                    <div class="d-flex align-items-center">
                        <button id="toggle-sidebar-btn" class="btn btn-link text-white p-2 me-2">
                            <i class="fas fa-bars fs-5"></i>
                        </button>
                        <h1 class="navbar-title text-white fs-6 fs-sm-5 fs-lg-4 fw-bold mb-0 text-truncate">SMK BIT BINA AULIA</h1>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Notification Bell -->
                        <x-notification-bell :unread-count="0" />
                        
                        <div class="position-relative">
                            <button id="user-menu-btn" class="btn btn-link text-white p-2">
                                <i class="fas fa-user-circle fs-3"></i>
                            </button>
                            <!-- User Dropdown -->
                            <div id="user-dropdown" class="user-dropdown">
                                <a href="{{ route('password.edit') }}" class="dropdown-item-custom" style="text-decoration: none;">
                                    <i class="fas fa-key text-secondary"></i>
                                    <span class="fw-medium small">Ganti Password</span>
                                </a>
                                <a href="{{ route('admin.forgot-password.index') }}" class="dropdown-item-custom" style="text-decoration: none;">
                                    <i class="fas fa-unlock-alt text-secondary"></i>
                                    <span class="fw-medium small">Permohonan Reset</span>
                                </a>
                                <div class="dropdown-item-custom">
                                    <i class="fas fa-envelope text-secondary"></i>
                                    <span class="fw-medium small text-break">{{ Auth::user()->email }}</span>
                                </div>
                                @if(Auth::user()->role === 'kepala_sekolah')
                                    <a href="{{ route('admin.users.create') }}" class="dropdown-item-custom">
                                        <i class="fas fa-user-plus text-secondary"></i>
                                        <span class="fw-medium small">Daftar Admin/Kepala Sekolah</span>
                                    </a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item-custom logout w-100 border-0 bg-transparent text-start">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span class="fw-medium small">Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Overlay for mobile -->
            <div id="sidebar-overlay" class="sidebar-overlay"></div>

            <!-- Main Content -->
            <main class="main-content bg-light">
                <!-- Content -->
                <div class="p-3 p-lg-4">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('main-container');
        const navbarContainer = document.getElementById('navbar-container');
        const overlay = document.getElementById('sidebar-overlay');
        
        // Deteksi ukuran layar
        const isMobile = () => window.innerWidth < 992;
        
        // Load sidebar state dari localStorage (hanya untuk desktop)
        let sidebarCollapsed = !isMobile() && localStorage.getItem('sidebarCollapsed') === 'true';
        
        // Apply state saat page load (hanya untuk desktop)
        if (!isMobile() && sidebarCollapsed) {
            sidebar.classList.add('sidebar-collapsed');
            mainContainer.classList.add('sidebar-collapsed');
            navbarContainer.classList.add('sidebar-collapsed');
        }

        toggleSidebarBtn?.addEventListener('click', () => {
            if (isMobile()) {
                // Mobile: toggle sidebar dengan overlay
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                // Desktop: toggle collapse sidebar (mini sidebar)
                sidebarCollapsed = !sidebarCollapsed;
                
                // Simpan state ke localStorage
                localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
                
                if (sidebarCollapsed) {
                    // Collapse sidebar - hanya tampilkan icon
                    sidebar.classList.add('sidebar-collapsed');
                    mainContainer.classList.add('sidebar-collapsed');
                    navbarContainer.classList.add('sidebar-collapsed');
                } else {
                    // Expand sidebar - tampilkan full
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContainer.classList.remove('sidebar-collapsed');
                    navbarContainer.classList.remove('sidebar-collapsed');
                }
            }
        });

        // Close sidebar when clicking overlay (mobile only)
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // User dropdown menu
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');

        userMenuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuBtn?.contains(e.target) && !userDropdown?.contains(e.target)) {
                userDropdown?.classList.remove('show');
            }
        });

        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        updateTime();
        setInterval(updateTime, 1000);
    </script>
    
    <!-- Toast Notifications for Real-time Events -->
    <x-notification-toast :user-id="Auth::id()" :user-role="Auth::user()?->role" />
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>
