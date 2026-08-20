<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Perawatan — {{ $siteName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: system-ui, sans-serif; background: {{ $themeColor }}; color: #eef0f6;
            padding: 1.5rem; text-align: center;
        }
        .box { max-width: 420px; }
        h1 { margin: 0 0 .75rem; font-size: 1.6rem; }
        p { color: #8b90a5; line-height: 1.6; }
        a { color: #b9a0ff; }
    </style>
</head>
<body>
    <div class="box">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height:48px;margin-bottom:1rem">
        @endif
        <h1>{{ $siteName }}</h1>
        <p>{{ $message }}</p>
        <p><a href="/my-panel/login">Admin login</a></p>
    </div>
</body>
</html>
