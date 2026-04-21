<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password changed</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Georgia,'Times New Roman',serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e4e4e7;">
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#71717a;">{{ $appName }}</p>
                            <h1 style="margin:12px 0 0 0;font-size:22px;font-weight:600;color:#18181b;line-height:1.3;">Your password was updated</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 24px 28px;color:#3f3f46;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px 0;">Hi{{ $recipientName ? ', '.$recipientName : '' }},</p>
                            <p style="margin:0 0 16px 0;">This is a confirmation that the password for your account was successfully changed.</p>
                            <p style="margin:0 0 16px 0;">If you made this change, no further action is needed.</p>
                            <p style="margin:0;font-size:14px;color:#71717a;">If you did <strong>not</strong> change your password, contact support immediately and secure your account.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 28px 28px;border-top:1px solid #f4f4f5;font-size:12px;color:#a1a1aa;">
                            Sent automatically for your security. Please do not reply to this message.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
