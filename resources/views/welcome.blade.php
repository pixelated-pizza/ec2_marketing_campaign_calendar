<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: true }" xmlns:x-transition="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketMap</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css','resources/css/campaign_chart.css',
    'resources/css/internal_promos.css','resources/css/external_promos.css',
    'resources/css/website_campaigns.css','resources/css/mainlayout.css',
    'resources/css/dashboard.css','resources/css/wsd.css'])
    <link rel="stylesheet" href="{{ asset('primeicons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/gantt/dhtmlxgantt.css') }}">
    <script src="{{ asset('vendor/gantt/dhtmlxgantt.js') }}"></script>
</head>

<body class="">

    <div id="app"></div>

    @vite('resources/js/app.js')
</body>

</html>