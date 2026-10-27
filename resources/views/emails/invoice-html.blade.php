<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:24px 12px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;border-collapse:collapse;background:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="background:#1f3a5f;color:#ffffff;padding:20px 28px;font-size:18px;font-weight:bold;">{{ $company->title }}</td>
                </tr>
                <tr>
                    {{-- the text is what the sender typed: escaped, line breaks kept --}}
                    <td style="padding:28px;font-size:15px;line-height:1.6;">{!! nl2br(e($body)) !!}</td>
                </tr>
                <tr>
                    <td style="padding:14px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">{{ __('mail.invoice.attached', ['file' => $file]) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
