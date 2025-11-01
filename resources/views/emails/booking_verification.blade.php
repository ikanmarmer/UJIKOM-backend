<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .code-box {
            background-color: #f4f4f4;
            border: 2px solid #4F46E5;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #4F46E5;
            letter-spacing: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello {{ $userName }},</h2>
        <p>Thank you for registering with StayEasy!</p>
        <p>Your booking verification code is:</p>
        
        <div class="code-box">
            <div class="code">{{ $code }}</div>
        </div>
        
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this code, please ignore this email.</p>
        
        <p>Best regards,<br>The StayEasy Team</p>
    </div>
</body>
</html>
