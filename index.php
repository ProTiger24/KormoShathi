<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';

$dashboardUrl = ($userType === 'hr')
    ? 'hr-dashboard.php'
    : 'employee-dashboard.php';

$dashboardUrl = $isLoggedIn ? $dashboardUrl : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>KormoShathi | Smart HR Management Platform</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary: #3867F4;
    --primary-dark: #2451DB;
    --navy: #101D35;
    --navy-light: #1A3153;
    --text: #192A44;
    --muted: #718096;
    --bg: #F6F8FC;
    --white: #FFFFFF;
    --border: #E9EDF5;
    --shadow: 0 20px 60px rgba(20, 40, 80, .09);
    --gradient: linear-gradient(135deg, #3867F4, #7657EF);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

html { scroll-behavior: smooth; scroll-padding-top: 90px; }

body {
    font-family: 'DM Sans', sans-serif;
    color: var(--text);
    background: white;
    overflow-x: hidden;
}

h1, h2, h3, h4, h5 { font-family: 'Plus Jakarta Sans', sans-serif; }
a { text-decoration: none; }

/* ================= 3D HELPERS ================= */

/* Any element with data-tilt rotates toward the mouse.
   Children with data-depth="N" pop out by N pixels on the Z axis. */
[data-tilt] {
    transform-style: preserve-3d;
    transform: perspective(1000px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
    transition: transform .25s ease-out, box-shadow .4s, border-color .4s;
    will-change: transform;
}
[data-tilt].is-hover { transition: transform .08s linear, box-shadow .4s, border-color .4s; }
[data-depth] { transform: translateZ(calc(var(--z, 40) * 1px)); }

/* ================= NAVBAR ================= */

.navbar {
    padding: 17px 0;
    background: rgba(255,255,255,.88);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid rgba(230,235,245,.7);
    transition: .3s;
}
.navbar.scrolled { padding: 10px 0; box-shadow: 0 5px 25px rgba(20,40,80,.08); }

.navbar-brand { display: flex; align-items: center; gap: 11px; perspective: 400px; }

.brand-icon {
    height: 43px;
    width: 43px;
    display: grid;
    place-items: center;
    background: var(--gradient);
    color: white;
    border-radius: 13px;
    font-size: 21px;
    box-shadow: 0 8px 20px rgba(56,103,244,.3);
    transition: transform .6s;
    transform-style: preserve-3d;
}
.navbar-brand:hover .brand-icon { transform: rotateY(360deg); }

.brand-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 23px;
    color: var(--navy);
    letter-spacing: -.8px;
}

.nav-link {
    font-size: 14px;
    font-weight: 700;
    color: #53627A !important;
    margin: 0 5px;
    padding: 10px 13px !important;
    transition: .3s;
}
.nav-link:hover, .nav-link.active { color: var(--primary) !important; }

.btn-primary-custom {
    background: var(--gradient);
    color: white !important;
    padding: 12px 24px;
    border-radius: 11px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    transition: .3s;
    /* 3D button: solid "depth" edge under the face */
    box-shadow: 0 5px 0 #2447b8, 0 14px 26px rgba(56,103,244,.3);
}
.btn-primary-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 0 #2447b8, 0 20px 34px rgba(56,103,244,.35);
    color: white;
}
.btn-primary-custom:active {
    transform: translateY(3px);
    box-shadow: 0 2px 0 #2447b8, 0 6px 12px rgba(56,103,244,.3);
}

.btn-outline-custom {
    padding: 12px 24px;
    border-radius: 11px;
    border: 1px solid #DCE3F0;
    background: white;
    color: var(--navy);
    font-size: 14px;
    font-weight: 700;
    transition: .3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    box-shadow: 0 5px 0 #DCE3F0;
}
.btn-outline-custom:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-3px);
    box-shadow: 0 8px 0 #C7D5FF;
}
.btn-outline-custom:active { transform: translateY(3px); box-shadow: 0 2px 0 #DCE3F0; }

/* ================= HERO ================= */

.hero {
    min-height: 780px;
    padding: 150px 0 130px;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 10% 20%, rgba(74,112,245,.10), transparent 30%),
        radial-gradient(circle at 90% 80%, rgba(119,83,238,.10), transparent 30%),
        #F8FAFF;
}

/* 3D floor grid */
.hero-floor {
    position: absolute;
    left: -25%;
    right: -25%;
    bottom: -120px;
    height: 460px;
    background-image:
        linear-gradient(rgba(56,103,244,.16) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,103,244,.16) 1px, transparent 1px);
    background-size: 60px 60px;
    transform: perspective(500px) rotateX(62deg);
    transform-origin: bottom;
    -webkit-mask-image: linear-gradient(to top, #000 15%, transparent 85%);
    mask-image: linear-gradient(to top, #000 15%, transparent 85%);
    animation: floorMove 7s linear infinite;
    pointer-events: none;
}
@keyframes floorMove { to { background-position: 0 60px, 60px 0; } }

/* Floating 3D cubes in hero background */
.bg-cube {
    position: absolute;
    width: var(--s);
    height: var(--s);
    transform-style: preserve-3d;
    animation: cubeSpin var(--t) linear infinite;
    pointer-events: none;
    z-index: 1;
}
.bg-cube i {
    position: absolute;
    inset: 0;
    border: 1.5px solid rgba(56,103,244,.35);
    background: rgba(56,103,244,.06);
    border-radius: 6px;
}
.bg-cube i:nth-child(1) { transform: translateZ(calc(var(--s) / 2)); }
.bg-cube i:nth-child(2) { transform: rotateY(180deg) translateZ(calc(var(--s) / 2)); }
.bg-cube i:nth-child(3) { transform: rotateY(90deg) translateZ(calc(var(--s) / 2)); }
.bg-cube i:nth-child(4) { transform: rotateY(-90deg) translateZ(calc(var(--s) / 2)); }
.bg-cube i:nth-child(5) { transform: rotateX(90deg) translateZ(calc(var(--s) / 2)); }
.bg-cube i:nth-child(6) { transform: rotateX(-90deg) translateZ(calc(var(--s) / 2)); }
@keyframes cubeSpin {
    from { transform: rotateX(0) rotateY(0) translateY(0); }
    50%  { transform: rotateX(180deg) rotateY(180deg) translateY(-25px); }
    to   { transform: rotateX(360deg) rotateY(360deg) translateY(0); }
}
.bg-cube.c1 { --s: 56px; --t: 18s; top: 16%; left: 6%; }
.bg-cube.c2 { --s: 34px; --t: 13s; top: 62%; left: 44%; }
.bg-cube.c3 { --s: 72px; --t: 24s; top: 12%; right: 5%; }
.bg-cube.c4 { --s: 40px; --t: 16s; bottom: 14%; right: 14%; }

.hero-glow {
    position: absolute;
    width: 400px;
    height: 400px;
    background: #527BFF;
    opacity: .10;
    filter: blur(100px);
    border-radius: 50%;
    top: 20%;
    right: 10%;
}

.hero-content { position: relative; z-index: 5; }

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: #EAF0FF;
    color: var(--primary);
    border-radius: 30px;
    padding: 9px 16px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .5px;
    margin-bottom: 25px;
}
.hero-badge span {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #22C55E;
    box-shadow: 0 0 0 4px rgba(34,197,94,.12);
}

.hero-title {
    font-size: clamp(38px, 4.5vw, 62px);
    font-weight: 800;
    line-height: 1.17;
    letter-spacing: -2.5px;
    color: var(--navy);
    margin-bottom: 25px;
    /* layered 3D text depth */
    text-shadow: 0 1px 0 #d5dcef, 0 2px 0 #cbd3e8, 0 3px 0 #c1cae2, 0 10px 18px rgba(20,40,80,.12);
}
.hero-title span {
    display: block;
    background: var(--gradient);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: none;
    filter: drop-shadow(0 4px 0 rgba(36,71,184,.25)) drop-shadow(0 12px 14px rgba(56,103,244,.25));
}

.hero-description {
    max-width: 500px;
    color: var(--muted);
    font-size: 16px;
    line-height: 1.9;
    margin-bottom: 32px;
}

.hero-buttons { display: flex; gap: 14px; flex-wrap: wrap; }

.hero-note {
    margin-top: 32px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}
.hero-note i { color: #22B879; }

/* ================= 3D DASHBOARD SCENE ================= */

.hero-visual {
    position: relative;
    perspective: 1600px;
    padding: 40px 0;
}

.scene {
    position: relative;
    transform-style: preserve-3d;
    transform: rotateY(var(--sy, -14deg)) rotateX(var(--sx, 6deg));
    transition: transform .25s ease-out;
}
.hero-visual.is-hover .scene { transition: transform .08s linear; }

/* soft shadow on the "floor" under the scene */
.scene-shadow {
    position: absolute;
    left: 8%;
    right: 8%;
    bottom: -42px;
    height: 40px;
    background: radial-gradient(ellipse, rgba(17,35,75,.32), transparent 70%);
    filter: blur(12px);
    transform: translateZ(-80px);
}

/* Back plate gives the dashboard visible thickness */
.dashboard-back {
    position: absolute;
    inset: 14px -14px -14px 14px;
    border-radius: 22px;
    background: linear-gradient(135deg, #3867F4, #7657EF);
    opacity: .28;
    transform: translateZ(-60px);
}
.dashboard-mid {
    position: absolute;
    inset: 7px -7px -7px 7px;
    border-radius: 21px;
    background: #263A5B;
    opacity: .55;
    transform: translateZ(-30px);
}

.dashboard-window {
    position: relative;
    background: white;
    border: 7px solid #263A5B;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: 0 35px 70px rgba(17,35,75,.22), 0 10px 25px rgba(17,35,75,.10);
    transform: translateZ(0);
}

.window-header {
    height: 40px;
    background: #F5F7FB;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 6px;
}
.window-dot { height: 8px; width: 8px; border-radius: 50%; background: #D5DCE8; }
.window-title { font-size: 9px; font-weight: 800; color: #7B8798; margin-left: 12px; }

.dashboard-body { display: flex; min-height: 350px; }

.dash-sidebar {
    width: 125px;
    flex-shrink: 0;
    background: #14243D;
    padding: 18px 10px;
    color: white;
}
.dash-logo { font-family: 'Plus Jakarta Sans'; font-size: 12px; font-weight: 800; margin-bottom: 28px; }
.dash-menu { padding: 9px 8px; font-size: 8px; color: #A8B8D0; border-radius: 5px; margin-bottom: 5px; }
.dash-menu.active { background: #3867F4; color: white; }
.dash-menu i { width: 17px; }

.dash-main { flex: 1; padding: 20px 15px; min-width: 0; background: #F8FAFD; }

.dash-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.dash-heading h6 { font-size: 13px; font-weight: 800; margin: 0; color: var(--navy); }
.dash-heading span { font-size: 8px; color: var(--muted); }

.dash-stat-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 9px; }
.dash-stat { background: white; border: 1px solid #EDF0F5; border-radius: 8px; padding: 12px 10px; }
.dash-stat small { display: block; font-size: 8px; color: #8994A5; margin-bottom: 6px; }
.dash-stat strong { font-family: 'Plus Jakarta Sans'; font-size: 19px; color: #1B2D49; }
.dash-stat i { float: right; font-size: 12px; padding: 7px; border-radius: 7px; }
.dash-stat:nth-child(1) i { background: #EAF0FF; color: #3867F4; }
.dash-stat:nth-child(2) i { background: #E7F9F0; color: #19A76B; }
.dash-stat:nth-child(3) i { background: #FFF5E7; color: #E59A28; }
.dash-stat:nth-child(4) i { background: #F1EFFF; color: #8065DD; }

.dash-bottom { display: grid; grid-template-columns: 1.2fr 1fr; gap: 9px; margin-top: 10px; }
.dash-panel { background: white; padding: 12px; border-radius: 8px; border: 1px solid #EDF0F5; }
.dash-panel h6 { font-size: 9px; font-weight: 800; margin-bottom: 12px; }

.chart-bars {
    height: 85px;
    display: flex;
    align-items: end;
    justify-content: space-around;
    gap: 6px;
    border-bottom: 1px solid #EDF0F5;
}
.chart-bars span {
    width: 13px;
    border-radius: 4px 4px 0 0;
    background: linear-gradient(180deg,#628BFF,#3867F4);
    animation: barGrow 1.4s ease backwards;
}
@keyframes barGrow { from { height: 0; } }

.dash-activity { font-size: 8px; color: #718096; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
.activity-dot { width: 6px; height: 6px; background: #22C55E; border-radius: 50%; flex-shrink: 0; }

/* Floating widgets sit at different Z depths */
.float-widget {
    position: absolute;
    z-index: 5;
    background: white;
    border: 1px solid #EEF1F6;
    border-radius: 13px;
    padding: 13px 17px;
    box-shadow: 0 18px 45px rgba(20,40,80,.16);
    display: flex;
    align-items: center;
    gap: 12px;
}
.float-widget.one { top: -6px; right: -34px; transform: translateZ(110px); animation: floatUp 4s ease-in-out infinite; }
.float-widget.two { bottom: -6px; left: -44px; transform: translateZ(140px); animation: floatUp 4s ease-in-out infinite -2s; }
.float-widget.three { top: 44%; right: -52px; transform: translateZ(80px); animation: floatUp 5s ease-in-out infinite -1s; padding: 10px 14px; }

.float-icon { width: 39px; height: 39px; border-radius: 11px; display: grid; place-items: center; font-size: 16px; }
.float-icon.green { background: #E9F9F0; color: #16A36A; }
.float-icon.blue { background: #EDF2FF; color: #3867F4; }
.float-icon.purple { background: #F1EFFF; color: #7657EF; }
.float-widget small { display: block; font-size: 10px; color: var(--muted); font-weight: 600; }
.float-widget strong { display: block; font-family: 'Plus Jakarta Sans'; font-size: 16px; color: var(--navy); }

@keyframes floatUp {
    0%,100% { margin-top: 0; }
    50% { margin-top: -14px; }
}

/* ================= STATS ================= */

.stats-section { padding: 0 0 90px; position: relative; z-index: 10; perspective: 1400px; }

.stats-box {
    background: white;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 35px 15px;
}

.stat-item { text-align: center; border-right: 1px solid var(--border); }
.stat-item:last-child { border: none; }
.stat-item i { font-size: 22px; color: var(--primary); margin-bottom: 13px; }
.stat-item h3 { font-size: 27px; font-weight: 800; margin-bottom: 5px; }
.stat-item p { color: var(--muted); font-size: 12px; font-weight: 600; margin: 0; }

/* ================= COMMON SECTION ================= */

.section { padding: 100px 0; }

.section-label {
    display: inline-block;
    color: var(--primary);
    background: #EDF2FF;
    border-radius: 30px;
    padding: 8px 15px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .8px;
    margin-bottom: 18px;
}

.section-heading {
    font-size: clamp(29px, 3vw, 42px);
    font-weight: 800;
    letter-spacing: -1.5px;
    color: var(--navy);
    margin-bottom: 15px;
}

.section-description {
    color: var(--muted);
    max-width: 620px;
    line-height: 1.8;
    font-size: 15px;
    margin: 0 auto;
}

/* ================= FEATURES ================= */

.features-section { background: #F7F9FD; }
.features-grid { perspective: 1400px; }

.feature-card {
    height: 100%;
    padding: 30px;
    border: 1px solid #E9EDF5;
    background: white;
    border-radius: 17px;
    position: relative;
    box-shadow: 0 10px 30px rgba(40,70,140,.05);
}

/* the glow circle lives on its own clipped layer so 3D children can pop out */
.feature-card .card-glow {
    position: absolute;
    inset: 0;
    border-radius: 17px;
    overflow: hidden;
    pointer-events: none;
}
.feature-card .card-glow::after {
    content: "";
    position: absolute;
    height: 140px; width: 140px;
    background: #EDF2FF;
    border-radius: 50%;
    right: -70px; bottom: -70px;
    transition: .5s;
}
.feature-card:hover .card-glow::after { transform: scale(2.4); }
.feature-card:hover { box-shadow: 0 30px 60px rgba(40,70,140,.16); border-color: #C7D5FF; }

.feature-icon {
    width: 58px; height: 58px;
    display: grid; place-items: center;
    border-radius: 15px;
    background: #EDF2FF;
    color: var(--primary);
    font-size: 23px;
    margin-bottom: 22px;
    position: relative;
    transition: background .4s, color .4s, box-shadow .4s;
    box-shadow: 0 6px 0 #D9E3FF;
}
.feature-card:hover .feature-icon { background: var(--gradient); color: white; box-shadow: 0 6px 0 #2447b8, 0 16px 24px rgba(56,103,244,.35); }

.feature-card h4 { font-size: 17px; font-weight: 800; color: var(--navy); margin-bottom: 12px; position: relative; }
.feature-card p { font-size: 13px; line-height: 1.8; color: var(--muted); margin: 0; position: relative; }

/* ================= HOW IT WORKS ================= */

.steps-section { background: white; }
.steps-grid { perspective: 1400px; }

.step-card { text-align: center; padding: 25px 20px; position: relative; }

.step-number {
    height: 75px; width: 75px;
    display: grid; place-items: center;
    border-radius: 23px;
    background: var(--gradient);
    color: white;
    font-size: 25px;
    margin: 0 auto 25px;
    box-shadow: 0 8px 0 #2447b8, 0 18px 30px rgba(56,103,244,.3);
    transform: rotateX(var(--nx, 0deg)) rotate(-8deg);
    transition: transform .4s;
}
.step-card:hover .step-number { transform: rotate(0deg) translateY(-8px); }

.step-card h4 { font-size: 17px; font-weight: 800; margin-bottom: 12px; }
.step-card p { color: var(--muted); font-size: 13px; line-height: 1.8; }

/* ================= ABOUT ================= */

.about-section { background: #F7F9FD; }
.about-visual { perspective: 1200px; }

.about-card {
    background: linear-gradient(145deg,#14243D,#243F69);
    border-radius: 25px;
    padding: 35px;
    color: white;
    min-height: 380px;
    position: relative;
    box-shadow: 0 30px 60px rgba(20,40,80,.18);
}

.about-card::before {
    content: "";
    position: absolute;
    height: 250px; width: 250px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
    right: -90px; top: -100px;
    box-shadow: 0 0 0 35px rgba(255,255,255,.04), 0 0 0 70px rgba(255,255,255,.03);
    clip-path: inset(0 round 0);
    pointer-events: none;
}

.about-card h3 { font-size: 25px; font-weight: 800; margin-bottom: 15px; position: relative; }
.about-card p { color: #B9C8DF; font-size: 14px; line-height: 1.8; position: relative; }

/* Rotating 3D cube with the KormoShathi logo icon */
.cube-stage { height: 130px; margin-top: 30px; display: grid; place-items: center; perspective: 700px; }
.cube {
    --c: 90px;
    position: relative;
    width: var(--c); height: var(--c);
    transform-style: preserve-3d;
    animation: spinCube 14s linear infinite;
}
.cube div {
    position: absolute; inset: 0;
    display: grid; place-items: center;
    font-size: 34px;
    color: white;
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(56,103,244,.85), rgba(118,87,239,.85));
    box-shadow: inset 0 0 25px rgba(255,255,255,.18);
}
.cube .f1 { transform: translateZ(calc(var(--c) / 2)); }
.cube .f2 { transform: rotateY(180deg) translateZ(calc(var(--c) / 2)); }
.cube .f3 { transform: rotateY(90deg) translateZ(calc(var(--c) / 2)); }
.cube .f4 { transform: rotateY(-90deg) translateZ(calc(var(--c) / 2)); }
.cube .f5 { transform: rotateX(90deg) translateZ(calc(var(--c) / 2)); }
.cube .f6 { transform: rotateX(-90deg) translateZ(calc(var(--c) / 2)); }
@keyframes spinCube {
    from { transform: rotateX(-20deg) rotateY(0deg); }
    to   { transform: rotateX(-20deg) rotateY(360deg); }
}

.about-content h2 { font-size: 36px; font-weight: 800; color: var(--navy); margin-bottom: 20px; }
.about-content > p { color: var(--muted); font-size: 15px; line-height: 1.9; margin-bottom: 25px; }

.about-check { display: flex; gap: 13px; margin-bottom: 18px; align-items: flex-start; }
.about-check i { color: #16A36A; font-size: 17px; margin-top: 3px; }
.about-check span { color: var(--text); font-size: 14px; font-weight: 700; }

/* ================= CTA ================= */

.cta-section { padding: 50px 0 100px; perspective: 1400px; }

.cta-box {
    position: relative;
    padding: 65px 30px;
    text-align: center;
    border-radius: 25px;
    color: white;
    background: linear-gradient(120deg,#172A49,#3867F4 75%,#7657EF);
    box-shadow: 0 25px 55px rgba(56,103,244,.20);
}

.cta-box .cta-rings { position: absolute; inset: 0; border-radius: 25px; overflow: hidden; pointer-events: none; }
.cta-box .cta-rings::before,
.cta-box .cta-rings::after {
    content: "";
    position: absolute;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 50%;
    height: 280px; width: 280px;
}
.cta-box .cta-rings::before { left: -120px; top: -150px; }
.cta-box .cta-rings::after { right: -100px; bottom: -170px; }

.cta-box h2 { font-size: clamp(28px,3vw,40px); font-weight: 800; margin-bottom: 15px; position: relative; }
.cta-box p { color: #D4DFF7; margin-bottom: 27px; font-size: 14px; position: relative; }
.cta-box .btn { position: relative; box-shadow: 0 6px 0 #c3cbe0, 0 16px 24px rgba(0,0,0,.2); }
.cta-box .btn:active { transform: translateY(4px); box-shadow: 0 2px 0 #c3cbe0; }

/* ================= FAQ ================= */

.faq-section { background: #F7F9FD; }

.accordion-item {
    border: 1px solid var(--border) !important;
    border-radius: 12px !important;
    margin-bottom: 13px;
    overflow: hidden;
    background: white;
    transition: transform .3s, box-shadow .3s;
}
.accordion-item:hover { transform: translateY(-3px) translateZ(0); box-shadow: 0 12px 28px rgba(40,70,140,.10); }

.accordion-button { padding: 20px; font-weight: 700; font-size: 14px; color: var(--navy); background: white; box-shadow: none !important; }
.accordion-button:not(.collapsed) { background: #EDF2FF; color: var(--primary); }
.accordion-body { color: var(--muted); font-size: 13px; line-height: 1.8; }

/* ================= FOOTER ================= */

.footer { background: #101D35; padding: 75px 0 25px; color: white; }
.footer .brand-name { color: white; }
.footer-description { color: #9CAEC8; font-size: 13px; line-height: 1.9; margin-top: 20px; max-width: 350px; }
.footer-title { font-size: 15px; font-weight: 800; margin-bottom: 23px; }
.footer-links { list-style: none; padding: 0; }
.footer-links li { margin-bottom: 14px; }
.footer-links a { color: #9CAEC8; font-size: 13px; transition: .3s; }
.footer-links a:hover { color: white; padding-left: 5px; }

.social-links { display: flex; gap: 11px; margin-top: 25px; }
.social-links a {
    width: 38px; height: 38px;
    display: grid; place-items: center;
    background: rgba(255,255,255,.09);
    color: white;
    border-radius: 10px;
    transition: .3s;
    box-shadow: 0 4px 0 rgba(0,0,0,.25);
}
.social-links a:hover { background: var(--primary); transform: translateY(-4px); box-shadow: 0 8px 0 rgba(0,0,0,.25); }

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,.1);
    padding-top: 23px;
    margin-top: 50px;
    text-align: center;
    color: #8798B1;
    font-size: 12px;
}

/* ================= SCROLL REVEAL ================= */

.reveal {
    opacity: 0;
    transform: perspective(1000px) translateY(40px) rotateX(10deg);
    transition: opacity .8s ease, transform .8s ease;
}
.reveal.show { opacity: 1; transform: perspective(1000px) translateY(0) rotateX(0); }

/* Keyboard focus */
a:focus-visible, button:focus-visible { outline: 3px solid #9DB5FF; outline-offset: 3px; }

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
    .reveal { opacity: 1; transform: none; }
}

/* ================= RESPONSIVE ================= */

@media (max-width: 991px) {
    .hero { padding: 125px 0 100px; text-align: center; }
    .hero-description { margin: 0 auto 30px; }
    .hero-buttons, .hero-note { justify-content: center; }
    .hero-visual { margin-top: 65px; }
    .scene { transform: rotateY(-6deg) rotateX(3deg); }
    .float-widget.one { right: -5px; }
    .float-widget.two { left: -5px; }
    .float-widget.three { display: none; }
    .stat-item:nth-child(2) { border-right: none; }
    .stat-item:nth-child(3), .stat-item:nth-child(4) { margin-top: 30px; }
    .about-content { margin-top: 45px; }
}

@media (max-width: 767px) {
    .hero-title { font-size: 40px; }
    .section { padding: 75px 0; }
    .stats-section { padding-bottom: 60px; }
    .stat-item { padding: 15px 0; }
    .dash-sidebar { width: 85px; }
    .dash-main { padding: 12px 9px; }
    .dash-stat strong { font-size: 15px; }
    .dash-bottom { grid-template-columns: 1fr; }
    .dashboard-body { min-height: 320px; }
    .float-widget { padding: 9px 11px; }
    .float-widget.one { top: -20px; right: 0; }
    .float-widget.two { bottom: -25px; left: 0; }
    .float-widget strong { font-size: 12px; }
    .float-icon { height: 32px; width: 32px; }
    .about-content h2 { font-size: 29px; }
    .bg-cube.c3, .bg-cube.c4 { display: none; }
}

@media (max-width: 575px) {
    .hero { padding-top: 115px; }
    .hero-title { font-size: 34px; letter-spacing: -1.5px; }
    .hero-description { font-size: 14px; }
    .hero-buttons { flex-direction: column; }
    .hero-buttons a { width: 100%; }
    .hero-visual { margin-top: 55px; }
    .dashboard-window { border-width: 4px; }
    .dashboard-body { min-height: 280px; }
    .dash-sidebar { width: 68px; padding: 12px 5px; }
    .dash-menu { font-size: 7px; padding: 8px 4px; }
    .dash-menu i { width: 13px; }
    .dash-logo { font-size: 9px; }
    .dash-stat { padding: 8px 6px; }
    .dash-stat small { font-size: 7px; }
    .dash-stat strong { font-size: 13px; }
    .dash-stat i { font-size: 9px; padding: 5px; }
    .float-widget.one { right: -5px; }
    .float-widget.two { left: -5px; }
    .float-widget small { font-size: 8px; }
    .float-widget strong { font-size: 10px; }
    .float-icon { width: 28px; height: 28px; font-size: 12px; }
    .section-heading { font-size: 29px; }
    .stats-box { padding: 20px 10px; }
    .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
    .stat-item:last-child { border-bottom: none; }
    .cta-box { padding: 50px 20px; }
}
</style>
</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar navbar-expand-lg fixed-top">
<div class="container">

    <a class="navbar-brand" href="index.php">
        <div class="brand-icon">
            <i class="fa-solid fa-people-group"></i>
        </div>
        <span class="brand-name">KormoShathi</span>
    </a>

    <button class="navbar-toggler border-0 shadow-none"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNavbar"
        aria-label="Toggle navigation">
        <i class="fa-solid fa-bars fs-4"></i>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto align-items-lg-center">

            <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
            <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
            <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>

            <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary-custom">
                    <i class="fa-solid fa-user"></i>
                    <?= $isLoggedIn ? 'Dashboard' : 'Login' ?>
                </a>
            </li>

        </ul>
    </div>

</div>
</nav>


<!-- ================= HERO ================= -->

<section class="hero" id="home">

<div class="hero-floor"></div>
<div class="hero-glow"></div>

<!-- floating 3D cubes -->
<div class="bg-cube c1" data-parallax="0.04"><i></i><i></i><i></i><i></i><i></i><i></i></div>
<div class="bg-cube c2" data-parallax="-0.03"><i></i><i></i><i></i><i></i><i></i><i></i></div>
<div class="bg-cube c3" data-parallax="0.05"><i></i><i></i><i></i><i></i><i></i><i></i></div>
<div class="bg-cube c4" data-parallax="-0.05"><i></i><i></i><i></i><i></i><i></i><i></i></div>

<div class="container hero-content">
<div class="row align-items-center">

    <div class="col-lg-6">

        <div class="hero-badge">
            <span></span>
            SMART HR MANAGEMENT PLATFORM
        </div>

        <h1 class="hero-title">
            Manage Your People.
            <span>Empower Your Business.</span>
        </h1>

        <p class="hero-description">
            Simplify your entire HR operation with KormoShathi.
            Manage employees, track attendance, handle payroll,
            organize leave requests and connect your workforce
            from one powerful platform.
        </p>

        <div class="hero-buttons">
            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary-custom">
                Get Started
                <i class="fa-solid fa-arrow-right"></i>
            </a>

            <a href="#features" class="btn-outline-custom">
                <i class="fa-solid fa-play"></i>
                Explore Features
            </a>
        </div>

        <div class="hero-note">
            <i class="fa-solid fa-circle-check"></i>
            <span>One Platform. Complete Workforce Management.</span>
        </div>

    </div>


    <!-- 3D DASHBOARD SCENE -->
    <div class="col-lg-6">

        <div class="hero-visual" id="heroVisual">

            <div class="scene" id="scene">

                <div class="scene-shadow"></div>
                <div class="dashboard-back"></div>
                <div class="dashboard-mid"></div>

                <div class="float-widget one">
                    <div class="float-icon green"><i class="fa-solid fa-user-check"></i></div>
                    <div>
                        <small>Attendance Overview</small>
                        <strong>Real-time Tracking</strong>
                    </div>
                </div>

                <div class="float-widget two">
                    <div class="float-icon blue"><i class="fa-solid fa-wallet"></i></div>
                    <div>
                        <small>Payroll Management</small>
                        <strong>Easy Processing</strong>
                    </div>
                </div>

                <div class="float-widget three">
                    <div class="float-icon purple"><i class="fa-solid fa-calendar-check"></i></div>
                    <div>
                        <small>Leave Requests</small>
                        <strong>Quick Approval</strong>
                    </div>
                </div>

                <div class="dashboard-window">

                    <div class="window-header">
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>
                        <span class="window-title">KORMOSHATHI / HR DASHBOARD</span>
                    </div>

                    <div class="dashboard-body">

                        <div class="dash-sidebar">
                            <div class="dash-logo">
                                <i class="fa-solid fa-people-group"></i>
                                KormoShathi
                            </div>
                            <div class="dash-menu active"><i class="fa-solid fa-house"></i> Dashboard</div>
                            <div class="dash-menu"><i class="fa-solid fa-users"></i> Employees</div>
                            <div class="dash-menu"><i class="fa-solid fa-calendar-check"></i> Attendance</div>
                            <div class="dash-menu"><i class="fa-solid fa-calendar-days"></i> Leave</div>
                            <div class="dash-menu"><i class="fa-solid fa-wallet"></i> Payroll</div>
                            <div class="dash-menu"><i class="fa-solid fa-chart-line"></i> Reports</div>
                        </div>

                        <div class="dash-main">

                            <div class="dash-heading">
                                <h6>Dashboard Overview</h6>
                                <span><i class="fa-regular fa-bell"></i> &nbsp; Admin</span>
                            </div>

                            <div class="dash-stat-grid">
                                <div class="dash-stat"><small>Total Employees</small><strong>248</strong><i class="fa-solid fa-users"></i></div>
                                <div class="dash-stat"><small>Present Today</small><strong>215</strong><i class="fa-solid fa-user-check"></i></div>
                                <div class="dash-stat"><small>On Leave</small><strong>12</strong><i class="fa-solid fa-calendar-minus"></i></div>
                                <div class="dash-stat"><small>Pending Requests</small><strong>08</strong><i class="fa-solid fa-clock"></i></div>
                            </div>

                            <div class="dash-bottom">

                                <div class="dash-panel">
                                    <h6>Attendance Overview</h6>
                                    <div class="chart-bars">
                                        <span style="height:55%"></span>
                                        <span style="height:75%"></span>
                                        <span style="height:65%"></span>
                                        <span style="height:90%"></span>
                                        <span style="height:80%"></span>
                                        <span style="height:100%"></span>
                                        <span style="height:70%"></span>
                                    </div>
                                </div>

                                <div class="dash-panel">
                                    <h6>Recent Activities</h6>
                                    <div class="dash-activity"><span class="activity-dot"></span> Employee Added</div>
                                    <div class="dash-activity"><span class="activity-dot"></span> Leave Approved</div>
                                    <div class="dash-activity"><span class="activity-dot"></span> Attendance Updated</div>
                                    <div class="dash-activity"><span class="activity-dot"></span> Payroll Updated</div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
</div>
</section>


<!-- ================= STATS ================= -->

<section class="stats-section">
<div class="container">

    <div class="stats-box" data-tilt data-max="3">
        <div class="row g-4">

            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <i class="fa-solid fa-users" data-depth style="--z:30; display:inline-block"></i>
                    <h3 data-depth style="--z:20">Employee</h3>
                    <p>Management</p>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <i class="fa-solid fa-clock" data-depth style="--z:30; display:inline-block"></i>
                    <h3 data-depth style="--z:20">Smart</h3>
                    <p>Attendance Tracking</p>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <i class="fa-solid fa-wallet" data-depth style="--z:30; display:inline-block"></i>
                    <h3 data-depth style="--z:20">Automated</h3>
                    <p>Payroll Management</p>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <i class="fa-solid fa-shield-halved" data-depth style="--z:30; display:inline-block"></i>
                    <h3 data-depth style="--z:20">Secure</h3>
                    <p>Workforce Data</p>
                </div>
            </div>

        </div>
    </div>

</div>
</section>


<!-- ================= FEATURES ================= -->

<section class="section features-section" id="features">
<div class="container">

    <div class="text-center mb-5 reveal">
        <span class="section-label">OUR FEATURES</span>
        <h2 class="section-heading">Everything Your HR Team Needs</h2>
        <p class="section-description">
            Powerful HR tools designed to simplify daily
            operations and improve workforce productivity.
        </p>
    </div>

    <div class="row g-4 features-grid">

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-users-gear"></i></div>
                <h4 data-depth style="--z:35">Employee Management</h4>
                <p data-depth style="--z:18">
                    Manage employee profiles, departments,
                    designations and employment records
                    from one centralized system.
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-fingerprint"></i></div>
                <h4 data-depth style="--z:35">Attendance Management</h4>
                <p data-depth style="--z:18">
                    Monitor daily attendance, working hours,
                    late arrivals and employee attendance
                    records with ease.
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <h4 data-depth style="--z:35">Payroll Management</h4>
                <p data-depth style="--z:18">
                    Organize salary records, bonuses,
                    deductions and payroll processing
                    with simplified workflows.
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-calendar-check"></i></div>
                <h4 data-depth style="--z:35">Leave Management</h4>
                <p data-depth style="--z:18">
                    Submit leave applications, manage
                    leave balances and streamline
                    approval processes.
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-comments"></i></div>
                <h4 data-depth style="--z:35">Internal Communication</h4>
                <p data-depth style="--z:18">
                    Connect HR and employees through
                    workplace messaging, notifications
                    and company announcements.
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card" data-tilt data-max="10">
                <div class="card-glow"></div>
                <div class="feature-icon" data-depth style="--z:55"><i class="fa-solid fa-chart-pie"></i></div>
                <h4 data-depth style="--z:35">Reports & Analytics</h4>
                <p data-depth style="--z:18">
                    Generate workforce reports and
                    visualize attendance, employee
                    and payroll information.
                </p>
            </div>
        </div>

    </div>

</div>
</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="section steps-section" id="how-it-works">
<div class="container">

    <div class="text-center mb-5 reveal">
        <span class="section-label">HOW IT WORKS</span>
        <h2 class="section-heading">Get Started in Three Simple Steps</h2>
        <p class="section-description">
            A simple and organized way to manage
            your entire workforce.
        </p>
    </div>

    <div class="row g-4 steps-grid">

        <div class="col-md-4 reveal">
            <div class="step-card" data-tilt data-max="8">
                <div class="step-number" data-depth style="--z:50"><i class="fa-solid fa-building"></i></div>
                <h4 data-depth style="--z:30">01. Set Up Your HR</h4>
                <p data-depth style="--z:15">
                    Access your HR dashboard and organize
                    your company departments and
                    employee information.
                </p>
            </div>
        </div>

        <div class="col-md-4 reveal">
            <div class="step-card" data-tilt data-max="8">
                <div class="step-number" data-depth style="--z:50"><i class="fa-solid fa-user-plus"></i></div>
                <h4 data-depth style="--z:30">02. Manage Employees</h4>
                <p data-depth style="--z:15">
                    Add employees, assign designations
                    and manage their professional
                    information.
                </p>
            </div>
        </div>

        <div class="col-md-4 reveal">
            <div class="step-card" data-tilt data-max="8">
                <div class="step-number" data-depth style="--z:50"><i class="fa-solid fa-chart-line"></i></div>
                <h4 data-depth style="--z:30">03. Track Everything</h4>
                <p data-depth style="--z:15">
                    Monitor attendance, manage payroll,
                    approve leaves and analyze
                    organizational activities.
                </p>
            </div>
        </div>

    </div>

</div>
</section>


<!-- ================= ABOUT ================= -->

<section class="section about-section" id="about">
<div class="container">
<div class="row align-items-center">

    <div class="col-lg-6 about-visual reveal">

        <div class="about-card" data-tilt data-max="9">

            <span class="section-label" data-depth style="--z:40; background:rgba(255,255,255,.12);color:white">
                ABOUT KORMOSHATHI
            </span>

            <h3 data-depth style="--z:35">
                People First.
                Productivity Always.
            </h3>

            <p data-depth style="--z:20">
                Bringing your entire workforce
                management process together
                through one organized digital platform.
            </p>

            <!-- rotating 3D logo cube -->
            <div class="cube-stage" data-depth style="--z:70">
                <div class="cube">
                    <div class="f1"><i class="fa-solid fa-people-group"></i></div>
                    <div class="f2"><i class="fa-solid fa-wallet"></i></div>
                    <div class="f3"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="f4"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="f5"><i class="fa-solid fa-users"></i></div>
                    <div class="f6"><i class="fa-solid fa-shield-halved"></i></div>
                </div>
            </div>

        </div>

    </div>


    <div class="col-lg-6 about-content reveal">

        <span class="section-label">WHY KORMOSHATHI</span>

        <h2>Simplify HR. Focus on People.</h2>

        <p>
            KormoShathi is designed to make workforce
            management easier, more organized and
            more efficient. From employee records
            to daily HR operations, everything
            comes together in one place.
        </p>

        <div class="about-check">
            <i class="fa-solid fa-circle-check"></i>
            <span>Centralized Employee Information</span>
        </div>

        <div class="about-check">
            <i class="fa-solid fa-circle-check"></i>
            <span>Organized HR Workflows</span>
        </div>

        <div class="about-check">
            <i class="fa-solid fa-circle-check"></i>
            <span>Role-Based Employee Access</span>
        </div>

        <div class="about-check">
            <i class="fa-solid fa-circle-check"></i>
            <span>Easy-to-Use Management Interface</span>
        </div>

        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary-custom mt-3">
            Explore KormoShathi
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</div>
</div>
</section>


<!-- ================= FAQ ================= -->

<section class="section faq-section" id="faq">
<div class="container">

    <div class="text-center mb-5 reveal">
        <span class="section-label">FAQ</span>
        <h2 class="section-heading">Frequently Asked Questions</h2>
        <p class="section-description">Find answers to common questions about KormoShathi.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="accordion" id="faqAccordion">

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqOne">
                            What is KormoShathi?
                        </button>
                    </h2>
                    <div id="faqOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            KormoShathi is an HR Management
                            System designed to organize
                            employee information, attendance,
                            leave and payroll operations.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwo">
                            Who can use KormoShathi?
                        </button>
                    </h2>
                    <div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            HR administrators can manage
                            workforce operations, while
                            employees can access their
                            personal dashboards and
                            available HR services.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThree">
                            Can employees access their own information?
                        </button>
                    </h2>
                    <div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes. Employees can access their
                            own dashboard and view the
                            information and services
                            permitted by their account.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFour">
                            How can I access the HR dashboard?
                        </button>
                    </h2>
                    <div id="faqFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Use your authorized account
                            credentials to log in.
                            Your account role determines
                            which dashboard you can access.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>
</section>


<!-- ================= CTA ================= -->

<section class="cta-section">
<div class="container">

    <div class="cta-box reveal">
        <div class="cta-box-inner" data-tilt data-max="4" style="border-radius:25px">
            <div class="cta-rings"></div>

            <h2 data-depth style="--z:40">Ready to Simplify Your HR Operations?</h2>

            <p data-depth style="--z:25">
                Bring your workforce management together
                with KormoShathi.
            </p>

            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
                class="btn btn-light btn-lg rounded-3 fw-bold px-4"
                data-depth style="--z:60; display:inline-block">
                Get Started
                <i class="fa-solid fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>

</div>
</section>


<!-- ================= FOOTER ================= -->

<footer class="footer">
<div class="container">

    <div class="row g-5">

        <div class="col-lg-5">

            <a href="index.php" class="navbar-brand">
                <div class="brand-icon"><i class="fa-solid fa-people-group"></i></div>
                <span class="brand-name">KormoShathi</span>
            </a>

            <p class="footer-description">
                Simplifying workforce management
                through organized HR operations,
                smart tools and a people-first approach.
            </p>

            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
            </div>

        </div>

        <div class="col-lg-3 col-6">
            <h5 class="footer-title">Quick Links</h5>
            <ul class="footer-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
        </div>

        <div class="col-lg-4 col-6">
            <h5 class="footer-title">Account</h5>
            <ul class="footer-links">
                <li><a href="login.php">Login</a></li>
                <li>
                    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                </li>
                <li><a href="mailto:info@kormoshathi.com">Contact Us</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> KormoShathi. All Rights Reserved.
    </div>

</div>
</footer>


<!-- ================= SCRIPTS ================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    // ---------- Navbar scroll effect ----------
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });


    // ---------- Scroll reveal ----------
    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window && !reduceMotion) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealElements.forEach(el => observer.observe(el));
    } else {
        revealElements.forEach(el => el.classList.add('show'));
    }


    // ---------- Smooth scroll + close mobile menu ----------
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href.length < 2) return;

            const target = document.querySelector(href);
            if (!target) return;

            e.preventDefault();
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });

            const menu = document.querySelector('#mainNavbar');
            if (menu.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(menu).hide();
            }
        });
    });


    // ---------- Active nav link on scroll ----------
    const sections = ['home', 'features', 'how-it-works', 'about', 'faq']
        .map(id => document.getElementById(id));
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    window.addEventListener('scroll', () => {
        let current = 'home';
        sections.forEach(sec => {
            if (sec && window.scrollY >= sec.offsetTop - 140) current = sec.id;
        });
        navLinks.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + current);
        });
    }, { passive: true });


    if (reduceMotion || !canHover) return;


    // ---------- Hero 3D scene follows the mouse ----------
    const heroVisual = document.getElementById('heroVisual');
    const scene = document.getElementById('scene');

    if (heroVisual && scene) {
        heroVisual.addEventListener('mouseenter', () => heroVisual.classList.add('is-hover'));

        heroVisual.addEventListener('mousemove', (e) => {
            const r = heroVisual.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            scene.style.setProperty('--sy', (-14 + x * 18) + 'deg');
            scene.style.setProperty('--sx', (6 - y * 12) + 'deg');
        });

        heroVisual.addEventListener('mouseleave', () => {
            heroVisual.classList.remove('is-hover');
            scene.style.setProperty('--sy', '-14deg');
            scene.style.setProperty('--sx', '6deg');
        });
    }


    // ---------- Generic 3D tilt for [data-tilt] elements ----------
    document.querySelectorAll('[data-tilt]').forEach(el => {
        const max = parseFloat(el.dataset.max || 8);

        el.addEventListener('mouseenter', () => el.classList.add('is-hover'));

        el.addEventListener('mousemove', (e) => {
            const r = el.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            el.style.setProperty('--ry', (x * max * 2) + 'deg');
            el.style.setProperty('--rx', (-y * max * 2) + 'deg');
        });

        el.addEventListener('mouseleave', () => {
            el.classList.remove('is-hover');
            el.style.setProperty('--rx', '0deg');
            el.style.setProperty('--ry', '0deg');
        });
    });


    // ---------- Background cubes parallax on scroll ----------
    const cubes = document.querySelectorAll('[data-parallax]');
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        cubes.forEach(c => {
            c.style.translate = '0 ' + (y * parseFloat(c.dataset.parallax) * 10) + 'px';
        });
    }, { passive: true });

})();
</script>

</body>
</html>