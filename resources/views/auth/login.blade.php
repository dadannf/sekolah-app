<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - SMK BIT BINA AULIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4169E1 0%, #1E3A8A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-wrapper {
            background: white;
            border-radius: 30px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 550px;
            position: relative;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .row-wrapper {
            display: flex;
            min-height: 550px;
        }

        /* Left Side - Form */
        .form-container {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: fadeInLeft 0.8s ease-out;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-container h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .social-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .social-btn {
            width: 45px;
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            color: #666;
        }

        .social-btn:hover {
            background: #4169E1;
            color: white;
            border-color: #4169E1;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(65, 105, 225, 0.3);
        }

        .divider {
            text-align: center;
            color: #999;
            font-size: 0.85rem;
            margin: 20px 0;
        }

        .form-input {
            background: #f0f0f0;
            border: none;
            padding: 14px 20px;
            margin: 8px 0;
            width: 100%;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            background: #e8e8e8;
            outline: none;
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.1);
        }

        .forgot-link {
            color: #4169E1;
            font-size: 0.85rem;
            text-decoration: none;
            display: block;
            text-align: center;
            margin: 15px 0;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #1E3A8A;
        }

        .btn-signin {
            background: linear-gradient(135deg, #4169E1 0%, #1E3A8A 100%);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 45px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(65, 105, 225, 0.4);
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(65, 105, 225, 0.5);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        /* Right Side - Overlay Panel */
        .overlay-container {
            flex: 1;
            background: linear-gradient(135deg, #4169E1 0%, #1E3A8A 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInRight 0.8s ease-out;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .overlay-container::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .overlay-content {
            position: relative;
            z-index: 1;
        }

        .overlay-container h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .overlay-container p {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .btn-signup {
            background: transparent;
            border: 2px solid white;
            color: white;
            border-radius: 25px;
            padding: 12px 45px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-signup:hover {
            background: white;
            color: #4169E1;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 255, 255, 0.3);
        }

        .alert-custom {
            background: #fee;
            color: #c33;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert-success-custom {
            background: #efe;
            color: #3c3;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Demo Info */
        .demo-info {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #666;
            border-left: 4px solid #4169E1;
        }

        .demo-info strong {
            color: #4169E1;
        }

        .demo-info code {
            background: #e8e8e8;
            padding: 2px 6px;
            border-radius: 4px;
            color: #333;
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #4169E1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .overlay-container {
                display: none;
            }

            .form-container {
                padding: 40px 30px;
            }

            .container-wrapper {
                border-radius: 20px;
            }

            .form-container h1 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .form-container {
                padding: 30px 20px;
            }

            .social-btn {
                width: 40px;
                height: 40px;
            }

            .btn-signin {
                padding: 10px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="row-wrapper">
            <!-- Left Side - Sign In Form -->
            <div class="form-container">
                <h1>Sign In</h1>

                <div class="divider">Masuk dengan akun Anda</div>

                @if(session('success'))
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    
                    <input 
                        type="text" 
                        class="form-input" 
                        name="email" 
                        placeholder="NIS / Email" 
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                    <small style="display: block; margin-top: -8px; margin-bottom: 12px; color: #6c757d; font-size: 0.75rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Admin:</strong> Gunakan email | <strong>Siswa:</strong> Gunakan NIS (contoh: 22211161)
                    </small>
                    
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            class="form-input" 
                            id="password-input"
                            name="password" 
                            placeholder="Password" 
                            required
                        >
                        <i class="fas fa-eye password-toggle" id="toggle-password" title="Show password"></i>
                    </div>

                    <button type="submit" class="btn-signin w-100">Sign In</button>

                    @if(!empty($canBootstrapAdmin))
                        <a href="{{ route('admin.register') }}" class="forgot-link" style="margin-top: 16px;">
                            <i class="fas fa-user-plus me-1"></i>
                            Registrasi Admin/Kepala Sekolah
                        </a>
                    @endif

                    <a href="{{ route('password.request') }}" class="forgot-link" style="margin-top: 10px;">
                        Lupa Password?
                    </a>

                </form>
            </div>

            <!-- Right Side - Overlay Panel -->
            <div class="overlay-container">
                <div class="overlay-content">
                    <div class="mb-4">
                        <i class="fas fa-graduation-cap" style="font-size: 5rem; opacity: 0.9;"></i>
                    </div>
                    <h1>Selamat Datang di MYBBA!</h1>
                    <p>Sistem Informasi Manajemen Sekolah<br>SMK BIT BINA AULIA</p>

                    @if(!empty($canBootstrapAdmin))
                        <a href="{{ route('admin.register') }}" class="btn-signup" style="text-decoration: none; display: inline-block;">
                            Daftar Admin
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password-input');

        togglePassword.addEventListener('click', function() {
            // Toggle input type
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            
            // Update title
            this.setAttribute('title', type === 'password' ? 'Show password' : 'Hide password');
        });
    </script>
</body>
</html>
