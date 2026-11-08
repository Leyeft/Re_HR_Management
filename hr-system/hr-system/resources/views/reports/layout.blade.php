{{-- Shared look for every PDF report (rendered by dompdf: plain CSS, no JS). --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title') · Crewly</title>
    <style>
        @page { margin: 28px 32px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; color: #1A2B3C; }
        .brand { border-bottom: 3px solid #2EC4B6; padding-bottom: 8px; margin-bottom: 12px; }
        .brand .name { font-size: 18px; font-weight: bold; color: #12305A; }
        .brand .name span { color: #2EC4B6; }
        .brand .meta { float: right; text-align: right; color: #4A5D73; font-size: 9px; line-height: 1.5; }
        h1 { font-size: 15px; color: #12305A; margin: 0 0 4px; }
        .filters { color: #4A5D73; margin-bottom: 10px; }
        .totals { margin-bottom: 12px; }
        .totals td { border: 1px solid #DDE4EC; padding: 6px 10px; background: #F4F7FA; }
        .totals .num { font-size: 14px; font-weight: bold; color: #12305A; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #12305A; color: #fff; text-align: left; padding: 5px 6px; font-size: 9px; }
        table.data td { border-bottom: 1px solid #DDE4EC; padding: 4px 6px; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #F4F7FA; }
        .num-col { text-align: right; }
        .muted { color: #4A5D73; }
        .status { font-weight: bold; }
        .status-approved { color: #187A46; }
        .status-rejected { color: #C93A3A; }
        .status-pending_head, .status-pending_hr { color: #9A5B00; }
        .status-cancelled { color: #4A5D73; }
        .empty { padding: 16px; text-align: center; color: #4A5D73; }
        footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #4A5D73; }
        footer .page { position: absolute; right: 0; top: 0; }
        footer .page:after { content: "Page " counter(page); }
    </style>
</head>
<body>
    <footer>Crewly HR · Confidential — for internal use only<span class="page"></span></footer>

    <div class="brand">
        <div class="meta">
            Generated {{ now()->format('d M Y, H:i') }}<br>
            by {{ auth()->user()?->name }}
        </div>
        <div class="name">Crew<span>ly</span></div>
    </div>

    @yield('content')
</body>
</html>
