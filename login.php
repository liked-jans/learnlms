<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(getRoleDashboard($_SESSION['role']));

$error = '';

// Live platform stats
$statsQuery = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM syllabi) as total_syllabi,
        (SELECT COUNT(*) FROM syllabus_topics) as total_topics,
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active') as total_students
");
$stats = $statsQuery ? $statsQuery->fetch_assoc() : [
    'total_syllabi' => 0,
    'total_topics' => 0,
    'total_students' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND status='active'");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            logActivity($user['id'], 'Logged in', 'Authentication');
            redirect(getRoleDashboard($user['role']));
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Login | BlendEd LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --green-dark: #05140d;
    --green-deep: #0b2419;
    --green: #113827;
    --green-mid: #1c5c3f;
    --green-light: #2ebc7f;
    --green-pale: #e7f7ef;
    --accent: #e48547;
    --accent-tint: #fbf1ea;
    --info: #358cd4;
    --white: #FFFFFF;
    --off-white: #f3f6f4;
    --text: #0a130f;
    --text2: #2b3b31;
    --text3: #5b7566;
    --border-color: #dbeae0;
    
    --ease: cubic-bezier(.25, .8, .25, 1);
    --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
    --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
    
    --shadow-sm: 0 2px 8px rgba(5,20,13,0.03);
    --shadow-md: 0 12px 32px rgba(5,20,13,0.06);
    --shadow-lg: 0 24px 48px rgba(5,20,13,0.1);
}

html { -webkit-text-size-adjust: 100%; height: 100%; }
body {
    min-height: 100dvh;
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--off-white);
    color: var(--text);
    display: flex;
    flex-direction: row;
    overflow-x: hidden;
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}

.left-panel {
    flex: 1.35;
    background: var(--green-deep);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: clamp(40px, 5vw, 64px);
    position: relative;
    overflow: hidden;
}
.grid-overlay {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(255,255,255,0.012) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.012) 1px, transparent 1px);
    background-size: 40px 40px;
}
.blob {
    position: absolute; pointer-events: none; filter: blur(100px);
    opacity: 0.18; mix-blend-mode: screen;
}
.blob-1 {
    width: min(600px, 45vw); height: min(600px, 45vw); top: -10%; right: -5%;
    background: var(--green-light);
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    animation: morph 20s infinite alternate ease-in-out;
}
.blob-2 {
    width: min(500px, 35vw); height: min(500px, 35vw); bottom: -10%; left: -5%;
    background: var(--accent);
    border-radius: 70% 30% 50% 50% / 30% 40% 60% 70%;
    animation: morph 26s infinite alternate-reverse ease-in-out;
}
@keyframes morph {
    0% { border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; transform: rotate(0deg) scale(1); }
    50% { border-radius: 60% 40% 30% 70% / 50% 60% 40% 50%; transform: rotate(180deg) scale(1.1); }
    100% { border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; transform: rotate(360deg) scale(1); }
}

.left-content { position: relative; z-index: 1; max-width: 540px; margin: auto 0; }
.brand-mark { display: inline-flex; align-items: center; gap: 16px; margin-bottom: clamp(40px, 5vh, 56px); text-decoration: none; }
.brand-icon {
    width: 56px; height: 56px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: var(--white); flex-shrink: 0;
    box-shadow: var(--shadow-sm);
    transition: transform 0.4s var(--ease-spring);
}
.brand-mark:hover .brand-icon { transform: rotate(8deg) scale(1.08); }
.brand-name { font-family: 'Fraunces', serif; font-size: 34px; font-weight: 700; color: var(--white); letter-spacing: -0.5px; }
.brand-name span { color: var(--green-light); }

.hero-headline {
    font-family: 'Fraunces', serif;
    font-size: clamp(36px, 4.2vw, 56px);
    font-weight: 700; line-height: 1.15;
    color: var(--white); margin-bottom: clamp(16px, 3vh, 28px);
    letter-spacing: -1.2px;
}
.hero-headline em { color: var(--accent); font-style: italic; font-weight: 400; }
.hero-sub {
    font-size: clamp(14.5px, 1.6vw, 17px); color: rgba(255,255,255,0.65);
    line-height: 1.75; max-width: 480px; margin-bottom: clamp(36px, 5vh, 48px);
}

.feature-list { display: flex; flex-direction: column; gap: clamp(12px, 2vh, 18px); }
.feature-item {
    display: flex; align-items: center; gap: 18px;
    padding: 14px 20px; border-radius: 18px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.04);
    transition: all .3s var(--ease);
}
.feature-item:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.12);
    transform: translateX(6px);
}
.feature-dot {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; transition: transform .3s var(--ease-spring);
}
.feature-item:hover .feature-dot { transform: scale(1.1) rotate(-6deg); }
.feature-dot.g { background: rgba(46,188,127,0.18); color: var(--green-light); }
.feature-dot.o { background: rgba(229,138,79,0.18); color: var(--accent); }
.feature-dot.b { background: rgba(59,143,217,0.18); color: #5db0f5; }
.feature-text { font-size: clamp(13.5px, 1.45vw, 15.5px); color: rgba(255,255,255,0.75); font-weight: 500; }

.deco-cards {
    position: relative; z-index: 1;
    display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(12px, 2vw, 20px);
    max-width: 580px; margin-top: auto;
}
.deco-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px; padding: clamp(16px, 2vw, 24px);
    backdrop-filter: blur(16px);
    display: flex; flex-direction: column; gap: 10px;
    transition: all .3s var(--ease);
}
.deco-card:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.18);
    transform: translateY(-6px);
}
.deco-card-icon {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 14px; margin-bottom: 2px;
}
.deco-card-icon.g { background: rgba(46,188,127,0.2); color: var(--green-light); }
.deco-card-icon.o { background: rgba(229,138,79,0.2); color: var(--accent); }
.deco-card-icon.b { background: rgba(59,143,217,0.2); color: #5db0f5; }
.deco-card-label { font-size: clamp(9px, 1vw, 11.5px); color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
.deco-card-value { font-family: 'Fraunces', serif; font-size: clamp(22px, 2.8vw, 30px); font-weight: 700; color: var(--white); line-height: 1; }
.deco-card-sub { font-size: clamp(10.5px, 1vw, 12.5px); color: var(--green-light); font-weight: 500; }

.mobile-brand { display: none; }
.mobile-stats { display: none; }

.right-panel {
    width: min(520px, 48vw); flex-shrink: 0;
    background: var(--white);
    display: flex; flex-direction: column; justify-content: center;
    padding: 60px clamp(40px, 5vw, 64px);
    border-left: 1px solid var(--border-color);
    position: relative;
    overflow-y: auto;
}

.login-box { width: 100%; max-width: 360px; margin: 0 auto; }
.login-header { margin-bottom: clamp(24px, 3.5vh, 36px); }
.login-title {
    font-family: 'Fraunces', serif;
    font-size: clamp(30px, 3.2vw, 36px); font-weight: 700;
    color: var(--text); margin-bottom: 10px;
    letter-spacing: -0.5px;
}
.login-sub { font-size: clamp(13.5px, 1.35vw, 15px); color: var(--text3); line-height: 1.55; }

.role-tabs {
    position: relative;
    display: flex; gap: 4px; margin-bottom: clamp(22px, 3vh, 32px);
    background: var(--off-white); border-radius: 14px; padding: 5px;
    border: 1px solid var(--border-color);
}
.role-tab-indicator {
    position: absolute; top: 5px; bottom: 5px; left: 5px;
    width: calc((100% - 14px) / 3);
    background: var(--white); border-radius: 10px;
    box-shadow: 0 4px 12px rgba(5,20,13,0.06);
    transition: transform .35s var(--ease-spring);
    z-index: 0;
}
.role-tab {
    flex: 1; padding: 12px 4px; border: none; border-radius: 10px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(12px, 1.25vw, 13.5px); font-weight: 700; cursor: pointer;
    color: var(--text3); background: none;
    transition: color .2s var(--ease);
    display: flex; align-items: center; justify-content: center; gap: 6px;
    position: relative; z-index: 1;
}
.role-tab i { font-size: 13px; transition: transform .2s var(--ease); }
.role-tab.active { color: var(--green); }
.role-tab:hover:not(.active) { color: var(--text2); }
.role-tab.active i { transform: scale(1.15); }

.form-group { margin-bottom: 22px; }
.form-label {
    display: block; font-size: 11px; font-weight: 800;
    color: var(--text2); margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.8px;
}
.input-wrap { position: relative; }
.input-icon {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: var(--text3); font-size: 15px; pointer-events: none;
    transition: color .25s var(--ease);
}
.input-wrap:focus-within .input-icon { color: var(--green-light); }
.form-input {
    width: 100%; padding: 14.5px 16px 14.5px 46px;
    border: 1.5px solid var(--border-color); border-radius: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 15px; color: var(--text);
    background: var(--white); outline: none;
    transition: all .25s var(--ease);
}
.form-input:hover { border-color: #bad3c2; }
.form-input:focus { border-color: var(--green-light); box-shadow: 0 0 0 4px rgba(46,188,127,0.12); }
.form-input::placeholder { color: #abbfb2; }

.toggle-pass {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--text3); font-size: 14px;
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s var(--ease);
}
.toggle-pass:hover { color: var(--green-light); background: var(--green-pale); }

.form-meta { display: flex; align-items: center; justify-content: space-between; margin: -4px 0 24px; }
.remember-wrap { display: flex; align-items: center; gap: 8px; }
.remember-wrap input[type="checkbox"] {
    width: 16px; height: 16px; accent-color: var(--green); cursor: pointer;
    border-radius: 4px; border: 1.5px solid var(--border-color);
}
.remember-wrap label { font-size: 13.5px; color: var(--text2); cursor: pointer; user-select: none; }
.forgot-link { font-size: 13.5px; color: var(--green-mid); text-decoration: none; font-weight: 700; transition: color .2s var(--ease); }
.forgot-link:hover { color: var(--green-light); }

.error-box {
    background: #fdf2f2; border: 1px solid #fbd5d5;
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    font-size: 14px; color: #9b1c1c; margin-bottom: 24px;
    animation: shake .4s ease;
}
@keyframes shake {
    0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)}
}

.btn-login {
    width: 100%; padding: 15.5px;
    background: var(--green-deep);
    color: var(--white); border: none; border-radius: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 16px; font-weight: 700; cursor: pointer;
    transition: all .25s var(--ease-out);
    margin-top: 8px;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    box-shadow: 0 4px 12px rgba(12,43,29,0.15);
    position: relative; overflow: hidden;
}
.btn-login i { transition: transform .2s var(--ease); }
.btn-login:hover { background: var(--green); box-shadow: 0 12px 24px rgba(12,43,29,0.25); transform: translateY(-2px); }
.btn-login:hover i { transform: translateX(4px); }
.btn-login:active { transform: translateY(0); box-shadow: 0 4px 12px rgba(12,43,29,0.15); }

.btn-login .spinner {
    width: 18px; height: 18px; border-radius: 50%;
    border: 2.5px solid rgba(255,255,255,0.3); border-top-color: var(--white);
    display: none; animation: spin .6s linear infinite;
}
.btn-login.is-loading .spinner { display: inline-block; }
.btn-login.is-loading .btn-label, .btn-login.is-loading i { display: none; }
@keyframes spin { to { transform: rotate(360deg); } }

.btn-login .ripple {
    position: absolute; border-radius: 50%; background: rgba(255,255,255,0.35);
    transform: scale(0); animation: ripple-anim .6s var(--ease-out);
    pointer-events: none;
}
@keyframes ripple-anim { to { transform: scale(3.5); opacity: 0; } }

.divider { display: flex; align-items: center; gap: 14px; margin: 24px 0; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1.5px; background: var(--border-color); }
.divider span { font-size: 11px; color: var(--text3); font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; }

.login-hint {
    text-align: center; margin-top: 10px;
    font-size: 13.5px; color: var(--text2);
    background: var(--off-white); border-radius: 12px; padding: 14px;
    border: 1px dashed #c0d3c6;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    transition: all .2s var(--ease);
}
.login-hint i { color: var(--green-mid); }
.login-hint span { font-weight: 500; }
.login-hint strong { color: var(--green-deep); font-weight: 700; }

@media (max-width: 1024px) {
    .left-panel { padding: 40px; flex: 1; }
    .hero-headline { font-size: 38px; }
    .right-panel { width: 440px; padding: 40px; }
}

@media (max-width: 850px) {
    body { flex-direction: column; overflow-y: auto; height: auto; min-height: 100dvh; }
    .left-panel { display: none; }
    
    .mobile-brand {
        display: flex; align-items: center; justify-content: center; gap: 12px;
        padding: max(24px, env(safe-area-inset-top)) max(24px, env(safe-area-inset-right)) 16px max(24px, env(safe-area-inset-left));
        background: var(--green-dark);
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .mobile-brand .brand-icon {
        width: 44px; height: 44px; font-size: 18px; border-radius: 12px;
        background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
        display: flex; align-items: center; justify-content: center; color: white;
    }
    .mobile-brand .brand-name { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 700; color: white; }
    .mobile-brand .brand-name span { color: var(--green-light); }

    .mobile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        padding: 16px max(24px, env(safe-area-inset-right)) 16px max(24px, env(safe-area-inset-left));
        background: var(--green-dark);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        box-sizing: border-box;
        width: 100%;
    }
    .mobile-stat {
        display: flex; flex-direction: column; align-items: center; text-align: center;
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 14px 6px;
        gap: 6px;
        box-sizing: border-box;
    }
    .mobile-stat-icon {
        width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 12px; margin-bottom: 2px;
    }
    .mobile-stat-icon.g { background: rgba(46,188,127,0.2); color: var(--green-light); }
    .mobile-stat-icon.o { background: rgba(229,138,79,0.2); color: var(--accent); }
    .mobile-stat-icon.b { background: rgba(59,143,217,0.2); color: #5db0f5; }
    .mobile-stat-value { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 700; color: var(--white); line-height: 1.1; }
    .mobile-stat-label { font-size: 9px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }

    .right-panel {
        width: 100%; flex: 1; border-left: none;
        border-radius: 24px 24px 0 0;
        box-shadow: 0 -12px 36px rgba(5,20,13,0.12);
        margin-top: -12px; 
        padding: 40px max(24px, env(safe-area-inset-right)) max(40px, calc(env(safe-area-inset-bottom) + 24px)) max(24px, env(safe-area-inset-left));
        z-index: 2;
    }
}

@media (max-width: 480px) {
    .login-title { font-size: 26px; }
    .login-sub { font-size: 13.5px; }
    .role-tabs { margin-bottom: 20px; padding: 4px; }
    .role-tab { font-size: 11.5px; padding: 9px 2px; }
    .role-tab span { display: none; }
    .role-tab i { font-size: 15px; }
    .form-input { font-size: 16px; padding: 13px 16px 13px 44px; }
    .mobile-brand { padding: 18px 20px 10px; }
    .mobile-stats { padding: 8px 20px; gap: 8px; }
    .mobile-stat { padding: 10px 4px; border-radius: 12px; gap: 4px; }
    .mobile-stat-icon { width: 28px; height: 28px; font-size: 11px; }
    .mobile-stat-value { font-size: 15px; }
    .mobile-stat-label { font-size: 8px; }
    .right-panel { padding: 32px 18px; border-radius: 20px 20px 0 0; }
}

@media (max-width: 340px) {
    .mobile-stats { gap: 6px; }
    .mobile-stat { padding: 8px 2px; }
    .mobile-stat-value { font-size: 13px; }
    .mobile-stat-label { font-size: 7px; }
    .right-panel { padding: 28px 12px; }
}

@media (max-height: 600px) and (max-width: 960px) {
    body { overflow-y: auto; }
    .mobile-brand { padding: 10px 24px; }
    .mobile-stats { display: none; }
    .right-panel { padding-top: 24px; border-radius: 16px 16px 0 0; }
    .login-header { margin-bottom: 16px; }
    .role-tabs { margin-bottom: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-meta { margin-bottom: 16px; }
}
</style>
</head>
<body>

<div class="left-panel">
    <div class="grid-overlay"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <a href="#" class="brand-mark">
        <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
        <span class="brand-name">Blend<span>Ed</span> LMS</span>
    </a>

    <div class="left-content">
        <h1 class="hero-headline">
            Smart Learning,<br>
            <em>Beautifully</em><br>
            Mapped.
        </h1>
        <p class="hero-sub">
            A complete blended learning platform with intelligent syllabus mapping — connecting face-to-face and online instruction seamlessly.
        </p>

        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-dot g"><i class="fas fa-map"></i></div>
                <span class="feature-text">Visual week-by-week syllabus mapping</span>
            </div>
            <div class="feature-item">
                <div class="feature-dot o"><i class="fas fa-layer-group"></i></div>
                <span class="feature-text">Blended, online & face-to-face delivery modes</span>
            </div>
            <div class="feature-item">
                <div class="feature-dot b"><i class="fas fa-chart-line"></i></div>
                <span class="feature-text">Real-time student progress tracking</span>
            </div>
        </div>
    </div>

    <div class="deco-cards">
        <div class="deco-card">
            <div class="deco-card-icon g"><i class="fas fa-map"></i></div>
            <div class="deco-card-label">Active Syllabi</div>
            <div class="deco-card-value"><?= number_format((int)($stats['total_syllabi'] ?? 0)) ?></div>
            <div class="deco-card-sub">Curriculum outlines</div>
        </div>
        <div class="deco-card">
            <div class="deco-card-icon o"><i class="fas fa-diagram-project"></i></div>
            <div class="deco-card-label">Topics Mapped</div>
            <div class="deco-card-value"><?= number_format((int)($stats['total_topics'] ?? 0)) ?></div>
            <div class="deco-card-sub">Weekly lesson plans</div>
        </div>
        <div class="deco-card">
            <div class="deco-card-icon b"><i class="fas fa-user-graduate"></i></div>
            <div class="deco-card-label">Active Students</div>
            <div class="deco-card-value"><?= number_format((int)($stats['total_students'] ?? 0)) ?></div>
            <div class="deco-card-sub">Enrolled learners</div>
        </div>
    </div>
</div>

<div class="mobile-brand">
    <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
    <span class="brand-name">Blend<span>Ed</span> LMS</span>
</div>

<div class="mobile-stats">
    <div class="mobile-stat">
        <div class="mobile-stat-icon g"><i class="fas fa-map"></i></div>
        <div class="mobile-stat-value"><?= number_format((int)($stats['total_syllabi'] ?? 0)) ?></div>
        <div class="mobile-stat-label">Active Syllabi</div>
    </div>
    <div class="mobile-stat">
        <div class="mobile-stat-icon o"><i class="fas fa-diagram-project"></i></div>
        <div class="mobile-stat-value"><?= number_format((int)($stats['total_topics'] ?? 0)) ?></div>
        <div class="mobile-stat-label">Topics Mapped</div>
    </div>
    <div class="mobile-stat">
        <div class="mobile-stat-icon b"><i class="fas fa-user-graduate"></i></div>
        <div class="mobile-stat-value"><?= number_format((int)($stats['total_students'] ?? 0)) ?></div>
        <div class="mobile-stat-label">Students</div>
    </div>
</div>

<div class="right-panel">
    <div class="login-box">
        <div class="login-header">
            <h2 class="login-title">Welcome back</h2>
            <p class="login-sub">Sign in to your account to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-input" placeholder="Enter your username" required autocomplete="username" autofocus>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="passInput" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="toggle-pass" onclick="togglePass()">
                        <i class="fas fa-eye" id="passIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-meta">
                <div class="remember-wrap">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="index.php" class="forgot-link">Back</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span class="spinner"></span>
                <i class="fas fa-sign-in-alt"></i>
                <span class="btn-label">Sign In</span>
            </button>
        </form>

<script>
function togglePass() {
    const inp = document.getElementById('passInput');
    const ico = document.getElementById('passIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'fas fa-eye';
    }
}

const loginBtn = document.getElementById('loginBtn');
loginBtn.addEventListener('click', function (e) {
    const rect = this.getBoundingClientRect();
    const ripple = document.createElement('span');
    const size = Math.max(rect.width, rect.height);
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
    this.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
});

document.getElementById('loginForm').addEventListener('submit', function () {
    loginBtn.classList.add('is-loading');
});
</script>
</body>
</html>