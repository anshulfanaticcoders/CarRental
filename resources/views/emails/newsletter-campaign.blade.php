<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $log->campaign->subject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #153B4F; padding: 24px 32px; border-radius: 8px 8px 0 0; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 1px;">
                                Vrooem
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px; line-height: 1.6; color: #333333; font-size: 16px;">
                            {!! $processedContent !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 24px 32px; border-radius: 0 0 8px 8px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0 0 8px; color: #6c757d; font-size: 13px;">
                                You received this email because you subscribed to the Vrooem newsletter.
                            </p>
                            <p style="margin: 0; color: #6c757d; font-size: 13px;">
                                <a href="{{ $unsubscribeUrl }}" style="color: #153B4F; text-decoration: underline;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Tracking pixel --}}
    <img src="{{ $trackingPixelUrl }}" width="1" height="1" alt="" style="display: block; width: 1px; height: 1px; border: 0;" />
</body>
</html>
