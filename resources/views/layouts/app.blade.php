<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'Payroll') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $user = auth()->user();
    $admin = $user->isAdmin();
    $pendingLeave = $admin ? \App\Models\LeaveRequest::where('status', 'pending')->count() : 0;
    $groups = $admin ? [
        'Overview' => [['dashboard', 'Dashboard', 'grid', 'dashboard']],
        'Workforce' => [['employees.index', 'Employees', 'users', 'employees.*'], ['departments.index', 'Departments', 'layers', 'departments.*']],
        'Time & attendance' => [['attendance.index', 'Attendance', 'clock', 'attendance.*'], ['leave.index', 'Leave requests', 'calendar', 'leave.*']],
        'Payroll & finance' => [
            ['payroll.index', 'Payroll', 'wallet', 'payroll.*'],
            ['adjustments.index', 'Bonuses & deductions', 'sliders', 'adjustments.*'],
            ['salary.index', 'Salary', 'credit', 'salary.*'],
            ['loans.index', 'Loans', 'landmark', 'loans.*'],
            ['expenses.index', 'Expenses', 'receipt', 'expenses.*'],
        ],
    ] : [
        'My workspace' => [
            ['dashboard', 'Dashboard', 'grid', 'dashboard'],
            ['payslips.index', 'My payslips', 'wallet', 'payslips.*'],
            ['attendance.index', 'My attendance', 'clock', 'attendance.*'],
            ['leave.index', 'My leave', 'calendar', 'leave.*'],
            ['loans.index', 'My loans', 'landmark', 'loans.*'],
            ['expenses.index', 'My expenses', 'receipt', 'expenses.*'],
        ],
    ];
@endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
        <div class="logo logo-dark"><span class="logo-mark"><i></i><i></i><i></i></span><strong>Pay<span>Roll</span></strong></div>
    </div>
    <nav class="side-nav" aria-label="Application navigation">
        @foreach ($groups as $label => $items)
            <p>{{ $label }}</p>
            @foreach ($items as [$route, $text, $icon, $match])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($match) ? 'active' : '' }}">
                    @include('partials.icon', ['name' => $icon])
                    <span>{{ $text }}</span>
                    @if ($route === 'leave.index' && $pendingLeave > 0)<b>{{ $pendingLeave }}</b>@endif
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="sidebar-user">
        <span class="avatar">{{ $user->initials() }}</span>
        <div><strong>{{ $user->name }}</strong><small>{{ $admin ? 'Administrator' : ($user->employee->position ?? 'Employee') }}</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" aria-label="Log out" title="Log out">@include('partials.icon', ['name' => 'logout'])</button>
        </form>
    </div>
</aside>

<div class="app-main">
    <header class="app-header">
        <div class="header-left">
            <button class="menu-toggle" aria-label="Open navigation" onclick="document.getElementById('sidebar').classList.toggle('open')">@include('partials.icon', ['name' => 'menu'])</button>
            <div><small>{{ config('payroll.company_name') }} / @yield('title', 'Dashboard')</small><h1>@yield('title', 'Dashboard')</h1></div>
        </div>
        <div class="header-right"><span class="badge badge-info">{{ $admin ? 'Admin' : 'Employee' }}</span></div>
    </header>

    <main class="content">
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-error" role="alert"><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
