@extends('layouts.auth')

@section('title', 'Register your business | ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Create your business account',
    ])

    <x-card class="shadow-sm">
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">
                <p class="font-semibold">You are almost there!</p>
                <p class="mt-1 text-amber-900/90">Please check the tips below each field - small fixes will get your shop online in a minute.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-3 sm:space-y-4" id="register-form" data-skip-validate>
            @csrf
            <div>
                <x-input type="text" name="business_name" label="Business name" value="{{ old('business_name') }}" required autofocus
                         class="@error('business_name') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                @error('business_name')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                {{-- @else
                    <p class="mt-1 text-xs text-gray-500">Use the name customers know - your shop or company name.</p> --}}
                @enderror
            </div>
            <div>
                <label for="business_type" class="mb-1.5 block text-sm font-medium text-gray-700">Business type <span class="text-red-500">*</span></label>
                <select name="business_type" id="business_type" required
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('business_type') border-red-300 ring-red-200 @enderror">
                    <option value="">Select</option>
                    @foreach(\App\Enums\BusinessType::labels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('business_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('business_type')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                {{-- @else
                    <p class="mt-1 text-xs text-gray-500">Tip: Pick the option that best matches what you sell today.</p> --}}
                @enderror
            </div>
            <div>
                <x-input type="text" name="name" label="Your name" value="{{ old('name') }}" required
                         class="@error('name') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                @error('name')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                {{-- @else
                    <p class="mt-1 text-xs text-gray-500">Tip: This is the owner or manager name shown to your team.</p> --}}
                @enderror
            </div>
            <div>
                <x-input type="text" name="username" id="username" label="Username" value="{{ old('username') }}" required
                         autocomplete="username"
                         pattern="[A-Za-z0-9_-]+"
                         title="Use letters, numbers, dashes, or underscores only - no spaces or symbols like @."
                         class="@error('username') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                @error('username')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                @else
                    <p class="mt-1 text-xs text-gray-500">Do not put spaces or special symbols like @ in your username.</p>
                @enderror
                <p id="username-status" class="mt-1 hidden text-xs" aria-live="polite"></p>
            </div>
            <div>
                <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autocomplete="email"
                         class="@error('email') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                @error('email')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                {{-- @else
                    <p class="mt-1 text-xs text-gray-500">Use an email you check often - we send welcome details there.</p> --}}
                @enderror
            </div>
            <div>
                <x-input type="tel" name="phone" label="Phone (optional)" value="{{ old('phone') }}" autocomplete="tel"
                         class="@error('phone') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                @error('phone')
                    <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                {{-- @else
                    <p class="mt-1 text-xs text-gray-500">Tip: Adding a phone helps support reach you quickly if needed.</p> --}}
                @enderror
            </div>
            <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">Currency: <strong>UGX (Ugandan Shillings)</strong></p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <x-input type="password" name="password" id="password" label="Password" required autocomplete="new-password" minlength="8"
                             class="@error('password') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                    @error('password')
                        <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                    {{-- @else
                        <p id="password-tip" class="mt-1 text-xs text-gray-500">Tip: Make your password a bit longer and add a few numbers to keep your business secure.</p> --}}
                    @enderror
                    <p id="password-live" class="mt-1 hidden text-xs" aria-live="polite"></p>
                </div>
                <div>
                    <x-input type="password" name="password_confirmation" id="password_confirmation" label="Repeat password" required autocomplete="new-password" minlength="8"
                             class="@error('password_confirmation') [&_input]:border-red-300 [&_input]:ring-red-200 @enderror" />
                    @error('password_confirmation')
                        <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                    {{-- @else
                        <p id="password-confirmation-tip" class="mt-1 text-xs text-gray-500">Tip: Type the same password again so we know you got it right.</p> --}}
                    @enderror
                    <p id="password-confirmation-live" class="mt-1 hidden text-xs" aria-live="polite"></p>
                </div>
            </div>
            <x-button variant="primary" size="lg" type="submit" class="w-full">Create account</x-button>
        </form>
        <p class="mt-4 text-center text-xs text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Login</a>
        </p>
        <p class="mb-4 mt-4 text-center text-sm font-medium text-gray-400">Got issues? Contact 0758-582681</p>
    </x-card>
@endsection

@push('scripts')
<script>
(function () {
    function setLive(el, text, tone) {
        if (!el) return;
        if (!text) {
            el.textContent = '';
            el.classList.add('hidden');
            return;
        }
        el.textContent = text;
        el.classList.remove('hidden');
        el.className = 'mt-1 text-xs' + (tone === 'ok' ? ' text-emerald-600' : ' font-medium text-red-600');
    }

    function toggleTip(tipEl, hide) {
        if (!tipEl) return;
        tipEl.classList.toggle('hidden', !!hide);
    }

    function passwordIssues(value) {
        if (!value) {
            return null;
        }
        if (value.length < 8) {
            // return 'Please make your password a bit longer and include at least one number.';
            return 'Your password is too short.';
        }
        if (!/\d/.test(value)) {
            // return 'Please make your password a bit longer and include at least one number.';
            return 'Include at least one number.';
        }
        return null;
    }

    function passwordIsValid(value) {
        return value && value.length >= 8 && /\d/.test(value);
    }

    var usernameInput = document.getElementById('username');
    var usernameStatus = document.getElementById('username-status');
    var usernameTimer = null;

    if (usernameInput && usernameStatus) {
        usernameInput.addEventListener('input', function () {
            clearTimeout(usernameTimer);
            var start = usernameInput.selectionStart;
            var end = usernameInput.selectionEnd;
            usernameInput.value = usernameInput.value.toLowerCase();
            usernameInput.setSelectionRange(start, end);
            var value = usernameInput.value.trim();

            if (/[^a-z0-9_-]/.test(value)) {
                setLive(usernameStatus, 'Please remove spaces or symbols like @ from your username.', 'error');
                return;
            }

            if (value.length < 3) {
                setLive(usernameStatus, null);
                return;
            }

            usernameTimer = setTimeout(function () {
                fetch(@json(route('register.check-username')) + '?username=' + encodeURIComponent(value), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.available) {
                        setLive(usernameStatus, data.message || 'Great - that username is available.', 'ok');
                    } else {
                        setLive(usernameStatus, data.message || 'That username is already taken.', 'error');
                    }
                })
                .catch(function () { setLive(usernameStatus, null); });
            }, 350);
        });
    }

    var passwordInput = document.getElementById('password');
    var passwordConfirmInput = document.getElementById('password_confirmation');
    var passwordTip = document.getElementById('password-tip');
    var passwordLive = document.getElementById('password-live');
    var confirmTip = document.getElementById('password-confirmation-tip');
    var confirmLive = document.getElementById('password-confirmation-live');

    function refreshPasswordLive() {
        if (!passwordInput || !passwordLive) return;
        var value = passwordInput.value;
        var issue = passwordIssues(value);

        if (!value) {
            setLive(passwordLive, null);
            toggleTip(passwordTip, false);
            return;
        }

        toggleTip(passwordTip, true);

        if (issue) {
            setLive(passwordLive, issue, 'error');
            return;
        }

        setLive(passwordLive, 'Your password is now fine.', 'ok');
    }

    function refreshConfirmationLive() {
        if (!passwordConfirmInput || !confirmLive) return;
        var password = passwordInput ? passwordInput.value : '';
        var confirm = passwordConfirmInput.value;

        if (!confirm) {
            setLive(confirmLive, null);
            toggleTip(confirmTip, false);
            return;
        }

        toggleTip(confirmTip, true);

        if (password !== confirm) {
            // setLive(confirmLive, 'Please type the same password in both boxes so they match.', 'error');
            setLive(confirmLive, 'Please type the same password.', 'error');
            return;
        }

        if (!passwordIsValid(password)) {
            setLive(confirmLive, 'Almost there - finish strengthening your password on the left first.', 'error');
            return;
        }

        setLive(confirmLive, 'Passwords match - let\'s continue.', 'ok');
    }

    function onPasswordChange() {
        refreshPasswordLive();
        refreshConfirmationLive();
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', onPasswordChange);
    }
    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', refreshConfirmationLive);
    }
})();
</script>
@endpush
