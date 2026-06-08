<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SMK BIT BINA AULIA</title>
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
            min-height: 600px;
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
            min-height: 600px;
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
            margin-bottom: 20px;
            text-align: center;
        }

        .divider {
            text-align: center;
            color: #999;
            font-size: 0.85rem;
            margin: 10px 0 20px 0;
        }

        .form-input {
            background: #f0f0f0;
            border: 1px solid transparent;
            padding: 12px 16px;
            width: 100%;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }

        .form-input:focus {
            background: #fff;
            border-color: #4169E1;
            outline: none;
            box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.15);
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

            .btn-signin {
                padding: 10px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="row-wrapper">
            <!-- Left Side - Form -->
            <div class="form-container">
                <h1>Lupa Password</h1>

                <div class="divider">Ajukan permohonan reset password kepada Admin Sekolah</div>

                @if(session('success'))
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST">
                    @csrf
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">NIS / Email <span class="text-danger">*</span></label>
                            <input type="text" class="form-input" name="username" placeholder="Masukkan NIS / Email" value="{{ old('username') }}" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">NIS / NIP <span class="fw-normal text-black-50">(Opsional)</span></label>
                            <input type="text" class="form-input" name="nis_nip" placeholder="Masukkan NIS / NIP" value="{{ old('nis_nip') }}">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium small text-muted mb-1">Nama Lengkap Pemohon <span class="text-danger">*</span></label>
                        <input type="text" class="form-input" name="full_name" placeholder="Masukkan nama lengkap sesuai data" value="{{ old('full_name') }}" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">Kelas <span class="fw-normal text-black-50">(Opsional)</span></label>
                            <input type="text" class="form-input" name="class" placeholder="Contoh: 10" value="{{ old('class') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">Jurusan <span class="fw-normal text-black-50">(Opsional)</span></label>
                            <input type="text" class="form-input" name="major" placeholder="Contoh: Pemasaran" value="{{ old('major') }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium small text-muted mb-1">Alasan Permohonan Reset <span class="text-danger">*</span></label>
                        <textarea class="form-input" name="reason" placeholder="Berikan alasan mengapa Anda mengajukan reset password (min. 10 karakter).&#10;Contoh: Saya lupa password dan sudah tidak bisa login." required>{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="btn-signin w-100">Kirim Permohonan</button>

                    <a href="{{ route('login') }}" class="forgot-link" style="margin-top: 16px;">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke halaman Login
                    </a>
                </form>
            </div>

            <!-- Right Side - Overlay Panel -->
            <div class="overlay-container">
                <div class="overlay-content">
                    <div class="mb-4">
                        <i class="fas fa-key" style="font-size: 5rem; opacity: 0.9;"></i>
                    </div>
                    <h1>Lupa Password?</h1>
                    <p>Jangan khawatir. Kirimkan permohonan reset password, dan Admin kami akan menindaklanjutinya secepat mungkin.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
