<?php

/*
|--------------------------------------------------------------------------
| Payroll rules
|--------------------------------------------------------------------------
| SAMPLE VALUES. Replace with the rules that apply to your organisation and
| country (tax brackets, social security rate, etc.) before running real payroll.
| All amounts are monthly.
*/
return [
    'currency' => env('PAYROLL_CURRENCY', 'USD'),
    'currency_symbol' => env('PAYROLL_CURRENCY_SYMBOL', '$'),
    'company_name' => env('PAYROLL_COMPANY_NAME', 'Your Company Ltd.'),

    // Progressive monthly income-tax brackets: income above `from` is taxed at `rate`.
    // Must be sorted by `from` ascending.
    'tax_brackets' => [
        ['from' => 0,     'rate' => 0.00],
        ['from' => 1500,  'rate' => 0.05],
        ['from' => 3000,  'rate' => 0.10],
        ['from' => 6000,  'rate' => 0.15],
        ['from' => 10000, 'rate' => 0.20],
    ],

    // Employee social-security contribution: rate x base salary, capped.
    'social_security_rate' => 0.05,
    'social_security_cap' => 500,

    // Flat monthly benefits deduction (e.g. health plan share). 0 to disable.
    'benefits_deduction' => 100,

    // Overtime pay multiplier applied to the employee's hourly overtime_rate.
    'overtime_multiplier' => 1.0,

    // Standard working hours per day; hours beyond this count as overtime in attendance.
    'standard_hours' => 8,

    // Day of month employees are paid (clamped to month end).
    'pay_day' => 28,
];
