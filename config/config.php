<?php
/*
|--------------------------------------------------------------------------
| Payroll System configuration
|--------------------------------------------------------------------------
| 1. Set your MySQL login below (XAMPP default: user "root", empty password).
| 2. The payroll rules are SAMPLE values - replace them with the rules for your
|    country / company (tax brackets, social security, benefits) before real use.
| All payroll amounts are MONTHLY.
*/
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'payroll',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    ],

    'app' => [
        'name'     => 'PayrollPro',
        'company'  => 'Your Company Ltd.',
        'currency' => '$',
        'timezone' => 'Asia/Phnom_Penh',
    ],

    'payroll' => [
        // Progressive monthly income tax: income above `from` is taxed at `rate` (sorted ascending).
        'tax_brackets' => [
            ['from' => 0,     'rate' => 0.00],
            ['from' => 1500,  'rate' => 0.05],
            ['from' => 3000,  'rate' => 0.10],
            ['from' => 6000,  'rate' => 0.15],
            ['from' => 10000, 'rate' => 0.20],
        ],
        'social_security_rate' => 0.05,   // x base salary ...
        'social_security_cap'  => 500,    // ... capped at this amount
        'benefits_deduction'   => 100,    // flat monthly benefits deduction (0 to disable)
        'overtime_multiplier'  => 1.0,    // x employee's hourly overtime rate
        'standard_hours'       => 8,      // hours/day before overtime starts
        'late_after'           => '09:15:00', // check-in after this = "late"
        'pay_day'              => 28,     // day of month employees are paid
        'annual_leave_days'    => 20,
    ],
];
