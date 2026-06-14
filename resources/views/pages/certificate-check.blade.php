<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CalamusEducation">
    <meta name="author" content="CalamusEducation">
    <title>Calamus | Certificate Authentication</title>

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700,500" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rosario:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Roboto', 'Rosario', sans-serif;
            background: linear-gradient(180deg, #f5f5f5 0%, #e8e8e8 100%);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .wrapper { width: 100%; max-width: 560px; }
        .cert-card,
        .error-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .cert-card {
            overflow: hidden;
            padding: 32px 28px;
        }
        .cert-header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .cert-header h1 {
            font-family: 'Rosario', sans-serif;
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 0.5px;
            color: #1a1a1a;
            margin: 0 0 16px 0;
        }
        .cert-badge {
            width: 56px;
            height: 56px;
            margin: 0 auto;
            border-radius: 50%;
            background: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 28px;
            line-height: 1;
        }
        .cert-details {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .cert-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }
        .cert-row:last-child { border-bottom: none; }
        .cert-row .label {
            font-family: 'Rosario', sans-serif;
            font-weight: 600;
            color: #555;
            min-width: 120px;
            flex-shrink: 0;
        }
        .cert-row .value {
            color: #1a1a1a;
            word-break: break-word;
        }
        .error-container {
            text-align: center;
            padding: 48px 32px;
            max-width: 400px;
            margin: 0 auto;
        }
        .error-badge {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #eee;
            color: #777;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }
        .error-text {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }
        @media (max-width: 520px) {
            body { padding: 16px; }
            .cert-card { padding: 28px 22px; }
            .cert-row {
                flex-direction: column;
                gap: 4px;
            }
            .cert-row .label { min-width: 0; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        @if(empty($error))
            <div class="cert-card" id="captureArea">
                <div class="cert-header">
                    <h1>Certificate Authentication</h1>
                    <div class="cert-badge">&#10003;</div>
                </div>
                <div class="cert-details">
                    <div class="cert-row">
                        <span class="label">Certificate ID</span>
                        <span class="value">{{ $certificate_ref }}</span>
                    </div>
                    <div class="cert-row">
                        <span class="label">Name</span>
                        <span class="value">{{ $user['learner_name'] }}</span>
                    </div>
                    <div class="cert-row">
                        <span class="label">Course</span>
                        <span class="value">{{ $course['certificate_title'] }}</span>
                    </div>
                    <div class="cert-row">
                        <span class="label">Issued Date</span>
                        <span class="value">{{ $certificate['formatted_date'] }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="error-container">
                <div class="error-badge">!</div>
                <div class="error-text">{!! $error !!}</div>
            </div>
        @endif
    </div>
</body>
</html>
