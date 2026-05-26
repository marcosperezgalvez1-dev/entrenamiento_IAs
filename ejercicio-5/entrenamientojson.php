<?php
$respuesta = "";
$tiempo = null;
$pregunta = $_POST["pregunta"] ?? "";
$contexto_usado = "";

// Función que busca en el JSON las entradas más relevantes
function buscarContexto($pregunta, $conocimiento) {
    $palabras_pregunta = explode(" ", strtolower($pregunta));
    $resultados = [];

    foreach ($conocimiento as $entrada) {
        $texto = strtolower($entrada["pregunta"] . " " . $entrada["respuesta"]);
        $coincidencias = 0;

        foreach ($palabras_pregunta as $palabra) {
            if (strlen($palabra) > 3 && strpos($texto, $palabra) !== false) {
                $coincidencias++;
            }
        }

        if ($coincidencias > 0) {
            $resultados[] = [
                "entrada"      => $entrada,
                "coincidencias" => $coincidencias
            ];
        }
    }

    // Ordenamos por relevancia
    usort($resultados, fn($a, $b) => $b["coincidencias"] - $a["coincidencias"]);

    // Devolvemos las 3 más relevantes
    return array_slice(array_column($resultados, "entrada"), 0, 3);
}

if (isset($_POST["preguntar"]) && !empty($pregunta)) {

    // Cargamos el JSON de conocimiento
    $json_raw    = file_get_contents(__DIR__ . "/conocimiento.json");
    $conocimiento = json_decode($json_raw, true);

    // Buscamos contexto relevante
    $entradas_relevantes = buscarContexto($pregunta, $conocimiento);

    // Construimos el bloque de contexto
    if (!empty($entradas_relevantes)) {
        $contexto_usado = "Usa ÚNICAMENTE la siguiente información para responder:\n\n";
        foreach ($entradas_relevantes as $entrada) {
            $contexto_usado .= "P: " . $entrada["pregunta"] . "\n";
            $contexto_usado .= "R: " . $entrada["respuesta"] . "\n\n";
        }
    } else {
        $contexto_usado = "No se ha encontrado información específica sobre este tema en la base de conocimiento.";
    }

    $prompt = "Eres el asistente virtual de un instituto de formación profesional.
{$contexto_usado}
Si la información proporcionada no es suficiente para responder, indícalo amablemente y sugiere contactar con secretaría.
Responde siempre en español, de forma clara y amable.
Pregunta del usuario: {$pregunta}";

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

    $json_resp = json_decode($resultado, true);
    $respuesta = trim($json_resp["response"] ?? "No se pudo obtener respuesta.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente Instituto · Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0b0d14;
            --surface: #111420;
            --border:  #1a1f2e;
            --accent:  #38bdf8;
            --accent2: #fb923c;
            --text:    #e8eaf0;
            --muted:   #52586e;
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
                radial-gradient(circle at 20% 20%, rgba(56,189,248,.04) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(251,146,60,.03) 0%, transparent 50%);
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
            background: rgba(56,189,248,.08);
            border: 1px solid rgba(56,189,248,.2);
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

        /* Sugerencias rápidas */
        .sugerencias {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 28px;
            justify-content: center;
        }

        .sugerencia-btn {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            padding: 6px 14px;
            cursor: pointer;
            transition: all .2s;
        }

        .sugerencia-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .card {
            width: 100%;
            max-width: 700px;
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
            padding: 14px 16px;
            resize: vertical;
            min-height: 100px;
            outline: none;
            transition: border-color .2s;
        }

        textarea:focus { border-color: rgba(56,189,248,.4); }
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
            color: #0b0d14;
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

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(56,189,248,.25);
        }

        .divider { height: 1px; background: var(--border); margin: 28px 0; }

        .response-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: 12px;
            padding: 18px 20px;
            font-size: 15px;
            line-height: 1.75;
            white-space: pre-wrap;
        }

        /* Bloque de contexto usado (transparencia del sistema) */
        .contexto-box {
            margin-top: 16px;
            background: rgba(251,146,60,.04);
            border: 1px solid rgba(251,146,60,.15);
            border-radius: 12px;
            padding: 14px 18px;
        }

        .contexto-title {
            font-family: 'Syne', sans-serif;
            font-size: 10px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 8px;
        }

        .contexto-text {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
            white-space: pre-wrap;
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
            margin-top: 10px;
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
    <div class="badge">🎓 Asistente Instituto · IA Local</div>
    <h1>Asistente Virtual del Instituto</h1>
    <p class="subtitle">Pregúntame cualquier cosa sobre el instituto. Respondo con información real.</p>
</header>

<!-- Sugerencias de preguntas -->
<div class="sugerencias">
    <?php
    $sugerencias = [
        "¿Cuál es el horario?",
        "¿Qué ciclos ofrecéis?",
        "¿Cómo me matriculo?",
        "¿Tenéis bolsa de trabajo?",
        "¿Dónde estáis ubicados?",
    ];
    foreach ($sugerencias as $s) {
        echo "<button class='sugerencia-btn' onclick=\"document.querySelector('textarea').value='{$s}';\">{$s}</button>";
    }
    ?>
</div>

<div class="card">
    <form method="POST">
        <div class="step-label">Tu pregunta</div>
        <textarea name="pregunta"
            placeholder="Ej: ¿A qué hora abre el instituto?"
        ><?php echo htmlspecialchars($pregunta); ?></textarea>

        <div class="btn-row">
            <button type="submit" name="preguntar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Preguntar
            </button>
        </div>
    </form>

    <?php if (!empty($respuesta)): ?>
    <div class="divider"></div>

    <div class="step-label">Respuesta</div>
    <div class="response-box"><?php echo htmlspecialchars($respuesta); ?></div>

    <?php if ($tiempo): ?>
        <div class="timer-pill">⏱ <?php echo $tiempo; ?>s</div>
    <?php endif; ?>

    <!-- Mostramos el contexto que se usó, muy útil para la demo del examen -->
    <div class="contexto-box">
        <div class="contexto-title">📚 Contexto del JSON utilizado</div>
        <div class="contexto-text"><?php echo htmlspecialchars($contexto_usado); ?></div>
    </div>
    <?php endif; ?>
</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>
