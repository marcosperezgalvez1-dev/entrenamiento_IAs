<?php
$respuesta = "";
$tiempo = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pregunta = $_POST["pregunta"] ?? "";

    if (!empty($pregunta)) {
        // Forzamos respuesta siempre en español mediante el prompt
        $prompt_completo = "Responde siempre en español, de forma clara y útil. Pregunta del usuario: " . $pregunta;

        $datos = json_encode([
            "model"  => "llama3.2",
            "prompt" => $prompt_completo,
            "stream" => false
        ]);

        // Medimos el tiempo de respuesta
        $inicio = microtime(true);

        $curl = curl_init("http://localhost:11434/api/generate");
        curl_setopt($curl, CURLOPT_POST,           true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,     $datos);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,     ["Content-Type: application/json"]);

        $resultado = curl_exec($curl);
        curl_close($curl);

        $fin   = microtime(true);
        $tiempo = round($fin - $inicio, 2);

        $json      = json_decode($resultado, true);
        $respuesta = $json["response"] ?? "No se pudo obtener respuesta.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente IA de Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #0d0f14;
            --surface:   #13161e;
            --border:    #1e2330;
            --accent:    #00e5a0;
            --accent2:   #0099ff;
            --text:      #e8eaf0;
            --muted:     #5a6070;
            --danger:    #ff4d6d;
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
            /* fondo con patrón sutil */
            background-image:
                radial-gradient(circle at 20% 20%, rgba(0,229,160,0.04) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0,153,255,0.04) 0%, transparent 50%);
        }

        /* ── CABECERA ── */
        header {
            text-align: center;
            margin-bottom: 48px;
            animation: fadeDown .6s ease both;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,229,160,.08);
            border: 1px solid rgba(0,229,160,.2);
            color: var(--accent);
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .badge::before {
            content: '';
            width: 6px; height: 6px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            background: linear-gradient(135deg, var(--text) 30%, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            margin-top: 10px;
            color: var(--muted);
            font-size: 15px;
            letter-spacing: .02em;
        }

        /* ── TARJETA PRINCIPAL ── */
        .card {
            width: 100%;
            max-width: 720px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            animation: fadeUp .6s .1s ease both;
        }

        label {
            display: block;
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 300;
            line-height: 1.6;
            padding: 16px;
            resize: vertical;
            min-height: 120px;
            transition: border-color .2s;
            outline: none;
        }

        textarea:focus {
            border-color: rgba(0,229,160,.4);
            box-shadow: 0 0 0 3px rgba(0,229,160,.06);
        }

        textarea::placeholder { color: var(--muted); }

        .btn-row {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
        }

        button[type="submit"] {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent);
            color: #0d0f14;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: .05em;
            padding: 12px 28px;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,229,160,.25);
        }

        button[type="submit"]:active { transform: translateY(0); }

        /* ── BLOQUE RESPUESTA ── */
        .response-block {
            margin-top: 32px;
            animation: fadeUp .4s ease both;
        }

        .response-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .response-label {
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .timer-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(0,153,255,.1);
            border: 1px solid rgba(0,153,255,.2);
            color: var(--accent2);
            font-size: 12px;
            font-family: 'Syne', sans-serif;
            padding: 4px 12px;
            border-radius: 100px;
        }

        .timer-pill svg { flex-shrink: 0; }

        .response-text {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: 12px;
            padding: 20px 24px;
            font-size: 15px;
            line-height: 1.75;
            white-space: pre-wrap;
            color: var(--text);
        }

        /* ── FOOTER ── */
        footer {
            margin-top: 48px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
            letter-spacing: .04em;
        }

        footer span { color: var(--accent); }

        /* ── ANIMACIONES ── */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .3; }
        }
    </style>
</head>
<body>

<header>
    <div class="badge">IA Local · Ollama</div>
    <h1>Asistente IA de Marcos</h1>
    <p class="subtitle">Inteligencia artificial corriendo en tu propio ordenador, sin internet.</p>
</header>

<div class="card">
    <form method="POST">
        <label for="pregunta">Tu pregunta</label>
        <textarea
            id="pregunta"
            name="pregunta"
            placeholder="Escribe aquí lo que quieras preguntarle a la IA..."
        ><?php echo htmlspecialchars($_POST["pregunta"] ?? ""); ?></textarea>

        <div class="btn-row">
            <button type="submit">
                <!-- icono enviar -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Enviar pregunta
            </button>
        </div>
    </form>

    <?php if (!empty($respuesta)): ?>
    <div class="response-block">
        <div class="response-header">
            <span class="response-label">Respuesta de la IA</span>
            <?php if ($tiempo !== null): ?>
            <span class="timer-pill">
                <!-- icono reloj -->
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php echo $tiempo; ?>s
            </span>
            <?php endif; ?>
        </div>
        <div class="response-text"><?php echo htmlspecialchars($respuesta); ?></div>
    </div>
    <?php endif; ?>
</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>
