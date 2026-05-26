<?php
$pregunta_ia   = "";
$resultado     = "";
$tiempo_pregunta = null;
$tiempo_respuesta = null;
$categoria_elegida = $_POST["categoria"] ?? "historia";
$pregunta_guardada = $_POST["pregunta_guardada"] ?? "";
$respuesta_usuario = $_POST["respuesta_usuario"] ?? "";

// ACCIÓN 1: El usuario pide que la IA genere una pregunta
if (isset($_POST["generar"])) {
    $prompt = "Eres un juego de preguntas y respuestas. 
    Genera UNA SOLA pregunta interesante sobre el tema: {$categoria_elegida}. 
    Escribe únicamente la pregunta, sin numeración, sin explicación, sin respuesta. 
    Responde siempre en español.";

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
    $resultado_curl = curl_exec($curl);
    curl_close($curl);
    $tiempo_pregunta = round(microtime(true) - $inicio, 2);

    $json = json_decode($resultado_curl, true);
    $pregunta_ia = trim($json["response"] ?? "No se pudo generar la pregunta.");
}

// ACCIÓN 2: El usuario envía su respuesta para que la IA la corrija
if (isset($_POST["comprobar"]) && !empty($pregunta_guardada) && !empty($respuesta_usuario)) {
    $prompt = "Eres un juez de un concurso de preguntas. 
    La pregunta era: \"{$pregunta_guardada}\"
    El usuario ha respondido: \"{$respuesta_usuario}\"
    Evalúa si la respuesta es correcta o incorrecta. 
    Empieza con ✅ CORRECTO o ❌ INCORRECTO según corresponda.
    Luego explica brevemente cuál era la respuesta correcta.
    Responde siempre en español y de forma amigable.";

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
    $resultado_curl = curl_exec($curl);
    curl_close($curl);
    $tiempo_respuesta = round(microtime(true) - $inicio, 2);

    $json = json_decode($resultado_curl, true);
    $resultado = trim($json["response"] ?? "No se pudo evaluar la respuesta.");
    $pregunta_ia = $pregunta_guardada;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz IA · Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0a0c10;
            --surface: #11141c;
            --border:  #1c2030;
            --accent:  #f0c040;
            --accent2: #ff6b6b;
            --green:   #00e5a0;
            --text:    #e8eaf0;
            --muted:   #555d70;
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
                radial-gradient(circle at 15% 50%, rgba(240,192,64,.04) 0%, transparent 50%),
                radial-gradient(circle at 85% 20%, rgba(255,107,107,.04) 0%, transparent 50%);
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
            background: rgba(240,192,64,.08);
            border: 1px solid rgba(240,192,64,.2);
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
            font-size: clamp(2rem, 5vw, 3rem);
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
            max-width: 680px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            animation: fadeUp .5s .1s ease both;
        }

        /* Pasos visuales */
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
            color: #0a0c10;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
        }

        select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            padding: 13px 16px;
            outline: none;
            cursor: pointer;
            transition: border-color .2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555d70' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
        }

        select:focus { border-color: rgba(240,192,64,.4); }

        textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 300;
            line-height: 1.6;
            padding: 14px 16px;
            resize: vertical;
            min-height: 90px;
            outline: none;
            transition: border-color .2s;
        }

        textarea:focus { border-color: rgba(240,192,64,.4); }
        textarea::placeholder { color: var(--muted); }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        .btn-row { display: flex; justify-content: flex-end; margin-top: 14px; }

        button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .05em;
            padding: 11px 26px;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        button:hover    { transform: translateY(-2px); }
        button:active   { transform: translateY(0); }

        .btn-primary {
            background: var(--accent);
            color: #0a0c10;
            box-shadow: 0 4px 16px rgba(240,192,64,.15);
        }

        .btn-primary:hover { box-shadow: 0 8px 24px rgba(240,192,64,.3); }

        .btn-check {
            background: var(--green);
            color: #0a0c10;
            box-shadow: 0 4px 16px rgba(0,229,160,.15);
        }

        .btn-check:hover { box-shadow: 0 8px 24px rgba(0,229,160,.3); }

        /* Bloque pregunta generada */
        .question-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: 12px;
            padding: 18px 20px;
            font-size: 16px;
            line-height: 1.65;
            margin-bottom: 6px;
        }

        /* Bloque resultado */
        .result-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--green);
            border-radius: 12px;
            padding: 18px 20px;
            font-size: 15px;
            line-height: 1.75;
            white-space: pre-wrap;
            margin-bottom: 6px;
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
            margin-top: 8px;
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
    <div class="badge">Quiz IA · Local</div>
    <h1>Quiz con Inteligencia Artificial</h1>
    <p class="subtitle">Elige un tema, responde la pregunta y descubre si aciertas.</p>
</header>

<div class="card">

    <!-- PASO 1: elegir categoría y generar pregunta -->
    <form method="POST">
        <div class="step-label">
            <span class="step-num">1</span> Elige una categoría
        </div>

        <select name="categoria">
            <?php
            $categorias = [
                "historia"     => "🏛️  Historia",
                "ciencia"      => "🔬  Ciencia",
                "tecnologia"   => "💻  Tecnología",
                "geografia"    => "🌍  Geografía",
                "arte"         => "🎨  Arte y cultura",
                "curiosidades" => "🤔  Curiosidades",
            ];
            foreach ($categorias as $val => $label) {
                $sel = ($categoria_elegida === $val) ? "selected" : "";
                echo "<option value=\"{$val}\" {$sel}>{$label}</option>";
            }
            ?>
        </select>

        <div class="btn-row">
            <button type="submit" name="generar" class="btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.01-6"/>
                </svg>
                Generar pregunta
            </button>
        </div>
    </form>

    <!-- PASO 2: mostrar pregunta y recoger respuesta -->
    <?php if (!empty($pregunta_ia)): ?>
    <div class="divider"></div>

    <div class="step-label">
        <span class="step-num">2</span> Pregunta generada
    </div>
    <div class="question-box"><?php echo htmlspecialchars($pregunta_ia); ?></div>
    <?php if ($tiempo_pregunta): ?>
        <div class="timer-pill">⏱ <?php echo $tiempo_pregunta; ?>s para generar</div>
    <?php endif; ?>

    <div class="divider"></div>

    <form method="POST">
        <!-- Pasamos la pregunta al siguiente POST para poder evaluarla -->
        <input type="hidden" name="pregunta_guardada" value="<?php echo htmlspecialchars($pregunta_ia); ?>">
        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria_elegida); ?>">

        <div class="step-label">
            <span class="step-num">3</span> Tu respuesta
        </div>
        <textarea name="respuesta_usuario" placeholder="Escribe aquí tu respuesta..."><?php echo htmlspecialchars($respuesta_usuario); ?></textarea>

        <div class="btn-row">
            <button type="submit" name="comprobar" class="btn-check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Comprobar respuesta
            </button>
        </div>
    </form>
    <?php endif; ?>

    <!-- PASO 3: resultado de la corrección -->
    <?php if (!empty($resultado)): ?>
    <div class="divider"></div>

    <div class="step-label">
        <span class="step-num">4</span> Resultado
    </div>
    <div class="result-box"><?php echo htmlspecialchars($resultado); ?></div>
    <?php if ($tiempo_respuesta): ?>
        <div class="timer-pill">⏱ <?php echo $tiempo_respuesta; ?>s para evaluar</div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>
