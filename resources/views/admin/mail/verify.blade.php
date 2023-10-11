<!DOCTYPE html>
<html>

<head>
    <style>
        /* Gaya CSS */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #007bff;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .content {
            background-color: #fff;
            padding: 20px;
        }

        .verify-button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Verifikasi Alamat Email</h1>
        </div>
        <div class="content">
            <p>Selamat datang di Aplikasi Kami!</p>
            <p>Untuk mengaktifkan akun Anda, silakan klik tombol di bawah ini:</p>
            <a href="{{ url('verify/' . $user->verified) }}" class="verify-button">Verifikasi Alamat Email</a>
            <p>Jika tombol di atas tidak berfungsi, Anda juga dapat menyalin dan menempel URL berikut di peramban web
                Anda:</p>
            <p>{{ url('verify/' . $user->verified) }}</p>
            <p>Terima kasih atas dukungan Anda.</p>
        </div>
    </div>
</body>

</html>
