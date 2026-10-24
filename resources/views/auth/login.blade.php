<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name', 'Payroll') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="login-page">
    <section class="login-panel">
        <div class="login-form">
            <div class="logo"><span class="logo-mark"><i></i><i></i><i></i></span><strong>Pay<span>Roll</span></strong></div>
            <div>
                <span class="eyebrow">Welcome back</span>
                <h1>Sign in to your workspace</h1>
                <p class="sub">Manage payroll, attendance and payslips in one place.</p>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" class="form-grid">
                @csrf
                <label class="field">Work email
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </label>
                @error('email')<div class="err">{{ $message }}</div>@enderror
                <label class="field">Password
                    <span class="pw-wrap">
                        <input type="password" name="password" id="password" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" data-toggle-pw="#password" aria-label="Show password" title="Show / hide password">
                            <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </span>
                </label>
                <script>
                    document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var input = document.querySelector(btn.getAttribute('data-toggle-pw'));
                            var show = input.type === 'password';
                            input.type = show ? 'text' : 'password';
                            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                            btn.classList.toggle('pw-on', show);
                        });
                    });
                </script>
                <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
                <button type="submit" class="btn btn-primary full">Sign in</button>
            </form>

            @if (app()->environment('local'))
                <div class="demo-note">
                    <strong>Demo accounts (local only)</strong>
                    <code>admin@payroll.test · password</code>
                    <code>olivia@payroll.test · password (employee)</code>
                </div>
            @endif
        </div>
    </section>
    <section class="login-art">
        <span class="eyebrow" style="color:#67e8f9">All your people. One clear view.</span>
        <h2>Payroll that runs calmly, every month.</h2>
        <p>Review pay runs, approve calculations and give every employee a clear payslip, with loans, bonuses and attendance flowing in automatically.</p>
    </section>
</main>
</body>
</html>
