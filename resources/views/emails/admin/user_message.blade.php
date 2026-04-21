<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
  </head>
  <body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e6e8ef;">
            <tr>
              <td style="padding:18px 20px;border-bottom:1px solid #eef0f5;">
                <div style="font-size:14px;color:#6b7280;">{{ $appName }}</div>
                <div style="font-size:18px;font-weight:700;color:#111827;line-height:1.3;">{{ $subject }}</div>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 20px;color:#111827;font-size:14px;line-height:1.6;">
                @if(!empty($recipientName))
                  <div style="margin-bottom:12px;">Hi {{ $recipientName }},</div>
                @endif
                <div style="white-space:pre-line;">{{ $body }}</div>
              </td>
            </tr>
            <tr>
              <td style="padding:14px 20px;border-top:1px solid #eef0f5;color:#6b7280;font-size:12px;">
                Sent from {{ $appName }}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>

