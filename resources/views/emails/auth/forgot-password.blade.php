<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password reset code</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Georgia,'Times New Roman',serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e4e4e7;">
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#71717a;">{{ $appName }}</p>
                            <h1 style="margin:12px 0 0 0;font-size:22px;font-weight:600;color:#18181b;line-height:1.3;">Reset your password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 24px 28px;color:#3f3f46;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px 0;">Hi{{ $recipientName ? ', '.$recipientName : '' }},</p>
                            <p style="margin:0 0 16px 0;">We received a request to reset your password. Enter this code in the app where prompted, then choose a new password.</p>
                            <p style="margin:24px 0;text-align:center;">
                                <span style="display:inline-block;letter-spacing:0.35em;font-size:28px;font-weight:700;color:#18181b;font-family:ui-monospace,Menlo,Consolas,monospace;padding:16px 24px;background:#f4f4f5;border-radius:8px;border:1px solid #e4e4e7;">{{ $otpCode }}</span>
                            </p>
                            <p style="margin:0;font-size:14px;color:#71717a;">This code expires in <strong>{{ $expiresMinutes }} minutes</strong>. Do not share it with anyone.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 28px 28px;border-top:1px solid #f4f4f5;font-size:12px;color:#a1a1aa;">
                            If you did not request a password reset, you can ignore this email. Your password will stay the same.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
