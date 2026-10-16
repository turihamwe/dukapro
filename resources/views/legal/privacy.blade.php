@extends('layouts.landing')

@section('title', 'Privacy policy - ' . platform_brand('name'))

@section('content')
@php
    $brand = platform_brand('name');
@endphp

<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <x-dukapro-logo size="sm" />
        @auth
            <a href="{{ route('home.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-ink-900">Back to the app</a>
        @else
            <a href="{{ route('home') }}" class="text-sm font-medium text-slate-600 hover:text-ink-900">Home</a>
        @endauth
    </div>
</header>

<main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
    <p class="text-sm font-medium text-brand-700">{{ $brand }}</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">Privacy policy</h1>
    <p class="mt-3 text-sm text-slate-500">Last updated 16 October 2026</p>

    <div class="mt-8 space-y-8 text-sm leading-relaxed text-slate-700">
        <p>
            This policy explains how {{ $brand }} handles personal information when you use our point-of-sale, inventory, and business-management service, our website, and related programs such as affiliate and shareholder sign-up. {{ $brand }} is the name of the app and the service. Personal information is sent over HTTPS.
        </p>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Who this covers</h2>
            <p class="mt-2">
                It covers people who create or use a {{ $brand }} account (owners, managers, cashiers, and other staff), people who contact us for support, and people who apply to the affiliate or shareholder programs.
            </p>
            <p class="mt-2">
                Shops also store their own records in {{ $brand }} - customers, suppliers, sales, and payments. For those records, the shop is responsible for telling its customers how it uses their details. {{ $brand }} stores and processes that information so the shop can run its business.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Information we collect</h2>
            <ul class="mt-2 list-disc space-y-2 pl-5">
                <li><span class="font-medium text-ink-900">Account details.</span> Name, email, phone number, username, role, and a password. Passwords are stored in a form that cannot be read back.</li>
                <li><span class="font-medium text-ink-900">Business records you enter.</span> Shop and branch details, products, stock, sales, receipts, invoices, expenses, customers, suppliers, credit balances, and wallet or payment-account activity.</li>
                <li><span class="font-medium text-ink-900">Subscription payments.</span> The mobile-money number and provider (such as MTN or Airtel) used to pay for a plan, and the payment result we receive from our payments partner.</li>
                <li><span class="font-medium text-ink-900">Support and chat.</span> Messages you send us on WhatsApp or through the live chat on our website and sign-in pages.</li>
                <li><span class="font-medium text-ink-900">Technical logs.</span> When something goes wrong, we may store the page address, a short description of the error, and the IP address of the request. These logs are kept for about 90 days.</li>
                <li><span class="font-medium text-ink-900">Program applications.</span> Details you submit to join as an affiliate or shareholder, including contact information and, for shareholders, payment details needed to complete a deposit.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">How we use it</h2>
            <ul class="mt-2 list-disc space-y-2 pl-5">
                <li>To create your account, sign you in, and run the shop tools you have turned on.</li>
                <li>To take subscription payments and keep a record of what was paid.</li>
                <li>To answer support requests and fix faults.</li>
                <li>To prepare receipts, invoices, reports, and exports that you ask the product to produce.</li>
                <li>Where a shop has switched on Uganda EFRIS, to send the sales information that filing requires to the tax authority.</li>
                <li>To operate the affiliate and shareholder programs you choose to join.</li>
            </ul>
            <p class="mt-2">We do not sell personal information.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Who we share it with</h2>
            <ul class="mt-2 list-disc space-y-2 pl-5">
                <li><span class="font-medium text-ink-900">People in your business.</span> Owners and staff see the records their role allows, such as sales, customers, and stock.</li>
                <li><span class="font-medium text-ink-900">Payments.</span> Mobile-money payments are sent through Yo! Payments and the mobile-money network you select. They receive the phone number and amount needed to complete the payment.</li>
                <li><span class="font-medium text-ink-900">Live chat.</span> The chat widget on our public and sign-in pages is provided by Tawk.to. If you use that chat, Tawk.to receives the messages you type there.</li>
                <li><span class="font-medium text-ink-900">Tax filing.</span> If your shop uses EFRIS, invoice details are submitted for filing with the Uganda Revenue Authority.</li>
                <li><span class="font-medium text-ink-900">Hosting and maintenance.</span> The service is stored on servers we operate or rent so the product can run. We may also use a platform operator’s access to help a shop that asks for support.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Cookies and storage on your device</h2>
            <p class="mt-2">
                We use a session cookie to keep you signed in, and a security cookie so forms you submit come from your own browser. Your chosen light or dark theme is saved in this browser only. Affiliate links may store a referral code in the session until you finish registering.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">How long we keep it</h2>
            <p class="mt-2">
                Shop sales, stock movements, and amounts owed stay with the shop. If a shop has switched on Uganda EFRIS, the filings that shop has sent stay for that service. Error logs, including IP addresses, are removed after about 90 days. Personal details on a login are deleted when that person deletes the account, as described below.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Delete your account</h2>
            <p class="mt-2">
                Shop owners can ask us to delete their account from the profile page in the owner area, and on the web:
                <a href="{{ route('account.delete') }}" class="font-medium text-brand-700 hover:text-brand-600">{{ route('account.delete') }}</a>.
                You confirm with your password and tell us what happened. Sending that form does not delete the account. We read the request and contact you before any login is removed.
                Staff logins are not deleted from the POS or other staff screens. The owner who created the login removes it.
            </p>
            <p class="mt-2">
                If a login is removed after that review, sale totals, stock, and balances stay with the shop. If the shop has switched on Uganda EFRIS, the filings already sent for that service stay with it.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Security</h2>
            <p class="mt-2">
                Pages and sign-in are served over HTTPS. Passwords are stored in a form that cannot be read back. Access to a shop is limited to people invited to that business, and each person sees only what their role allows. Sign-in sessions are renewed when you log in.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Your choices</h2>
            <p class="mt-2">
                Shop owners can update their profile in the app, or send a deletion request from
                <a href="{{ route('account.delete') }}" class="font-medium text-brand-700 hover:text-brand-600">the request page</a>.
                Staff ask the owner who created the login.
                If you are a customer of a shop that uses {{ $brand }}, contact that shop about the records it keeps. Questions about this policy can also be sent on WhatsApp, linked below.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Children</h2>
            <p class="mt-2">
                {{ $brand }} is a business tool. We do not knowingly collect information from children for their own accounts.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Changes</h2>
            <p class="mt-2">
                If we change this policy, we will post the new version on this page and update the date at the top.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-ink-900">Contact</h2>
            <p class="mt-2">
                Questions about this policy can be sent on
                <a href="{{ whatsapp_support_url('Hello, I have a question about the ' . $brand . ' privacy policy.') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-700 hover:text-brand-600">WhatsApp</a>.
            </p>
        </section>
    </div>
</main>

<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-3xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p>&copy; {{ date('Y') }} {{ $brand }}.</p>
        <p>Privacy policy</p>
    </div>
</footer>
@endsection
