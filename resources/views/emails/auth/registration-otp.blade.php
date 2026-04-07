<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 16px; overflow: hidden; margin-top: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: -0.5px; }
        .content { padding: 40px; }
        .content p { font-size: 16px; line-height: 24px; color: #475569; margin: 0 0 20px 0; }
        .otp-box { background-color: #f0fdfa; border-radius: 12px; padding: 24px; text-align: center; margin: 32px 0; border: 1px solid #99f6e4; }
        .otp-code { font-family: monospace; font-size: 36px; font-weight: 700; color: #0d9488; letter-spacing: 8px; margin: 0; }
        .footer { padding: 32px 40px; text-align: center; border-top: 1px solid #e2e8f0; background-color: #f8fafc; }
        .footer p { font-size: 14px; color: #64748b; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header">
                <h1>SmartSpace Library</h1>
            </div>
            <div class="content">
                <p>Hello <strong>{{ explode(',', $name)[1] ?? $name }}</strong>,</p>
                <p>Thank you for signing up with SmartSpace! To verify your email address and complete your registration, please enter the following 6-digit code.</p>
                <p>This code will expire in <strong>15 minutes</strong>.</p>
                
                <div class="otp-box">
                    <p class="otp-code">{{ $otpCode }}</p>
                </div>
                
                <p>If you did not attempt to create a SmartSpace account, you can safely ignore this email.</p>
                <p>Best regards,<br>The SmartSpace Team</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} SmartSpace. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
