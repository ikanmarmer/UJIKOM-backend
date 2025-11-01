<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7e22ce; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .code { font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 10px; color: #7e22ce; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>StayEasy Hotel Booking</h1>
        </div>
        <div class="content">
            <h2>Email Verification</h2>
            <p>Thank you for registering with StayEasy. Please use the following verification code to verify your email address:</p>

            <div class="code">{{ $code }}</div>

            <p>This code will expire in 1 hour. If you didn't create an account, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} StayEasy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
