<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $headline }}</title>
</head>
<body style="margin:0; padding:0; background:#F5F7FB; font-family:Arial, Helvetica, sans-serif; color:#0F172A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F7FB; padding:28px 12px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#FFFFFF; border:1px solid #E3E8F0; border-radius:12px;">
      <tr><td style="background:#0E1A3A; color:#FFFFFF; padding:20px 28px; border-radius:12px 12px 0 0;">
        <div style="font-size:16px; font-weight:bold;">NIMR Grants</div>
        <div style="font-size:12px; color:#C7D2EA; margin-top:2px;">{{ $callTitle ?: 'NIMR Grants' }}@if($reference) · {{ $reference }}@endif</div>
      </td></tr>
      <tr><td style="padding:28px;">
        <p style="margin:0 0 14px; font-size:15px;">Dear {{ $recipientName }},</p>
        <p style="margin:0 0 10px; font-size:17px; font-weight:bold; line-height:1.4;">{{ $headline }}</p>
        <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#334155;">{{ $body }}</p>
        <a href="{{ $actionUrl }}" style="display:inline-block; background:#2664EB; color:#FFFFFF; text-decoration:none; padding:12px 24px; border-radius:8px; font-size:15px; font-weight:bold;">Open in NIMR Grants</a>
      </td></tr>
      <tr><td style="padding:16px 28px; border-top:1px solid #E3E8F0; font-size:12px; color:#5A6577; line-height:1.6;">
        You are receiving this because of your part in the NIMR seed grant call. The same message is in your notifications in the portal.<br>
        Grants Management Unit, National Institute for Medical Research.
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
