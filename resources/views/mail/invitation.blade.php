<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', corexis_locale_code() ?: config('app.locale', 'hr')) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Pozivnica za suradnju') }}</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
        <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-radius:14px;overflow:hidden;">
            <div style="padding:30px 32px;border-bottom:1px solid #ececef;background:#ffffff;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">{{ __('Pozivnica za suradnju') }}</p>
                <h1 style="margin:0;font-size:26px;line-height:1.25;color:#18181b;">{{ $invitation->team->name }}</h1>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    {{ __('Pozvani ste da se pridružite organizaciji :team.', ['team' => $invitation->team->name]) }}
                </p>

                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    {{ __('Dodijeljena uloga:') }} <strong>{{ __($roleLabel) }}</strong>
                </p>

                @if ($invitation->grantsOwnerAccess())
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                        {{ __('Dodijeljen vam je vlasnički pristup organizaciji.') }}
                    </p>
                @endif

                <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">
                    {{ __('Pozivnica vrijedi do') }} <strong>{{ optional($invitation->expires_at)->format('d.m.Y. H:i') ?? __('bez isteka') }}</strong>.
                </p>

                <p style="margin:0 0 24px;">
                    <a href="{{ $url }}" style="display:inline-block;padding:13px 20px;background:#18181b;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">
                        {{ __('Prihvati pozivnicu') }}
                    </a>
                </p>

                <p style="margin:0;font-size:13px;line-height:1.6;color:#71717a;">
                    {{ __('Ako niste očekivali ovu poruku, možete je zanemariti.') }}
                </p>
            </div>
        </div>
    </body>
</html>
