<?php
/**
 * BlendEd LMS — Maintenance Mode
 * Sends a proper 503 so search engines / uptime monitors know this is temporary.
 */
header("HTTP/1.1 503 Service Unavailable");
header("Retry-After: 3600"); // hint: retry in 1 hour, adjust as needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, follow">
<title>Under Maintenance — BlendEd LMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;1,500;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg-deep:#081911;
    --bg-mid:#0d2419;
    --bg-soft:#12301f;
    --green:#3ecf8e;
    --green-dim:#1f5c3f;
    --orange:#ea8a4a;
    --orange-dim:#7a4a28;
    --ink:#f4f3ee;
    --muted:#9db3a6;
    --line: rgba(244,243,238,0.10);
    --card: rgba(255,255,255,0.035);
  }

  *{box-sizing:border-box;}
  html,body{height:100%;}
  body{
    margin:0;
    min-height:100vh;
    background: var(--bg-mid);
    font-family:'Plus Jakarta Sans', sans-serif;
    color:var(--ink);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:32px 20px;
    position:relative;
    overflow:hidden;
  }

  /* faint dot-grid texture like the hero screenshots */
  body::before{
    content:"";
    position:fixed; inset:0;
    background-image: radial-gradient(rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size:26px 26px;
    mask-image: radial-gradient(ellipse 80% 60% at 50% 35%, black 40%, transparent 90%);
    pointer-events:none;
  }

  /* ---------- blobs ---------- */
  .blob{
    position:fixed;
    border-radius:50%;
    filter:blur(70px);
    opacity:0.55;
    pointer-events:none;
    z-index:0;
  }
  .blob-1{
    width:460px;height:460px;
    background: var(--green);
    top:-160px; left:-140px;
    animation: drift1 22s ease-in-out infinite;
  }
  .blob-2{
    width:420px;height:420px;
    background: var(--orange);
    bottom:-180px; right:-120px;
    opacity:0.35;
    animation: drift2 26s ease-in-out infinite;
  }
  .blob-3{
    width:300px;height:300px;
    background: var(--green);
    bottom:10%; left:6%;
    opacity:0.18;
    animation: drift1 18s ease-in-out infinite reverse;
  }
  @keyframes drift1{
    0%,100%{ transform:translate(0,0) scale(1); }
    50%{ transform:translate(40px,30px) scale(1.08); }
  }
  @keyframes drift2{
    0%,100%{ transform:translate(0,0) scale(1); }
    50%{ transform:translate(-30px,-40px) scale(1.1); }
  }

  /* ---------- brand ---------- */
  .brand{
    position:relative; z-index:2;
    display:flex; align-items:center; gap:12px;
    margin-bottom:38px;
  }
  .logo-mark{
    width:44px; height:44px;
    border-radius:13px;
    background:#0f2b1d;
    border:1px solid rgba(255,255,255,0.08);
    display:flex; align-items:center; justify-content:center;
    animation: markPulse 3.2s ease-in-out infinite;
  }
  @keyframes markPulse{
    0%,100%{ box-shadow:0 0 0 0 rgba(62,207,142,0.0); }
    50%{ box-shadow:0 0 0 8px rgba(62,207,142,0.06); }
  }
  .logo-mark svg{ width:22px; height:22px; }
  .wordmark{ font-family:'Fraunces', serif; font-weight:600; font-size:1.35rem; letter-spacing:-0.01em; }
  .wordmark .ed{ color:var(--green); }

  /* ---------- card ---------- */
  .card{
    position:relative; z-index:2;
    width:100%; max-width:720px;
    background:var(--card);
    border:1px solid var(--line);
    border-radius:24px;
    padding:48px 44px 40px;
    backdrop-filter: blur(18px);
    text-align:center;
    box-shadow:0 30px 80px -30px rgba(0,0,0,0.6);
  }
  @media (max-width:560px){
    .card{ padding:36px 22px 30px; border-radius:18px; }
  }

  .badge{
    display:inline-flex; align-items:center; gap:8px;
    font-size:0.72rem; letter-spacing:0.09em; text-transform:uppercase;
    color:var(--orange);
    background:rgba(234,138,74,0.09);
    border:1px solid rgba(234,138,74,0.22);
    padding:6px 14px 6px 10px;
    border-radius:999px;
    margin-bottom:22px;
    font-weight:600;
  }
  .dot-pulse{
    width:7px; height:7px; border-radius:50%;
    background:var(--orange);
    box-shadow:0 0 0 0 rgba(234,138,74,0.6);
    animation: dotPulse 1.6s ease-out infinite;
  }
  @keyframes dotPulse{
    0%{ box-shadow:0 0 0 0 rgba(234,138,74,0.55); }
    70%{ box-shadow:0 0 0 9px rgba(234,138,74,0); }
    100%{ box-shadow:0 0 0 0 rgba(234,138,74,0); }
  }

  h1{
    font-family:'Fraunces', serif;
    font-weight:600;
    font-size:clamp(1.7rem, 4.2vw, 2.55rem);
    line-height:1.12;
    letter-spacing:-0.015em;
    margin:0 0 16px;
  }
  h1 em{
    font-style:italic;
    font-weight:500;
    color:var(--orange);
  }

  .sub{
    color:var(--muted);
    font-size:1rem;
    line-height:1.65;
    max-width:520px;
    margin:0 auto 34px;
  }

  /* ---------- roadmap illustration (signature element) ---------- */
  .roadmap-wrap{
    margin:0 auto 30px;
    max-width:600px;
  }
  .roadmap-wrap svg{ width:100%; height:auto; display:block; }

  .rm-path{
    fill:none;
    stroke:rgba(244,243,238,0.14);
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-dasharray:6 10;
  }
  .rm-path-progress{
    fill:none;
    stroke:var(--green);
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-dasharray:480;
    stroke-dashoffset:480;
    animation: drawPath 2.6s cubic-bezier(.65,0,.35,1) forwards, glowLine 3s ease-in-out 2.6s infinite;
    opacity:0.9;
  }
  @keyframes drawPath{
    to{ stroke-dashoffset:0; }
  }
  @keyframes glowLine{
    0%,100%{ filter:drop-shadow(0 0 0px rgba(62,207,142,0)); }
    50%{ filter:drop-shadow(0 0 5px rgba(62,207,142,0.55)); }
  }

  .rm-node{ transition:opacity .3s; }
  .rm-node circle.ring{
    fill:none;
    stroke:var(--green-dim);
    stroke-width:2;
  }
  .rm-node circle.core{
    fill:var(--bg-mid);
    stroke:var(--green);
    stroke-width:2;
  }
  .rm-node.done circle.core{ fill:var(--green); }
  .rm-node.active circle.ring{
    stroke:var(--orange);
    animation: ringPulse 1.8s ease-in-out infinite;
  }
  .rm-node.active circle.core{
    fill:var(--orange);
    stroke:var(--orange);
  }
  @keyframes ringPulse{
    0%{ transform:scale(1); opacity:0.9; }
    70%{ transform:scale(1.7); opacity:0; }
    100%{ opacity:0; }
  }
  .rm-node.upcoming circle.core{ fill:transparent; stroke:rgba(244,243,238,0.22); }
  .rm-label{
    font-family:'Plus Jakarta Sans', sans-serif;
    font-size:9.5px;
    fill:var(--muted);
    letter-spacing:0.03em;
  }
  .rm-tool{
    animation: toolTurn 3.4s ease-in-out infinite;
    transform-origin:center;
  }
  @keyframes toolTurn{
    0%,100%{ transform:rotate(-14deg); }
    50%{ transform:rotate(14deg); }
  }

  /* ---------- progress ---------- */
  .status-row{
    display:flex; flex-direction:column; align-items:center; gap:10px;
    margin-bottom:36px;
  }
  .progress-wrap{
    width:100%; max-width:280px; height:6px;
    background:rgba(244,243,238,0.08);
    border-radius:999px;
    overflow:hidden;
    position:relative;
  }
  .progress-fill{
    position:absolute; inset:0;
    width:40%;
    border-radius:999px;
    background: var(--green);
    animation: barSlide 2.4s ease-in-out infinite;
  }
  @keyframes barSlide{
    0%{ left:-40%; }
    50%{ left:60%; }
    100%{ left:100%; }
  }
  .status-text{ font-size:0.82rem; color:var(--muted); }
  .status-text b{ color:var(--ink); font-weight:600; }

  /* ---------- actions ---------- */
  .actions{
    display:flex; align-items:center; justify-content:center;
    gap:14px; flex-wrap:wrap;
  }
  .btn-back{
    display:inline-flex; align-items:center; gap:8px;
    background:var(--ink);
    color:#0b2418;
    font-weight:700;
    font-size:0.92rem;
    text-decoration:none;
    padding:12px 22px;
    border-radius:12px;
    transition:transform .18s ease, box-shadow .18s ease;
  }
  .btn-back:hover{ transform:translateY(-2px); box-shadow:0 10px 26px -8px rgba(244,243,238,0.35); }
  .btn-back svg{ width:16px; height:16px; }

  .btn-ghost{
    display:inline-flex; align-items:center; gap:8px;
    background:transparent;
    color:var(--ink);
    font-weight:600;
    font-size:0.92rem;
    text-decoration:none;
    padding:11px 20px;
    border-radius:12px;
    border:1px solid var(--line);
    transition:border-color .18s ease, background .18s ease;
  }
  .btn-ghost:hover{ border-color:rgba(244,243,238,0.3); background:rgba(255,255,255,0.03); }

  footer{
    position:relative; z-index:2;
    margin-top:26px;
    font-size:0.78rem;
    color:rgba(157,179,166,0.6);
    text-align:center;
  }

  @media (prefers-reduced-motion: reduce){
    *{ animation:none !important; transition:none !important; }
  }
</style>
</head>
<body>

  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <div class="brand">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 3L21 8L12 13L3 8L12 3Z" fill="#3ecf8e"/>
        <path d="M3 12L12 17L21 12" stroke="#3ecf8e" stroke-width="1.8" stroke-linecap="round" opacity="0.55"/>
        <path d="M3 16L12 21L21 16" stroke="#3ecf8e" stroke-width="1.8" stroke-linecap="round" opacity="0.3"/>
      </svg>
    </div>
    <div class="wordmark">Blend<span class="ed">Ed</span> LMS</div>
  </div>

  <main class="card">
    <span class="badge"><span class="dot-pulse"></span>Scheduled Maintenance</span>

    <h1>Currently <em>Remapping</em><br>Your Learning Path.</h1>
    <p class="sub">
      We're rolling out improvements to the syllabus engine that powers BlendEd LMS.
      Your courses, grades, and content are safe — we'll be back online shortly.
    </p>

    <div class="roadmap-wrap">
      <svg viewBox="0 0 600 120" aria-hidden="true">
        <path class="rm-path" d="M20,80 C120,20 180,20 220,60 S320,110 360,70 S460,20 500,55 S560,80 580,60" />
        <path class="rm-path-progress" d="M20,80 C120,20 180,20 220,60 S320,110 360,70 S460,20 500,55 S560,80 580,60" />

        <g class="rm-node done">
          <circle class="core" cx="20" cy="80" r="6"/>
          <text class="rm-label" x="20" y="102" text-anchor="middle">Week 1</text>
        </g>
        <g class="rm-node done">
          <circle class="core" cx="220" cy="60" r="6"/>
          <text class="rm-label" x="220" y="82" text-anchor="middle">Week 2</text>
        </g>
        <g class="rm-node active">
          <circle class="ring" cx="360" cy="70" r="6"/>
          <circle class="core" cx="360" cy="70" r="6"/>
          <text class="rm-label" x="360" y="30" text-anchor="middle" fill="#ea8a4a" font-weight="600">Updating</text>
          <g class="rm-tool" transform="translate(360,70)">
            <path d="M-3,-16 L3,-16 L3,-11 L-3,-11 Z" fill="#ea8a4a"/>
            <rect x="-1.4" y="-11" width="2.8" height="9" fill="#ea8a4a"/>
          </g>
        </g>
        <g class="rm-node upcoming">
          <circle class="core" cx="500" cy="55" r="6"/>
          <text class="rm-label" x="500" y="77" text-anchor="middle">Week 4</text>
        </g>
        <g class="rm-node upcoming">
          <circle class="core" cx="580" cy="60" r="6"/>
          <text class="rm-label" x="580" y="82" text-anchor="middle">Week 5</text>
        </g>
      </svg>
    </div>

    <div class="status-row">
      <div class="progress-wrap"><div class="progress-fill"></div></div>
      <div class="status-text">Estimated return: <b>shortly</b> — thanks for your patience</div>
    </div>

    <div class="actions">
      <a href="index.php" class="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Back to Home
      </a>
      <a href="mailto:support@blendedlms.com" class="btn-ghost">Contact Support</a>
    </div>
  </main>

  <footer>&copy; <?php echo date("Y"); ?> BlendEd LMS. All systems will resume automatically.</footer>

</body>
</html>