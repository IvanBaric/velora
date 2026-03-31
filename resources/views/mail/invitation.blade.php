<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Team invitation</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
        <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;overflow:hidden;">
            <div style="padding:32px;border-bottom:1px solid #e4e4e7;background:#18181b;color:#ffffff;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">Team invitation</p>
                <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ $invitation->team->name }}</h1>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    You have been invited to join <strong>{{ $invitation->team->name }}</strong>.
                </p>

                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    Assigned role: <strong>{{ $roleLabel }}</strong>
                </p>

                <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">
                    Invitation expires on <strong>{{ optional($invitation->expires_at)->format('d.m.Y. H:i') ?? 'never' }}</strong>.
                </p>

                <p style="margin:0 0 24px;">
                    <a href="{{ $url }}" style="display:inline-block;padding:14px 22px;background:#18181b;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;">
                        Open invitation
                    </a>
                </p>
            </div>
        </div>
    </body>
</html>
