<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk — TubeVault Admin</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #07080c;
            --panel: #12141e;
            --border: rgba(255,255,255,0.1);
            --text: #eef0f6;
            --muted: #8b90a5;
            --accent: #8b5cff;
            --danger: #ffc1c5;
            --danger-bg: rgba(240,113,120,0.14);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", system-ui, sans-serif;
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(700px 360px at 20% 0%, rgba(139,92,255,0.22), transparent 55%),
                radial-gradient(600px 320px at 90% 100%, rgba(71,191,255,0.1), transparent 50%),
                var(--bg);
        }
        .card {
            width: min(420px, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        }
        .brand { margin-bottom: 1.4rem; }
        .brand strong { display: block; font-size: 1.35rem; letter-spacing: -0.03em; }
        .brand span { color: var(--muted); font-size: 0.9rem; }
        label { display: block; margin: 0.85rem 0 0.35rem; font-size: 0.85rem; color: var(--muted); }
        input[type="email"], input[type="password"] {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0e1018;
            color: var(--text);
            padding: 0.75rem 0.9rem;
            font: inherit;
        }
        input:focus {
            outline: none;
            border-color: rgba(139,92,255,0.55);
            box-shadow: 0 0 0 3px rgba(139,92,255,0.15);
        }
        .row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0 1.25rem;
            color: var(--muted);
            font-size: 0.88rem;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            background: var(--accent);
            color: white;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { filter: brightness(1.08); }
        .flash {
            margin-bottom: 1rem;
            padding: 0.75rem 0.9rem;
            border-radius: 10px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: 0.9rem;
        }
        .flash--ok {
            background: rgba(90,212,160,0.14);
            color: #b7f0d4;
        }
        .back {
            display: inline-block;
            margin-top: 1rem;
            color: var(--muted);
            font-size: 0.85rem;
            text-decoration: none;
        }
        .back:hover { color: var(--text); }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <strong>TubeVault Admin</strong>
            <span>Masuk ke panel pengelolaan data</span>
        </div>

        @if (session('error'))
            <div class="flash">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="flash flash--ok">{{ session('success') }}</div>
        @endif

        <form method="POST" action="/my-panel/login">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

            <label for="password">Kata sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">

            <label class="row">
                <input type="checkbox" name="remember" value="1">
                Ingat sesi ini
            </label>

            <button type="submit">Masuk ke my-panel</button>
        </form>

        <a class="back" href="/">← Kembali ke TubeVault</a>
    </div>
</body>
</html>
