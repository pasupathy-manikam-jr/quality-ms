<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} – {{ config('app.name') }}</title>
    <style>
        body { font: 11pt/1.45 system-ui, sans-serif; color: #18181b; max-width: 780px; margin: 24px auto; padding: 0 16px; }
        h1 { font-size: 18pt; margin: 0; } h2 { font-size: 12pt; margin: 22px 0 6px; border-bottom: 1px solid #d4d4d8; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: start; vertical-align: top; padding: 4px 8px 4px 0; }
        th { width: 32%; font-weight: 600; color: #52525b; } td.box { border: 1px solid #d4d4d8; padding: 6px; }
        .meta { color: #52525b; font-size: 9.5pt; } .pre { white-space: pre-line; }
        .toolbar { margin-bottom: 16px; } @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">{{ __('Print or save as PDF') }}</button></div>
    @yield('content')
    <p class="meta">{{ __('Printed :date from :app. Uncontrolled when printed.', ['date' => now()->format('Y-m-d H:i'), 'app' => config('app.name')]) }}</p>
</body>
</html>
