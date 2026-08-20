<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel') — {{ $adminSiteName ?? 'TubeVault' }} Admin</title>
    <link rel="icon" href="{{ $adminSiteFavicon ?? '/favicon.svg' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #07080c;
            --bg-elevated: #0e1018;
            --bg-panel: #12141e;
            --border: rgba(255, 255, 255, 0.08);
            --border-strong: rgba(255, 255, 255, 0.14);
            --text: #eef0f6;
            --muted: #8b90a5;
            --accent: #8b5cff;
            --accent-soft: rgba(139, 92, 255, 0.16);
            --danger: #f07178;
            --danger-soft: rgba(240, 113, 120, 0.14);
            --success: #5ad4a0;
            --success-soft: rgba(90, 212, 160, 0.14);
            --warn: #e6b84d;
            --radius: 12px;
            --shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
            --font: "DM Sans", system-ui, sans-serif;
            --mono: "JetBrains Mono", ui-monospace, monospace;
            --sidebar: 248px;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: var(--font);
            background:
                radial-gradient(900px 420px at 10% -10%, rgba(139, 92, 255, 0.18), transparent 55%),
                radial-gradient(700px 380px at 100% 0%, rgba(71, 191, 255, 0.08), transparent 50%),
                var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }
        .mono { font-family: var(--mono); font-size: 0.85em; }

        .shell {
            display: grid;
            grid-template-columns: var(--sidebar) 1fr;
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 1.25rem 1rem;
            border-right: 1px solid var(--border);
            background: rgba(8, 9, 14, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.35rem 0.5rem;
        }
        .brand__mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(145deg, #9b6cff, #5b2fd6);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: -0.02em;
        }
        .brand__text strong { display: block; font-size: 0.95rem; }
        .brand__text span { color: var(--muted); font-size: 0.75rem; }

        .nav { display: flex; flex-direction: column; gap: 0.25rem; flex: 1; }
        .nav a {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            color: var(--muted);
            transition: background 0.15s, color 0.15s;
        }
        .nav a:hover { background: rgba(255,255,255,0.04); color: var(--text); }
        .nav a.is-active {
            background: var(--accent-soft);
            color: #d7c6ff;
        }
        .nav__icon {
            width: 1.1rem;
            text-align: center;
            opacity: 0.85;
        }

        .sidebar__foot {
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            display: grid;
            gap: 0.65rem;
        }
        .user-chip {
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            background: var(--bg-panel);
            border: 1px solid var(--border);
        }
        .user-chip strong { display: block; font-size: 0.88rem; }
        .user-chip span { color: var(--muted); font-size: 0.75rem; word-break: break-all; }

        .main { padding: 1.5rem 1.75rem 2.5rem; min-width: 0; }
        .topbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .topbar h1 {
            margin: 0;
            font-size: 1.55rem;
            letter-spacing: -0.03em;
        }
        .topbar p {
            margin: 0.35rem 0 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .flash {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 0.92rem;
        }
        .flash--success { background: var(--success-soft); border-color: rgba(90,212,160,0.35); color: #b7f0d4; }
        .flash--error { background: var(--danger-soft); border-color: rgba(240,113,120,0.35); color: #ffc1c5; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .stat {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.1rem;
            box-shadow: var(--shadow);
        }
        .stat__label { color: var(--muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .stat__value { margin-top: 0.35rem; font-size: 1.65rem; font-weight: 700; letter-spacing: -0.03em; }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1.1rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }
        .panel__head h2 { margin: 0; font-size: 1rem; }
        .panel__body { padding: 1.1rem; }

        .toolbar {
            display: flex;
            gap: 0.65rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .field {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
            min-width: 180px;
        }
        .field:focus {
            outline: none;
            border-color: rgba(139, 92, 255, 0.55);
            box-shadow: 0 0 0 3px rgba(139, 92, 255, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0.55rem 0.9rem;
            cursor: pointer;
            background: var(--accent);
            color: white;
            font-weight: 600;
            font-size: 0.88rem;
            transition: transform 0.12s, filter 0.12s, background 0.12s;
        }
        .btn:hover { filter: brightness(1.08); }
        .btn:active { transform: translateY(1px); }
        .btn--ghost {
            background: transparent;
            border-color: var(--border-strong);
            color: var(--text);
        }
        .btn--danger {
            background: var(--danger-soft);
            border-color: rgba(240,113,120,0.35);
            color: #ffc1c5;
        }
        .btn--sm { padding: 0.35rem 0.65rem; font-size: 0.8rem; border-radius: 8px; }

        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th, td {
            text-align: left;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            background: rgba(255,255,255,0.02);
        }
        tr:last-child td { border-bottom: 0; }
        .actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
        }
        .badge--admin { background: var(--accent-soft); color: #d7c6ff; border-color: rgba(139,92,255,0.35); }
        .badge--user { background: rgba(255,255,255,0.05); color: var(--muted); border-color: var(--border); }
        .badge--yt { background: rgba(255, 80, 80, 0.12); color: #ff9b9b; }
        .badge--sp { background: rgba(30, 215, 96, 0.12); color: #7dffb0; }
        .badge--cat { background: rgba(71, 191, 255, 0.12); color: #9ad8ff; }
        .badge--ok { background: var(--success-soft); color: #b7f0d4; }
        .badge--warn { background: rgba(230,184,77,.16); color: #f0d48a; }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        .stats--rich .stat__meta {
            margin-top: 0.45rem;
            color: var(--muted);
            font-size: 0.78rem;
        }
        .stat--link { display: block; transition: border-color .15s, transform .12s; }
        .stat--link:hover { border-color: rgba(139,92,255,.4); transform: translateY(-1px); }

        .status-list { display: grid; gap: 0.75rem; }
        .status-row {
            display: flex; justify-content: space-between; align-items: center; gap: .75rem;
            padding-bottom: .65rem; border-bottom: 1px solid var(--border); font-size: .9rem;
        }
        .status-row:last-child { border-bottom: 0; padding-bottom: 0; }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .form-stack { display: grid; gap: 0.9rem; }
        .form-stack label > span,
        .file-label > span { display: block; margin-bottom: .35rem; color: var(--muted); font-size: .82rem; }
        .form-stack .field, .form-stack textarea.field { width: 100%; min-width: 0; }
        .form-stack textarea.field { resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .asset-preview { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .asset-box {
            margin: .5rem 0 .75rem;
            min-height: 88px;
            border: 1px dashed var(--border-strong);
            border-radius: 12px;
            display: grid; place-items: center;
            background: var(--bg-elevated);
            overflow: hidden;
            padding: .75rem;
        }
        .asset-box img { max-width: 100%; max-height: 72px; object-fit: contain; }
        .asset-box--sm { min-height: 72px; }
        .asset-box--sm img { max-height: 40px; }
        .asset-box--og { min-height: 140px; }
        .asset-box--og img { max-height: 120px; width: 100%; object-fit: cover; border-radius: 8px; }
        .file-label input[type=file] { width: 100%; font-size: .82rem; color: var(--muted); }
        .check-line { display: flex; align-items: center; gap: .45rem; color: var(--muted); font-size: .85rem; margin-top: .5rem; }
        .switch-card {
            display: flex; justify-content: space-between; gap: 1rem; align-items: center;
            padding: .9rem 1rem; border: 1px solid var(--border); border-radius: 12px; background: var(--bg-elevated);
        }
        .switch-card p { margin: .25rem 0 0; font-size: .82rem; }
        .switch-card input { width: 1.15rem; height: 1.15rem; }
        .settings-actions { display: flex; gap: .65rem; flex-wrap: wrap; margin-top: 1rem; }
        .nav-section {
            margin: .85rem 0 .35rem; padding: 0 .85rem;
            color: var(--muted); font-size: .68rem; text-transform: uppercase; letter-spacing: .08em;
        }
        .brand__logo {
            width: 36px; height: 36px; border-radius: 10px; object-fit: cover;
            background: var(--bg-elevated); border: 1px solid var(--border);
        }

        /* —— UI Modals —— */
        .ui-overlay {
            position: fixed; inset: 0; z-index: 80;
            display: none;
            place-items: center;
            padding: 1.25rem;
            background: rgba(4, 5, 10, 0.62);
            backdrop-filter: blur(10px);
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease;
        }
        .ui-overlay.is-open {
            display: grid;
            opacity: 1;
            pointer-events: auto;
        }
        .ui-overlay[hidden] { display: none !important; pointer-events: none !important; }
        .ui-modal {
            width: min(440px, 100%);
            background: linear-gradient(180deg, #161926 0%, #10121a 100%);
            border: 1px solid var(--border-strong);
            border-radius: 18px;
            box-shadow: 0 30px 80px rgba(0,0,0,.55), 0 0 0 1px rgba(139,92,255,.08);
            transform: translateY(12px) scale(.98);
            opacity: 0;
            transition: transform .22s ease, opacity .22s ease;
            overflow: hidden;
        }
        .ui-modal.is-open { transform: none; opacity: 1; }
        .ui-modal--detail { width: min(560px, 100%); max-height: min(86vh, 720px); display: flex; flex-direction: column; }
        .ui-modal__head {
            display: flex; align-items: flex-start; gap: .9rem;
            padding: 1.15rem 1.2rem .85rem;
            border-bottom: 1px solid var(--border);
        }
        .ui-modal__icon {
            width: 44px; height: 44px; border-radius: 14px;
            display: grid; place-items: center; font-size: 1.15rem; flex-shrink: 0;
            background: var(--danger-soft); color: #ffc1c5;
            border: 1px solid rgba(240,113,120,.3);
        }
        .ui-modal__icon[data-tone="warn"] { background: rgba(230,184,77,.16); color: #f0d48a; border-color: rgba(230,184,77,.35); }
        .ui-modal__icon[data-tone="accent"] { background: var(--accent-soft); color: #d7c6ff; border-color: rgba(139,92,255,.35); }
        .ui-modal__titles { flex: 1; min-width: 0; }
        .ui-modal__titles h3 { margin: 0; font-size: 1.05rem; letter-spacing: -.02em; }
        .ui-modal__titles p { margin: .35rem 0 0; color: var(--muted); font-size: .9rem; line-height: 1.45; }
        .ui-modal__x {
            border: 0; background: transparent; color: var(--muted); cursor: pointer;
            width: 32px; height: 32px; border-radius: 8px; font-size: 1.1rem;
        }
        .ui-modal__x:hover { background: rgba(255,255,255,.06); color: var(--text); }
        .ui-modal__body { padding: 1rem 1.2rem 1.2rem; overflow: auto; }
        .ui-modal__foot {
            display: flex; justify-content: flex-end; gap: .55rem; flex-wrap: wrap;
            padding: .9rem 1.2rem 1.15rem; border-top: 1px solid var(--border);
            background: rgba(0,0,0,.18);
        }
        .ui-btn {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; padding: .6rem .95rem; font-weight: 600; font-size: .88rem;
            border: 1px solid transparent; cursor: pointer; color: #fff;
        }
        .ui-btn--ghost { background: transparent; border-color: var(--border-strong); color: var(--text); }
        .ui-btn--ghost:hover { background: rgba(255,255,255,.04); }
        .ui-btn--danger { background: linear-gradient(135deg, #e85d6a, #c94455); }
        .ui-btn--warn { background: linear-gradient(135deg, #d4a63a, #b88920); color: #1a1405; }
        .ui-btn--accent { background: linear-gradient(135deg, #9b6cff, #6d3ff0); }
        .ui-badge {
            display: inline-flex; margin-top: .4rem; padding: .15rem .5rem; border-radius: 999px;
            font-size: .72rem; font-weight: 600; background: var(--accent-soft); color: #d7c6ff;
        }
        .ui-detail__hero {
            margin: -.25rem 0 1rem; border-radius: 14px; overflow: hidden;
            border: 1px solid var(--border); background: #0a0b12;
            aspect-ratio: 16 / 9; max-height: 180px;
        }
        .ui-detail__hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .ui-detail__grid { display: grid; gap: .75rem; margin: 0; }
        .ui-detail__row {
            display: grid; grid-template-columns: 120px 1fr; gap: .65rem;
            padding-bottom: .7rem; border-bottom: 1px solid var(--border);
        }
        .ui-detail__row:last-child { border-bottom: 0; padding-bottom: 0; }
        .ui-detail__row dt { color: var(--muted); font-size: .8rem; margin: 0; }
        .ui-detail__row dd { margin: 0; word-break: break-word; font-size: .92rem; }
        .ui-detail__note {
            margin-top: 1rem; padding: .85rem 1rem; border-radius: 12px;
            background: rgba(139,92,255,.1); border: 1px solid rgba(139,92,255,.22);
            color: #d7c6ff; font-size: .88rem;
        }
        .ui-detail__actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }

        .chart-shell { position: relative; min-height: 260px; }
        .chart-shell--sm { min-height: 220px; }
        .chart-legend {
            display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: .75rem;
        }
        .chart-legend span {
            display: inline-flex; align-items: center; gap: .4rem;
            color: var(--muted); font-size: .78rem;
        }
        .chart-legend i {
            width: 8px; height: 8px; border-radius: 99px; display: inline-block;
        }

        .muted { color: var(--muted); }
        .empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--muted);
        }
        .pagination { display: flex; gap: 0.4rem; flex-wrap: wrap; padding: 1rem; }
        .pagination a, .pagination span {
            padding: 0.4rem 0.7rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.85rem;
        }
        .pagination .active span {
            background: var(--accent-soft);
            color: #d7c6ff;
            border-color: rgba(139,92,255,0.35);
        }

        .detail-grid {
            display: grid;
            gap: 0.85rem;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .detail-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .detail-row dt { color: var(--muted); font-size: 0.82rem; }
        .detail-row dd { margin: 0; word-break: break-word; }

        .mobile-toggle {
            display: none;
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 40;
            width: 48px;
            height: 48px;
            border-radius: 999px;
            border: 1px solid var(--border-strong);
            background: var(--bg-panel);
            color: var(--text);
            box-shadow: var(--shadow);
            cursor: pointer;
        }

        @media (max-width: 1100px) {
            .grid-3, .settings-grid { grid-template-columns: 1fr; }
            .grid-3 .panel[style*="span 2"] { grid-column: auto !important; }
        }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: min(84vw, 280px);
                z-index: 30;
                transform: translateX(-105%);
                transition: transform 0.2s ease;
            }
            .sidebar.is-open { transform: translateX(0); }
            .mobile-toggle { display: grid; place-items: center; }
            .main { padding: 1.15rem 1rem 5rem; }
            .grid-2, .form-row, .asset-preview { grid-template-columns: 1fr; }
            .detail-row, .ui-detail__row { grid-template-columns: 1fr; gap: 0.25rem; }
        }
    </style>
</head>
<body>
@php
    $siteName = $adminSiteName ?? 'TubeVault';
    $siteLogo = $adminSiteLogo ?? null;
    $siteFavicon = $adminSiteFavicon ?? '/favicon.svg';
    $navPrimary = [
        ['route' => 'admin.dashboard', 'label' => 'Ringkasan', 'icon' => '◈', 'match' => 'admin.dashboard'],
        ['route' => 'admin.users.index', 'label' => 'Pengguna', 'icon' => '◉', 'match' => 'admin.users.*'],
        ['route' => 'admin.videos.index', 'label' => 'Media', 'icon' => '▶', 'match' => 'admin.videos.*'],
        ['route' => 'admin.playlists.index', 'label' => 'Playlist', 'icon' => '☰', 'match' => 'admin.playlists.*'],
        ['route' => 'admin.feedback.index', 'label' => 'Feedback', 'icon' => '✎', 'match' => 'admin.feedback.*'],
    ];
    $navSystem = [
        ['route' => 'admin.settings.edit', 'label' => 'Settings Website', 'icon' => '⚙', 'match' => 'admin.settings.*'],
    ];
@endphp

<button type="button" class="mobile-toggle" id="adminNavToggle" aria-label="Menu">☰</button>

<div class="shell">
    <aside class="sidebar" id="adminSidebar">
        <div class="brand">
            @if ($siteLogo)
                <img class="brand__logo" src="{{ $siteLogo }}" alt="{{ $siteName }}">
            @else
                <div class="brand__mark">{{ strtoupper(substr($siteName, 0, 2)) }}</div>
            @endif
            <div class="brand__text">
                <strong>{{ $siteName }}</strong>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="nav">
            <div class="nav-section">Data</div>
            @foreach ($navPrimary as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['match']) ? 'is-active' : '' }}">
                    <span class="nav__icon">{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <div class="nav-section">Sistem</div>
            @foreach ($navSystem as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['match']) ? 'is-active' : '' }}">
                    <span class="nav__icon">{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="sidebar__foot">
            <div class="user-chip">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
            <a class="btn btn--ghost" href="/" target="_blank" rel="noopener">Buka website</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn--danger" style="width:100%" type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>@yield('heading')</h1>
                @hasSection('subheading')
                    <p>@yield('subheading')</p>
                @endif
            </div>
            <div>@yield('actions')</div>
        </div>

        @if (session('success'))
            <div class="flash flash--success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="flash flash--error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<div class="ui-overlay" data-ui-overlay hidden>
    <div class="ui-modal" data-ui-confirm role="dialog" aria-modal="true" aria-labelledby="uiConfirmTitle">
        <div class="ui-modal__head">
            <div class="ui-modal__icon" data-confirm-icon data-tone="danger">⌫</div>
            <div class="ui-modal__titles">
                <h3 id="uiConfirmTitle" data-confirm-title>Konfirmasi</h3>
                <p data-confirm-text>Apakah Anda yakin?</p>
            </div>
            <button type="button" class="ui-modal__x" data-ui-close aria-label="Tutup">×</button>
        </div>
        <div class="ui-modal__foot">
            <button type="button" class="ui-btn ui-btn--ghost" data-ui-close>Batal</button>
            <button type="button" class="ui-btn ui-btn--danger" data-confirm-ok>Ya, lanjutkan</button>
        </div>
    </div>

    <div class="ui-modal ui-modal--detail" data-ui-detail role="dialog" aria-modal="true" aria-labelledby="uiDetailTitle">
        <div class="ui-modal__head">
            <div class="ui-modal__icon" data-tone="accent">◈</div>
            <div class="ui-modal__titles">
                <h3 id="uiDetailTitle" data-detail-title>Detail</h3>
                <span class="ui-badge" data-detail-badge hidden></span>
            </div>
            <button type="button" class="ui-modal__x" data-ui-close aria-label="Tutup">×</button>
        </div>
        <div class="ui-modal__body" data-detail-body></div>
        <div class="ui-modal__foot">
            <button type="button" class="ui-btn ui-btn--ghost" data-ui-close>Tutup</button>
        </div>
    </div>
</div>

<script>
    const toggle = document.getElementById('adminNavToggle');
    const sidebar = document.getElementById('adminSidebar');
    toggle?.addEventListener('click', () => sidebar.classList.toggle('is-open'));
</script>
<script src="/js/admin-ui.js" defer></script>
@stack('scripts')
</body>
</html>
