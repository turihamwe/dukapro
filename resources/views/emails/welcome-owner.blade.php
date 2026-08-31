<!DOCTYPE html>
<html>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.6; color: #111827;">
    <h1 style="font-size: 22px;">Welcome to {{ platform_brand('name') }}, {{ $user->name }}!</h1>
    <p>Your store <strong>{{ $business->name }}</strong> is ready.</p>
    <p>Your 30-day trial has started. Log in anytime to add products, manage contacts, and run your POS.</p>
    <p style="margin-top: 24px;">
        <a href="{{ $business->portalLoginUrl() }}" style="background: #4f46e5; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600;">Go to your portal</a>
    </p>
    <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">Portal URL: {{ $business->portalLoginUrl() }}</p>
    </p>
    <p style="margin-top: 32px; font-size: 13px; color: #6b7280;">— The {{ platform_brand('name') }} Team</p>
</body>
</html>
