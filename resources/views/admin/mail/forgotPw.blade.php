<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <h2>Reset Password</h2>
                <p>Anda menerima email ini karena Anda meminta reset password akun Anda. Silakan gunakan token berikut
                    untuk mereset password Anda:</p>
                <div class="alert alert-info">
                    <strong>Token:</strong> <span>{{ $token }}</span>
                </div>
                <p>Jika Anda tidak merasa perlu mereset password, Anda dapat mengabaikan email ini.</p>
                <p>Terima kasih!</p>
            </div>
        </div>
    </div>
</body>

</html>
