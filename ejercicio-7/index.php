<?php
$resultado = null;
$tiempo    = null;
$texto     = $_POST["texto"] ?? "";
$error     = "";

if (isset($_POST["analizar"]) && !empty($texto)) {

    $datos  = json_encode(["texto" => $texto]);
    $inicio = microtime(true);

    $curl = curl_init("http://localhost:5000/analizar");
    curl_setopt($curl, CURLOPT_POST,           true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,     $datos);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,     ["Content-Type: application/json"]);
    curl_setopt($curl, CURLOPT_TIMEOUT,        90);
    $respuesta_raw = curl_exec($curl);
    curl_close($curl);

    $tiempo = round(microtime(true) - $inicio, 2);

    if ($respuesta_raw) {
        $resultado = json_decode($respuesta_raw, true);
        if (isset($resultado["error"])) {
            $error = $resultado["error"];
            $resultado = null;
        }
    } else {
        $error = "No se pudo conectar con el servidor Python. ¿Está corriendo app.py?";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stack PHP + Python · Marcos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0a0c13;
            --surface: #101320;
            --border:  #1a1f30;
            --php:     #7c3aed;
            --python:  #f59e0b;
            --accent:  #06b6d4;
            --green:   #10b981;
            --text:    #e8eaf0;
            --muted:   #505870;
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
                radial-gradient(circle at 10% 30%, rgba(124,58,237,.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 70%, rgba(245,158,11,.04) 0%, transparent 50%);
        }

        header { text-align: center; margin-bottom: 36px; animation: fadeDown .5s ease both; }

        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(6,182,212,.08);
            border: 1px solid rgba(6,182,212,.2);
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

        /* Diagrama de arquitectura */
        .arquitectura {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; flex-wrap: wrap; margin-bottom: 36px;
        }

        .arq-bloque {
            border-radius: 12px; padding: 10px 18px;
            font-family: 'Syne', sans-serif; font-size: 12px;
            font-weight: 700; letter-spacing: .06em; text-align: center;
        }

        .arq-php    { background: rgba(124,58,237,.12); border: 1px solid rgba(124,58,237,.3); color: #a78bfa; }
        .arq-python { background: rgba(245,158,11,.10); border: 1px solid rgba(245,158,11,.3); color: #fbbf24; }
        .arq-ollama { background: rgba(16,185,129,.10); border: 1px solid rgba(16,185,129,.3); color: #34d399; }
        .arq-arrow  { color: var(--muted); font-size: 18px; }

        .arq-bloque small {
            display: block; font-size: 10px;
            font-weight: 400; opacity: .7; margin-top: 2px;
        }

        /* Card principal */
        .card {
            width: 100%; max-width: 760px;
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
            font-size: 14px; font-weight: 300; line-height: 1.7;
            padding: 16px; resize: vertical; min-height: 160px;
            outline: none; transition: border-color .2s;
        }

        textarea:focus { border-color: rgba(6,182,212,.4); }
        textarea::placeholder { color: var(--muted); }

        .btn-row { margin-top: 16px; display: flex; justify-content: flex-end; }

        button[type="submit"] {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--accent); color: #0a0c13;
            font-family: 'Syne', sans-serif; font-weight: 700;
            font-size: 13px; letter-spacing: .05em;
            padding: 12px 28px; border: none; border-radius: 100px;
            cursor: pointer; transition: transform .15s, box-shadow .15s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6,182,212,.3);
        }

        .divider { height: 1px; background: var(--border); margin: 28px 0; }

        /* Error */
        .error-box {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 12px; padding: 14px 18px;
            color: #f87171; font-size: 14px;
        }

        /* Dashboard de resultados */
        .dashboard-title {
            font-family: 'Syne', sans-serif; font-size: 11px;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 16px;
        }

        /* Estadísticas en grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px; padding: 16px;
            text-align: center;
        }

        .stat-numero {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            color: var(--accent); line-height: 1;
        }

        .stat-label {
            margin-top: 6px; font-size: 11px;
            color: var(--muted); letter-spacing: .04em;
        }

        /* Idioma detectado */
        .idioma-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--python);
            border-radius: 12px; padding: 16px 20px;
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .idioma-nombre {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 700;
            color: #fbbf24;
        }

        .idioma-label { font-size: 11px; color: var(--muted); margin-bottom: 4px; }

        .confianza-bar {
            width: 120px; height: 6px;
            background: var(--border); border-radius: 100px; overflow: hidden;
        }

        .confianza-fill {
            height: 100%; background: var(--python); border-radius: 100px;
            transition: width .8s ease;
        }

        .confianza-texto { font-size: 11px; color: #fbbf24; margin-top: 4px; }

        /* Resumen IA */
        .resumen-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--green);
            border-radius: 12px; padding: 18px 20px;
            font-size: 15px; line-height: 1.75;
            white-space: pre-wrap;
        }

        /* Etiquetas PHP / Python */
        .origin-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-family: 'Syne', sans-serif; font-size: 10px;
            letter-spacing: .08em; text-transform: uppercase;
            padding: 3px 10px; border-radius: 100px;
            margin-bottom: 10px;
        }

        .tag-php    { background: rgba(124,58,237,.12); color: #a78bfa; border: 1px solid rgba(124,58,237,.2); }
        .tag-python { background: rgba(245,158,11,.10); color: #fbbf24; border: 1px solid rgba(245,158,11,.2); }
        .tag-ollama { background: rgba(16,185,129,.10);  color: #34d399; border: 1px solid rgba(16,185,129,.2); }

        .timer-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(0,153,255,.08); border: 1px solid rgba(0,153,255,.18);
            color: #0099ff; font-size: 11px; font-family: 'Syne', sans-serif;
            padding: 3px 10px; border-radius: 100px; margin-top: 12px;
        }

        footer { margin-top: 44px; color: var(--muted); font-size: 12px; text-align: center; }
        footer span { color: var(--accent); }

        @keyframes fadeDown { from { opacity:0; transform: translateY(-14px); } to { opacity:1; transform: translateY(0); } }
        @keyframes fadeUp   { from { opacity:0; transform: translateY(14px);  } to { opacity:1; transform: translateY(0); } }
    </style>
</head>
<body>

<header>
    <div class="badge">⚙️ Stack · PHP + Python</div>
    <h1>Analizador de Texto con IA</h1>
    <p class="subtitle">PHP y Python trabajando juntos para analizar y resumir cualquier texto.</p>
</header>

<!-- Diagrama de arquitectura -->
<div class="arquitectura">
    <div class="arq-bloque arq-php">
        🌐 PHP
        <small>Frontend · Formulario</small>
    </div>
    <div class="arq-arrow">→</div>
    <div class="arq-bloque arq-python">
        🐍 Python
        <small>Flask · Análisis</small>
    </div>
    <div class="arq-arrow">→</div>
    <div class="arq-bloque arq-ollama">
        🧠 Ollama
        <small>IA · Resumen</small>
    </div>
    <div class="arq-arrow">→</div>
    <div class="arq-bloque arq-php">
        🌐 PHP
        <small>Dashboard · Resultados</small>
    </div>
</div>

<div class="card">
    <form method="POST">
        <div class="step-label">Pega aquí el texto a analizar</div>
        <textarea name="texto"
            placeholder="Pega cualquier texto aquí: un artículo, un correo, un párrafo de un libro... Python lo analizará y la IA lo resumirá."
        ><?php echo htmlspecialchars($texto); ?></textarea>

        <div class="btn-row">
            <button type="submit" name="analizar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Analizar texto
            </button>
        </div>
    </form>

    <?php if (!empty($error)): ?>
        <div class="divider"></div>
        <div class="error-box">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($resultado): ?>
    <div class="divider"></div>

    <!-- ESTADÍSTICAS — calculadas por Python -->
    <div class="origin-tag tag-python">🐍 Python · Estadísticas</div>
    <div class="dashboard-title">Análisis del texto</div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-numero"><?php echo $resultado["estadisticas"]["palabras"]; ?></div>
            <div class="stat-label">Palabras</div>
        </div>
        <div class="stat-card">
            <div class="stat-numero"><?php echo $resultado["estadisticas"]["frases"]; ?></div>
            <div class="stat-label">Frases</div>
        </div>
        <div class="stat-card">
            <div class="stat-numero"><?php echo $resultado["estadisticas"]["caracteres"]; ?></div>
            <div class="stat-label">Caracteres</div>
        </div>
        <div class="stat-card">
            <div class="stat-numero"><?php echo $resultado["estadisticas"]["media_palabras_por_frase"]; ?></div>
            <div class="stat-label">Palabras / frase</div>
        </div>
    </div>

    <!-- IDIOMA — detectado por Python -->
    <div class="origin-tag tag-python">🐍 Python · Detección de idioma</div>
    <div class="idioma-box">
        <div>
            <div class="idioma-label">Idioma detectado</div>
            <div class="idioma-nombre"><?php echo htmlspecialchars($resultado["idioma"]["idioma"]); ?></div>
        </div>
        <div style="text-align:right">
            <div class="idioma-label">Confianza</div>
            <div class="confianza-bar">
                <div class="confianza-fill" style="width:<?php echo $resultado['idioma']['confianza']; ?>%"></div>
            </div>
            <div class="confianza-texto"><?php echo $resultado["idioma"]["confianza"]; ?>%</div>
        </div>
    </div>

    <!-- RESUMEN — generado por Ollama vía Python -->
    <div class="origin-tag tag-ollama">🧠 Ollama · Resumen con IA</div>
    <div class="resumen-box"><?php echo htmlspecialchars($resultado["resumen"]); ?></div>

    <?php if ($tiempo): ?>
        <div class="timer-pill">⏱ <?php echo $tiempo; ?>s total (PHP → Python → Ollama → PHP)</div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<footer>
    Desarrollado por <span>Marcos</span> · DAW2 · Diseño de Interfaces Web IA
</footer>

</body>
</html>