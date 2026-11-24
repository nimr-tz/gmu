{{--
    The seed grant email, from the owner's "Email" design. Inline styles and hex colours are
    required in email (copied from the tokens); everything sits in tables for mail clients.
--}}
@php $host = parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url'); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background:#E4E4E7;font-family:Arial,Helvetica,sans-serif;color:#18181B;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E4E4E7;padding:24px 12px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#F4F4F5;border-collapse:separate;border-radius:14px;">
      <tr><td style="padding:20px 24px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="40" style="vertical-align:middle;"><img src="{{ asset('images/nimr-logo.png') }}" width="36" height="36" alt="NIMR" style="display:block;border-radius:999px;background:#FFFFFF;"></td>
          <td style="vertical-align:middle;padding-left:10px;font-size:14px;font-weight:bold;color:#18181B;">NIMR Grants<div style="font-size:12px;font-weight:normal;color:#67676F;">National Institute for Medical Research</div></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:0 8px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #E6E6E9;border-radius:12px;border-collapse:separate;">
          <tr><td style="background:{{ $style['band'] }};padding:24px 28px 26px;border-radius:12px 12px 0 0;color:#FFFFFF;">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
              <td style="background:{{ $style['chip'] }};color:{{ $style['chipInk'] }};border-radius:6px;padding:3px 8px;font-size:12px;font-weight:bold;white-space:nowrap;">{{ $style['kicker'] }}</td>
              <td style="padding-left:10px;font-family:'Courier New',monospace;font-size:12.5px;color:{{ $style['muted'] }};white-space:nowrap;">{{ $reference }}</td>
            </tr></table>
            <div style="margin-top:14px;font-size:24px;line-height:1.25;font-weight:bold;">{{ $headline }}</div>
          </td></tr>
          <tr><td style="padding:28px 28px 8px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#18181B;">Dear {{ $recipientName }},</p>
            <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#3F3F46;">{{ $body }}</p>
          </td></tr>
          @if($facts)
            <tr><td style="padding:0 28px 8px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F7F8;border:1px solid #E6E6E9;border-radius:10px;border-collapse:separate;">
                @foreach($facts as [$k, $v])
                  <tr>
                    <td style="padding:11px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #E6E6E9;' }}font-size:13px;color:#67676F;">{{ $k }}</td>
                    <td align="right" style="padding:11px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #E6E6E9;' }}font-size:13.5px;font-weight:bold;color:#18181B;">{{ $v }}</td>
                  </tr>
                @endforeach
              </table>
            </td></tr>
          @endif
          <tr><td style="padding:20px 28px 30px;">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="background:{{ $style['band'] }};border-radius:9px;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;">{{ $button }} &rarr;</a></td></tr></table>
            <p style="margin:16px 0 0;font-size:12.5px;line-height:1.6;color:#67676F;">Or open <a href="{{ config('app.url') }}" style="color:#1D4FC4;">{{ $host }}</a> and sign in with NREIMS.</p>
          </td></tr>
        </table>
      </td></tr>
      <tr><td style="padding:14px 28px 22px;font-size:12px;line-height:1.6;color:#67676F;">{{ $why }} The same message is in your notifications in the portal.<br>Grants Management Unit, National Institute for Medical Research, Dar es Salaam.</td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
