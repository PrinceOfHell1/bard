<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <div class="jumbotron bg-primary text-white text-center">
            <h1 class="display-4">Verifikasi Alamat Email</h1>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="lead">Selamat datang di Brand!</p>
                <p>Untuk mengaktifkan akun Anda, silakan klik tombol di bawah ini:</p>
                <a href="{{ url('verify/' . $user->verified) }}" class="btn btn-primary">Verifikasi Alamat Email</a>
                <p>Jika tombol di atas tidak berfungsi, Anda juga dapat menyalin dan menempel URL berikut di peramban web Anda:</p>
                <p>{{ url('verify/' . $user->verified) }}</p>
                <p>Terima kasih atas dukungan Anda.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
