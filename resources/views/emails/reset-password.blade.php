<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>
    <p>Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.</p>
    <p>Silakan klik tautan di bawah ini untuk melakukan reset password:</p>
    
    <a href="{{ $resetUrl  }}">Reset Password</a>
    
    <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
    
    <p>Terima kasih,<br>
    {{ config('app.name') }}</p>
</body>
</html>