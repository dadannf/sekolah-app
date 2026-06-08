<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin - SMK BIT BINA AULIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

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
        }

        .row-wrapper { display: flex; min-height: 550px; }

        .form-container {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

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
        }

        .form-container h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-input {
            background: #f0f0f0;
            border: none;
            padding: 14px 20px;
            margin: 8px 0;
            width: 100%;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-input:focus {
            background: #e8e8e8;
            outline: none;
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.1);
        }

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
            user-select: none;
        }

        .password-toggle:hover {
            color: #4169E1;
        }

        .btn-primary-custom {
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
            width: 100%;
        }

        .btn-primary-custom:hover { transform: translateY(-2px); }

        .btn-link-custom {
            color: #4169E1;
            font-size: 0.85rem;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 14px;
        }

        .alert-custom {
            background: #fee;
            color: #c33;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .alert-success-custom {
            background: #efe;
            color: #3c3;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .overlay-container { display: none; }
            .form-container { padding: 40px 30px; }
            .container-wrapper { border-radius: 20px; }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="row-wrapper">
            <div class="form-container">
                <h1>Registrasi Admin</h1>

                @if(session('success'))
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('admin.register.store') }}" method="POST" autocomplete="off">
                    @csrf

                    <input
                        type="text"
                        class="form-input"
                        name="registration_key"
                        placeholder="Registration Key"
                        value="{{ old('registration_key') }}"
                        required
                    >

                    <input
                        type="text"
                        class="form-input"
                        name="name"
                        placeholder="Nama lengkap"
                        value="{{ old('name') }}"
                        required
                    >

                    <input
                        type="email"
                        class="form-input"
                        name="email"
                        placeholder="Email"
                        value="{{ old('email') }}"
                        required
                    >

                    <select name="role" class="form-input" required>
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih role</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="kepala_sekolah" {{ old('role') === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                    </select>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            class="form-input"
                            id="password-input"
                            name="password"
                            placeholder="Password (min 8 karakter)"
                            required
                        >
                        <i class="fas fa-eye password-toggle" id="toggle-password" title="Show password"></i>
                    </div>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            class="form-input"
                            id="password-confirmation-input"
                            name="password_confirmation"
                            placeholder="Konfirmasi password"
                            required
                        >
                        <i class="fas fa-eye password-toggle" id="toggle-password-confirmation" title="Show password"></i>
                    </div>

                    <button type="submit" class="btn-primary-custom">Buat Akun</button>
                    <a href="{{ route('login') }}" class="btn-link-custom">Kembali ke Login</a>
                </form>
            </div>

            <div class="overlay-container">
                <div class="mb-4">
                    <i class="fas fa-user-shield" style="font-size: 5rem; opacity: 0.9;"></i>
                </div>
                <h1 style="font-size: 2.3rem; font-weight: 700; margin-bottom: 16px;">Akses Khusus</h1>
                <p style="opacity: 0.9; line-height: 1.6;">
                    Halaman ini hanya untuk bootstrap Admin/Kepala Sekolah pertama.
                    Setelah ada admin, registrasi akan ditutup.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function wirePasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
                this.setAttribute('title', type === 'password' ? 'Show password' : 'Hide password');
            });
        }

        wirePasswordToggle('toggle-password', 'password-input');
        wirePasswordToggle('toggle-password-confirmation', 'password-confirmation-input');
    </script>
</body>
</html>
