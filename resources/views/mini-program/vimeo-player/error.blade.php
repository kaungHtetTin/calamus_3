<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calamus Education</title>
    <style>
        :root {
            --bg: #1e1f23;
            --card: #2a2c31;
            --text: #f2f4f8;
            --muted: #bcc3d0;
            --accent: #00adef;
            --danger: #ff6b6b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: radial-gradient(circle at top, #343842 0%, var(--bg) 55%);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: linear-gradient(180deg, #31343c 0%, var(--card) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--danger);
            background: rgba(255, 107, 107, 0.12);
            border: 1px solid rgba(255, 107, 107, 0.35);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: .3px;
        }
        h1 {
            margin: 14px 0 10px;
            font-size: 22px;
            line-height: 1.3;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 15px;
        }
        .tip {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #d9e4f4;
            font-size: 13px;
        }
        .tip strong { color: var(--accent); }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Error {{ $statusCode ?? 400 }}</span>
        <h1>{{ $title ?? 'Unable to open player' }}</h1>
        <p>{{ $message ?? 'Something went wrong while opening this video.' }}</p>
        <div class="tip">
            <strong>Tip:</strong> Please return to the app and try opening the lesson again. If the issue continues, contact support.
        </div>
    </main>
</body>
</html>
