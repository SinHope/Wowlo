<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color: #2A2235; line-height: 1.6;">
    <h2 style="color: #6D28D9; margin-bottom: 4px;">New message from the Wowlo contact form</h2>

    <table cellpadding="0" cellspacing="0" style="margin: 16px 0; font-size: 15px;">
        <tr><td style="padding: 2px 12px 2px 0; color: #6B5E73;"><strong>Name</strong></td><td>{{ $data['name'] }}</td></tr>
        <tr><td style="padding: 2px 12px 2px 0; color: #6B5E73;"><strong>Email</strong></td><td>{{ $data['email'] }}</td></tr>
        <tr><td style="padding: 2px 12px 2px 0; color: #6B5E73;"><strong>Subject</strong></td><td>{{ $data['subject'] }}</td></tr>
    </table>

    <div style="border-left: 3px solid #A78BFA; padding: 8px 16px; background: #FAF7FF; white-space: pre-line;">{{ $data['message'] }}</div>

    <p style="margin-top: 20px; font-size: 13px; color: #6B5E73;">Reply directly to this email to respond to {{ $data['name'] }}.</p>
</body>
</html>
