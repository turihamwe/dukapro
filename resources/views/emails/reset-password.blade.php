<!DOCTYPE html>
<html>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.6; color: #111827;">
    <h1 style="font-size: 22px;">Reset your password</h1>
    <p>Hello {{ $user->name }},</p>
    <p>We received a request to reset the password for your {{ platform_brand('name') }} account.</p>
    <p style="margin-top: 24px;">
        <a href="{{ $resetUrl }}" style="background: #4f46e5; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600;">Reset password</a>
    </p>
    <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">This link expires in {{ config('auth.passwords.users.expire', 60) }} minutes.</p>
    <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">If you did not request a password reset, you can safely ignore this email.</p>
    <p style="margin-top: 32px; font-size: 13px; color: #6b7280;">— The {{ platform_brand('name') }} Team</p>
</body>
</html>
