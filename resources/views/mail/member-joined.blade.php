<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Novi član tima') }}</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
        <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;overflow:hidden;">
            <div style="padding:32px;border-bottom:1px solid #e4e4e7;background:#18181b;color:#ffffff;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">{{ __('Obavijest administratoru') }}</p>
                <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ $invitation->team->name }}</h1>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    {{ __(':name se pridružio/la timu.', ['name' => $joinedUser->name]) }}
                </p>

                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    {{ __('E-mail:') }} <strong>{{ $joinedUser->email }}</strong>
                </p>

                @if ($invitation->role_slug)
                    <p style="margin:0;font-size:16px;line-height:1.6;">
                        {{ __('Dodijeljena uloga:') }} <strong>{{ __($invitation->role_slug) }}</strong>
                    </p>
                @endif
            </div>
        </div>
    </body>
</html>
