<?php
$correo_corregido = "";
$tiempo = null;
$correo_original = $_POST["correo_original"] ?? "";
$tono = $_POST["tono"] ?? "formal";

if (isset($_POST["corregir"]) && !empty($correo_original)) {

    $prompt = "Eres un asistente experto en comunicación profesional.
    El usuario te va a proporcionar un correo electrónico mal redactado.
    Tu tarea es reescribirlo de forma {$tono}, clara y profesional.
    El correo corregido debe tener esta estructura exacta:
    - Asunto: (una línea con el asunto del correo)
    - Saludo: (saludo apropiado)
    - Cuerpo: (el mensaje principal bien redactado)
    - Despedida: (cierre profesional con firma)
    No añadas explicaciones ni comentarios, devuelve únicamente el correo corregido.
    Responde siempre en español.
    
    Correo original: {$correo_original}";

    $datos = json_encode([
        "model"  => "llama3.2",
        "prompt" => $prompt,
        "stream" => false
    ]);

    $inicio = microtime(true);
    $curl = curl_init("http://localhost:11434/api/generate");
    curl_setopt($curl, CURLOPT_POST,           true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,     $datos);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,     ["Content-Type: application/json"]);
    $resultado = curl_exec($curl);
    curl_close($curl);
    $tiempo = round(microtime(true) - $inicio, 2);

    $json = json_decode($resultado, true);
    $correo_corregido = trim($json["response"] ?? "No se pudo corregir el correo.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrector de Correos IA · Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0c0e15;
            --surface: #12151f;
            --border:  #1a1f2e;
            --accent:  #a78bfa;
            --accent2: #34d399;
            --text:    #e8eaf0;
            --muted:   #525870;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 48px 20px 80px;
            background-image:
                radial-gradient(circle at 10% 30%, rgba(167,139,250,.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 70%, rgba(52,211,153,.04) 0%, transparent 50%);
        }

        header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeDown .5s ease both;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(167,139,250,.08);
            border: 1px solid rgba(167,139,250,.2);
            color: var(--accent);
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 18px;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--text) 30%, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            width: 100%;
            max-width: 900px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            animation: fadeUp .5s .1s ease both;
        }

        .step-label {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-num {
            width: 20px; height: 20px;
            background: var(--accent);
            color: #0c0e15;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
        }

        textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 300;
            line-height: 1.7;
            padding: 16px;
            resize: vertical;
            min-height: 160px;
            outline: none;
            transition: border-color .2s;
        }

        textarea:focus { border-color: rgba(167,139,250,.4); }
        textarea::placeholder { color: var(--muted); }

        /* Selector de tono */
        .tono-group {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .tono-group input[type="radio"] { display: none; }

        .tono-group label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 8px 18px;
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            letter-spacing: .06em;
            cursor: pointer;
            transition: all .2s;
            color: var(--muted);
        }

        .tono-group input[type="radio"]:checked + label {
            background: rgba(167,139,250,.12);
            border-color: rgba(167,139,250,.5);
            color: var(--accent);
        }

        .tono-group label:hover { border-color: var(--muted); color: var(--text); }

        .btn-row {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent);
            color: #0c0e15;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .05em;
            padding: 12px 28px;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(167,139,250,.3);
        }

        button:active { transform: translateY(0); }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        /* Comparativa en dos columnas */
        .comparativa {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .comparativa { grid-template-columns: 1fr; }
        }

        .col-title {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .col-original .col-title { color: var(--muted); }
        .col-corregido .col-title { color: var(--accent2); }

        .email-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            font-size: 14px;
            line-height: 1.75;
            white-space: pre-wrap;
            height: 100%;
            min-height: 200px;
        }

        .col-corregido .email-box {
            border-left: 3px solid var(--accent2);
        }

        .timer-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(0,153,255,.08);
            border: 1px solid rgba(0,153,255,.18);
            color: #0099ff;
            font-size: 11px;
            font-family: 'Syne', sans-serif;
            padding: 3px 10px;
            border-radius: 100px;
            margin-top: 12px;
        }

        footer {
            margin-top: 44px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        footer span { color: var(--accent); }

        @keyframes fadeDown {
            from { opacity:0; transform: translateY(-14px); }
            to   { opacity:1; transform: translateY(0); }
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(14px); }
            to   { opacity:1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<header>
    <div class="badge">✉️ Corrector IA · Local</div>
    <h1>Corrector de Correos con IA</h1>
    <p class="subtitle">Pega tu correo, elige el tono y la IA lo reescribe de forma profesional.</p>
</header>

<div class="card">
    <form method="POST">

        <div class="step-label">
            <span class="step-num">1</span> Pega aquí tu correo original
        </div>
        <textarea name="correo_original"
            placeholder="Ej: hola, necesito q me digais cuando puedo recoger el pedido porq llevo esperando mucho y nadie me avisa, gracias"
        ><?php echo htmlspecialchars($correo_original); ?></textarea>

        <div class="step-label" style="margin-top:22px;">
            <span class="step-num">2</span> Elige el tono del correo corregido
        </div>

        <div class="tono-group">
            <?php
            $tonos = [
                "formal"    => "🏛️ Formal",
                "amigable"  => "😊 Amigable",
                "urgente"   => "⚡ Urgente",
                "conciso"   => "✂️ Conciso",
            ];
            foreach ($tonos as $val => $label) {
                $checked = ($tono === $val) ? "checked" : "";
                echo "<input type='radio' name='tono' id='tono_{$val}' value='{$val}' {$checked}>";
                echo "<label for='tono_{$val}'>{$label}</label>";
            }
            ?>
        </div>

        <div class="btn-row">
            <button type="submit" name="corregir">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
                Corregir correo
            </button>
        </div>
    </form>

    <?php if (!empty($correo_corregido)): ?>
    <div class="divider"></div>

    <div class="step-label">
        <span class="step-num">3</span> Resultado
    </div>

    <div class="comparativa">
        <div class="col-original">
            <div class="col-title">✉️ Original</div>
            <div class="email-box"><?php echo htmlspecialchars($correo_original); ?></div>
        </div>
        <div class="col-corregido">
            <div class="col-title">✅ Corregido · tono <?php echo htmlspecialchars($tono); ?></div>
            <div class="email-box"><?php echo htmlspecialchars($correo_corregido); ?></div>
        </div>
    </div>

    <?php if ($tiempo): ?>
        <div class="timer-pill">⏱ <?php echo $tiempo; ?>s para corregir</div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>
