<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>

<body
    style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7fb; margin: 0; padding: 0; color: #333333;">
    <div
        style="max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);">

        <!-- Header -->
        <div
            style="background: linear-gradient(135deg, #4f46e5, #3b82f6); padding: 25px; text-align: center; color: #ffffff;">
            <h1 style="color:#111827; margin: 0; font-size: 24px;">👋 Welcome, {{ $name }}</h1>
            <p style="margin: 8px 0 0; font-size: 14px;">
                Your journey with <strong style="font-weight: bold;">{{ config('app.name') }}</strong> starts here
            </p>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 15px;">
                We’re excited to have you on board! 🎉<br>
                Your retailer account has been created successfully.
            </p>

            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 15px;">
                As a retailer, you now have full access to manage your account and services with ease.
            </p>

            <!-- Credentials -->
            <div
                style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin: 20px 0; font-size: 15px;">
                <p style="margin: 0;">
                    <strong style="color: #111827;">Email:</strong> {{ $email }}<br>
                    <strong style="color: #111827;">Password:</strong> {{ $password }}
                </p>
            </div>

            <!-- Security Tips -->
            <div
                style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin: 20px 0; font-size: 13px; color: #b91c1c;">
                <strong>⚠️ Security Tips:</strong>
                <ul style="margin: 10px 0 0 18px; padding: 0; color: #b91c1c; font-size: 13px; list-style-type: disc;">
                    <li style="margin-bottom: 4px;">Save your credentials in a safe place.</li>
                    <li style="margin-bottom: 4px;">Delete this email once you’ve noted them.</li>
                    <li>Update your password after your first login.</li>
                </ul>
            </div>

            <!-- Download App -->
            <p style="font-size: 15px; margin: 0 0 15px;">
                📲 Download the application for quick and secure access anytime.
            </p>

            <div style="text-align: center; margin: 25px 0;">
                <a href="https://play.google.com/store/apps/details?id=com.yourapp"
                    style="display: inline-block; margin: 10px;">
                    <img src="{{ asset('images/googleplay.png') }}" alt="Get it on Google Play"
                        style="height: 55px; border: none;">
                </a>
                <a href="https://apps.apple.com/app/idXXXXXXXX" style="display: inline-block; margin: 10px;">
                    <img src="{{ asset('images/appstore.png') }}" alt="Download on the App Store"
                        style="height: 55px; border: none;">
                </a>
            </div>

            <p style="font-size: 15px; line-height: 1.6; margin: 0;">
                Once installed, simply log in to access your dashboard and start exploring 🚀.
            </p>

            <p style="font-size: 13px; color:#6b7280; margin: 20px 0 0;">
                Your information is safe with us — we’ll never share your data with anyone.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 20px; text-align: center; font-size: 13px; color: #6b7280;">
            Thanks for joining us,<br>
            <strong>{{ config('app.name') }} Team</strong><br><br>
            <small>If you didn’t create this account, please ignore this email.</small>
        </div>
    </div>
</body>

</html>
