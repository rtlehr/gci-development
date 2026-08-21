<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | IRAD</title>
    <style>
        :root { color-scheme: light dark; font-family: ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; background: #f8fafc; color: #0f172a; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 2rem; }
        section { width: min(42rem, 100%); background: white; border: 1px solid #cbd5e1; border-radius: 0.75rem; padding: 2rem; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
        .status { font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #475569; }
        h1 { margin: .5rem 0 1rem; font-size: 1.5rem; }
        p { margin: 0; line-height: 1.6; color: #334155; }
        @media (prefers-color-scheme: dark) {
            body { background: #020617; color: #f8fafc; }
            section { background: #0f172a; border-color: #334155; }
            .status { color: #94a3b8; }
            p { color: #cbd5e1; }
        }
    </style>
</head>
<body>
<main>
    <section role="alert" aria-labelledby="identity-error-title">
        <div class="status">IRAD identity error {{ $status }}</div>
        <h1 id="identity-error-title">{{ $title }}</h1>
        <p>{{ $message }}</p>
    </section>
</main>
</body>
</html>
