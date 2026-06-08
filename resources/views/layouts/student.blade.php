<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Siswa')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}">
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-bg shadow-lg">
            <div class="d-flex flex-column h-100">
                <!-- User Profile Section -->
                <div class="user-section p-4 border-bottom border-white border-opacity-25">
                    <div class="d-flex flex-column align-items-center text-center">
                        @if($student ?? null)
                            @if($student->photo_path)
                                <img src="{{ asset('storage/' . $student->photo_path) }}" alt="Foto Siswa" class="user-avatar mb-3" style="object-fit: cover;">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=ffffff&color=2563eb&size=100&bold=true" alt="User Avatar" class="user-avatar mb-3">
                            @endif
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=ffffff&color=2563eb&size=100&bold=true" alt="User Avatar" class="user-avatar mb-3">
                        @endif
                        <div class="user-info">
                            <h4 class="fw-bold text-white">{{ Auth::user()->name }}</h4>
                            <p class="text-white-50 small mt-1 d-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Siswa
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-grow-1 py-3">
                    <ul class="list-unstyled">
                        <li class="mb-1">
                            <a href="{{ route('student.dashboard') }}" class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-home me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">HOME</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('student.profile') }}" class="sidebar-link {{ request()->routeIs('student.profile') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-user me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">DATA PRIBADI</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('student.keuangan') }}" class="sidebar-link {{ request()->routeIs('student.keuangan') ? 'sidebar-active' : '' }}">
                                <i class="fas fa-money-bill-wave me-3 fs-5"></i>
                                <span class="sidebar-text fw-medium small">KEUANGAN</span>
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
                        <h1 class="text-white fs-5 fs-lg-4 fw-bold mb-0">SMK BIT BINA AULIA</h1>
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
                                <a href="{{ route('student.password.edit') }}" class="dropdown-item-custom" style="text-decoration: none;">
                                    <i class="fas fa-key text-secondary"></i>
                                    <span class="fw-medium small">Ganti Password</span>
                                </a>
                                <div class="dropdown-item-custom">
                                    <i class="fas fa-envelope text-secondary"></i>
                                    <span class="fw-medium small">{{ Auth::user()->email }}</span>
                                </div>
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

            <!-- Main Content Area -->
            <div id="main-content" class="main-content p-3 p-lg-4">
                <!-- Page Header -->
                <div class="mb-3 mb-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                        <div class="w-100 w-md-auto">
                            <h2 class="fw-bold text-dark mb-1" style="font-size: clamp(1.25rem, 4vw, 1.75rem);">@yield('page-title')</h2>
                        </div>
                        <div class="mt-2 mt-md-0">
                            @yield('page-actions')
                        </div>
                    </div>
                </div>

                <!-- Content -->
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    
    <!-- Toast Notifications for Real-time Events -->
    <x-notification-toast :user-id="Auth::id()" :user-role="Auth::user()?->role" />
    
    @stack('scripts')
    @yield('scripts')
</body>
</html>
