<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Dibatasi - SIM BK</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .restrict-card { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 90%; text-align: center; }
        .icon-box { width: 80px; height: 80px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; }
    </style>
</head>
<body>

<div class="restrict-card">
    <div class="icon-box">
        <i class="fas fa-lock"></i>
    </div>
    <h3 class="fw-bold text-dark mb-3">Akun Anda Dibatasi</h3>
    <p class="text-muted mb-4">
        Maaf, akun Anda tidak dapat mengakses dashboard karena data kelas belum diperbarui dalam masa tenggang yang ditentukan.
    </p>
    
    <div class="alert alert-warning mb-4" role="alert">
        <i class="fas fa-info-circle me-2"></i> Silakan segera hubungi bagian **Tata Usaha (TU)** untuk memperbarui data kelas Anda agar akun dapat diaktifkan kembali.
    </div>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-danger px-4">
            <i class="fas fa-sign-out-alt me-2"></i> Keluar Sistem
        </button>
    </form>
</div>

</body>
</html>