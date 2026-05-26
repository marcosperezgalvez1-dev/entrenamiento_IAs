<?php
$respuesta      = "";
$contexto_usado = "";
$tiempo         = null;
$pregunta       = $_POST["pregunta"] ?? "";

if (isset($_POST["preguntar"]) && !empty($pregunta)) {

    $datos = json_encode(["pregunta" => $pregunta]);

    $inicio = microtime(true);

    $curl = curl_init("http://localhost:5000/preguntar");
    curl_setopt($curl, CURLOPT_POST,           true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,     $datos);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,     ["Content-Type: application/json"]);
    $resultado = curl_exec($curl);
    curl_close($curl);

    $tiempo = round(microtime(true) - $inicio, 2);

    $json           = json_decode($resultado, true);
    $respuesta      = $json["respuesta"]      ?? "Error al conectar con el servidor Python.";
    $contexto_usado = $json["contexto_usado"] ?? "";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAG Vectorial · Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #090b12;
            --surface: #0f1220;
            --border:  #181d2e;
            --accent:  #818cf8;
            --accent2: #34d399;
            --text:    #e8eaf0;
            --muted:   #4e5568;
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
                radial-gradient(circle at 15% 40%, rgba(129,140,248,.05) 0%, transparent 50%),
                radial-gradient(circle at 85% 60%, rgba(52,211,153,.04) 0%, transparent 50%);
        }

        header { text-align: center; margin-bottom: 40px; animation: fadeDown .5s ease both; }

        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(129,140,248,.08);
            border: 1px solid rgba(129,140,248,.2);
            color: var(--accent);
            font-family: 'Syne', sans-serif; font-size: 11px;
            letter-spacing: .12em; text-transform: uppercase;
            padding: 5px 14px; border-radius: 100px; margin-bottom: 18px;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800;
            background: linear-gradient(135deg, var(--text) 30%, var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle { margin-top: 8px; color: var(--muted); font-size: 14px; }

        .flujo {
            display: flex; align-items: center; gap: 8px;
            flex-wrap: wrap; justify-content: center;
            margin-bottom: 36px;
        }

        .flujo-step {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 14px;
            font-family: 'Syne', sans-serif; font-size: 11px;
            letter-spacing: .06em; color: var(--muted); text-align: center;
        }

        .flujo-step.active { border-color: var(--accent); color: var(--accent); }
        .flujo-arrow { color: var(--muted); font-size: 14px; }

        .card {
            width: 100%; max-width: 700px;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 20px; padding: 32px;
            animation: fadeUp .5s .1s ease both;
        }

        .step-label {
            font-family: 'Syne', sans-serif; font-size: 11px;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 10px;
        }

        textarea {
            width: 100%; background: var(--bg);
            border: 1px solid var(--border); border-radius: 12px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 300; line-height: 1.6;
            padding: 14px 16px; resize: vertical; min-height: 90px;
            outline: none; transition: border-color .2s;
        }

        textarea:focus { border-color: rgba(129,140,248,.4); }
        textarea::placeholder { color: var(--muted); }

        .btn-row { margin-top: 16px; display: flex; justify-content: flex-end; }

        button[type="submit"] {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--accent); color: #090b12;
            font-family: 'Syne', sans-serif; font-weight: 700;
            font-size: 13px; letter-spacing: .05em;
            padding: 12px 28px; border: none; border-radius: 100px;
            cursor: pointer; transition: transform .15s, box-shadow .15s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(129,140,248,.3);
        }

        .divider { height: 1px; background: var(--border); margin: 28px 0; }

        .response-box {
            background: var(--bg); border: 1px solid var(--border);
            border-left: 3px solid var(--accent2);
            border-radius: 12px; padding: 18px 20px;
            font-size: 15px; line-height: 1.75; white-space: pre-wrap;
        }

        .contexto-box {
            margin-top: 16px;
            background: rgba(129,140,248,.04);
            border: 1px solid rgba(129,140,248,.15);
            border-radius: 12px; padding: 14px 18px;
        }

        .contexto-title {
            font-family: 'Syne', sans-serif; font-size: 10px;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--accent); margin-bottom: 8px;
        }

        .contexto-text {
            font-size: 12px; color: var(--muted);
            line-height: 1.6; white-space: pre-wrap;
        }

        .timer-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(0,153,255,.08); border: 1px solid rgba(0,153,255,.18);
            color: #0099ff; font-size: 11px; font-family: 'Syne', sans-serif;
            padding: 3px 10px; border-radius: 100px; margin-top: 10px;
        }

        footer { margin-top: 44px; color: var(--muted); font-size: 12px; text-align: center; }
        footer span { color: var(--accent); }

        @keyframes fadeDown { from { opacity:0; transform: translateY(-14px); } to { opacity:1; transform: translateY(0); } }
        @keyframes fadeUp   { from { opacity:0; transform: translateY(14px);  } to { opacity:1; transform: translateY(0); } }
    </style>
</head>
<body>

<header>
    <div class="badge">🧠 RAG · Búsqueda Semántica</div>
    <h1>Asistente RAG Vectorial</h1>
    <p class="subtitle">Búsqueda por significado, no por palabras exactas.</p>
</header>

<div class="flujo">
    <div class="flujo-step active">👤 Pregunta</div>
    <div class="flujo-arrow">→</div>
    <div class="flujo-step active">🔢 Vector</div>
    <div class="flujo-arrow">→</div>
    <div class="flujo-step active">🗄️ ChromaDB</div>
    <div class="flujo-arrow">→</div>
    <div class="flujo-step active">🧠 Ollama</div>
    <div class="flujo-arrow">→</div>
    <div class="flujo-step active">💬 Respuesta</div>
</div>

<div class="card">
    <form method="POST">
        <div class="step-label">Tu pregunta</div>
        <textarea name="pregunta"
            placeholder="Prueba a escribir de forma natural: '¿a qué hora abrís?' o '¿cuándo puedo venir?'"
        ><?php echo htmlspecialchars($pregunta); ?></textarea>

        <div class="btn-row">
            <button type="submit" name="preguntar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Buscar y responder
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

    <div class="contexto-box">
        <div class="contexto-title">🧠 Contexto recuperado de ChromaDB</div>
        <div class="contexto-text"><?php echo htmlspecialchars($contexto_usado); ?></div>
    </div>
    <?php endif; ?>
</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>