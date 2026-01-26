<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f8fafc; padding:24px">

    <div style="max-width:480px; margin:auto; background:#ffffff; padding:24px; border-radius:8px">

        <h2 style="color:#0f172a;">Halo {{ $name }}</h2>

        <p>
            Kami menerima permintaan untuk reset password akun Anda.
        </p>

        <p>
            Gunakan kode OTP berikut:
        </p>

        <div style="
            font-size:28px;
            font-weight:bold;
            letter-spacing:6px;
            margin:24px 0;
            color:#16a34a;
            text-align:center
        ">
            {{ $otp }}
        </div>

        <p>
            Kode ini berlaku selama <strong>15 menit</strong>.
        </p>

        <p style="color:#64748b;font-size:13px">
            Jika Anda tidak meminta reset password, abaikan email ini.
        </p>

        <hr>

        <p style="font-size:12px;color:#94a3b8">
            © {{ date('Y') }} Koperasi Silih Tulungan
        </p>
    </div>

</body>
</html>
