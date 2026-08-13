<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle! — Readify Kids</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root{
            --sky-top:#5FC0FF; --sky-mid:#8FD8FF; --sky-bottom:#FFDD8A;
            --ground:#7BC96F; --ground-dark:#5AA652;
            --panel:#3B2E63; --panel-light:#5B4696;
            --gold:#FFC93C; --gold-dark:#E0A11B;
            --hp-green:#57D67B; --hp-orange:#FFA53C; --hp-red:#FF5C5C;
            --ink:#2B2140; --cream:#FFF7E6; --pink:#FF6FA5; --purple:#7C3AED;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: var(--sky-top); min-height: 100vh; overflow-x: hidden; }

        /* ── Top bar ─────────────────────────── */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 20px; background: var(--cream);
            border-bottom: 3px solid var(--panel); position: relative; z-index: 10;
        }
        .quit-btn {
            display: flex; align-items: center; gap: 6px;
            color: #B3261E; font-size: 13px; font-weight: 800;
            font-family: 'Baloo 2', sans-serif;
            text-decoration: none; padding: 6px 16px; border-radius: 20px;
            border: 2px solid #B3261E; cursor: pointer; background: #FFE1E1; transition: all 0.2s;
        }
        .quit-btn:hover { background: #FFC9C9; }
        .round-badge {
            background: var(--purple); color: #fff;
            font-family: 'Baloo 2', sans-serif;
            font-size: 12px; font-weight: 700;
            padding: 6px 18px; border-radius: 20px;
            box-shadow: 0 3px 0 rgba(0,0,0,.15);
        }
        .top-right { display: flex; align-items: center; gap: 12px; }
        .hp-display { font-size: 13px; font-weight: 800; color: var(--gold-dark); font-family: 'Baloo 2', sans-serif; }
        .level-chip {
            background: var(--gold); color: #5A3E00;
            font-family: 'Baloo 2', sans-serif;
            font-size: 11px; font-weight: 800;
            padding: 5px 14px; border-radius: 20px;
            box-shadow: 0 3px 0 rgba(0,0,0,.12);
        }

        /* ── Arena ───────────────────────────── */
        .arena {
            background: linear-gradient(180deg, var(--sky-top) 0%, var(--sky-mid) 40%, var(--sky-bottom) 82%, #FFEBB0 100%);
            min-height: calc(100vh - 52px);
            display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .stars { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
        .star  { position: absolute; background: #FFF6C9; border-radius: 50%; box-shadow: 0 0 6px 2px rgba(255,201,60,.5); animation: twinkle 2s infinite alternate; }
        .sun {
            position: absolute; top: 5%; right: 8%; width: 90px; height: 90px; border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #FFF6C9, var(--gold) 60%, var(--gold-dark) 100%);
            box-shadow: 0 0 50px 16px rgba(255,201,60,0.5);
            animation: sunPulse 4s ease-in-out infinite; z-index: 0;
        }
        @keyframes sunPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.06);}}
        .cloud { position:absolute; opacity:.9; z-index:0; }
        .cloud svg { display:block; }
        .cloud.c1 { top:8%; left:-10%; width:160px; animation:drift 42s linear infinite; }
        .cloud.c2 { top:18%; left:-20%; width:120px; animation:drift 56s linear infinite; animation-delay:-12s; }
        .cloud.c3 { top:4%; left:-15%; width:95px; animation:drift 34s linear infinite; animation-delay:-24s; }
        @keyframes drift{from{transform:translateX(0);}to{transform:translateX(140vw);}}
        .mountains{
            position:absolute; bottom:78px; left:0; width:100%; height:18%;
            background:linear-gradient(180deg,#B79CE0,#8F72C4);
            clip-path:polygon(0% 100%,8% 40%,18% 70%,30% 20%,42% 65%,55% 15%,68% 60%,80% 25%,92% 55%,100% 30%,100% 100%);
            opacity:.5; z-index:0;
        }
        .ground {
            position:absolute; bottom:0; left:0; right:0; height:80px;
            background:linear-gradient(180deg,var(--ground) 0%,var(--ground-dark) 100%);
            border-top:4px solid #4E9048; z-index:1;
        }

        /* ── HP section ──────────────────────── */
        .hp-section {
            display:flex; align-items:flex-start; justify-content:space-between;
            padding:14px 24px 0; position:relative; z-index:5;
        }
        .hp-block {
            width:200px; background:linear-gradient(180deg,var(--panel-light),var(--panel));
            border:3px solid #2A2050; border-radius:16px; padding:8px 12px;
            box-shadow:0 4px 0 rgba(0,0,0,.15);
        }
        .hp-name {
            font-size:13px; font-weight:700; color:#fff;
            font-family:'Baloo 2',sans-serif;
            margin-bottom:5px; display:flex; align-items:center; gap:6px;
        }
        .hp-name .label {
            font-size:10px; background:var(--gold); color:#5A3E00;
            padding:1px 8px; border-radius:20px; font-weight:700;
        }
        .hp-bar-bg {
            background:#1F1738; border-radius:8px; height:14px;
            overflow:hidden; border:2px solid #14102A;
        }
        .hp-bar-bg.shake { animation:hpShake 0.4s ease; }
        .hp-bar-fill { height:100%; border-radius:6px; transition:width 0.8s ease; }
        .hp-bar-fill.student { background:linear-gradient(180deg,#7CE79A,var(--hp-green)); }
        .hp-bar-fill.enemy   { background:linear-gradient(180deg,#FF8A8A,var(--hp-red)); }
        .hp-text { font-size:10px; color:rgba(255,255,255,0.85); font-weight:700; margin-top:4px; text-align:right; }

        /* ── Center info ─────────────────────── */
        .center-info {
            flex:1; display:flex; flex-direction:column;
            align-items:center; padding:0 16px; position:relative; z-index:5;
        }
        .vs-badge {
            font-family:'Baloo 2',sans-serif;
            font-size:20px; font-weight:800; color:var(--gold-dark);
            -webkit-text-stroke:1px #fff;
            text-shadow:0 2px 0 rgba(0,0,0,.15); margin-bottom:6px;
        }
        .rounds-left-pill {
            font-family:'Baloo 2',sans-serif;
            font-size:11px; padding:4px 14px; border-radius:20px;
            font-weight:700; margin-bottom:8px;
            display:inline-block; transition:all 0.3s;
            border:2px solid var(--panel); background:var(--cream); color:var(--panel);
        }

        /* ── Center word card ────────────────── */
        #center-word-card {
            display:none; background:var(--cream);
            border:4px solid var(--panel); border-radius:18px;
            padding:14px 22px; text-align:center; width:100%; max-width:420px;
            position:relative; z-index:10;
            box-shadow:0 6px 0 rgba(0,0,0,.15); animation:wordPumpIn 0.5s ease;
        }
        .cw-label {
            font-family:'Baloo 2',sans-serif;
            font-size:11px; color:var(--panel-light);
            font-weight:700; text-transform:uppercase;
            letter-spacing:0.08em; margin-bottom:6px;
        }
        #center-word-text {
            font-family:'Baloo 2',sans-serif;
            font-size:28px; font-weight:700; color:var(--ink); line-height:1.4;
        }
        #center-word-text.paragraph { font-size:15px; line-height:1.7; }

        /* ── Countdown overlay ───────────────── */
        #countdown-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(20,15,40,0.72); z-index:8888;
            align-items:center; justify-content:center;
            flex-direction:column; gap:16px;
        }
        .countdown-number {
            font-family:'Baloo 2',sans-serif;
            font-size:140px; font-weight:800; color:#fff;
            text-shadow:0 0 60px rgba(255,201,60,0.9),0 0 20px rgba(255,255,255,0.5);
            animation:countPop 0.9s cubic-bezier(0.34,1.56,0.64,1) forwards; line-height:1;
        }
        .countdown-label {
            font-family:'Baloo 2',sans-serif;
            font-size:18px; font-weight:700; color:var(--gold);
            letter-spacing:0.15em; text-transform:uppercase;
        }
        .countdown-sub { font-size:13px; color:rgba(255,255,255,0.6); margin-top:4px; }

        /* ── Battlefield ─────────────────────── */
        .battlefield {
            flex:1; display:flex; align-items:flex-end;
            justify-content:space-between; padding:0 80px 90px;
            position:relative; z-index:2;
        }
        .character-wrap {
            display:flex; flex-direction:column;
            align-items:center; gap:8px; position:relative;
        }
        .character-label {
            font-family:'Baloo 2',sans-serif; font-size:11px; font-weight:700;
            color:var(--panel); background:rgba(255,255,255,.55);
            padding:2px 10px; border-radius:10px;
            text-transform:uppercase; letter-spacing:0.06em;
        }
        .char-platform {
            position:absolute; bottom:-6px; left:50%; transform:translateX(-50%);
            width:120px; height:20px; border-radius:50%;
            background:radial-gradient(ellipse at center,rgba(0,0,0,.28),transparent 72%); z-index:0;
        }
        .character-sprite,.enemy-sprite {
            width:140px; height:170px;
            display:flex; align-items:flex-end; justify-content:center;
            filter:drop-shadow(0 8px 6px rgba(0,0,0,.25)); transition:filter 0.3s;
            position:relative; z-index:1;
        }
        .character-sprite svg,.enemy-sprite svg { width:100%; height:100%; display:block; }

        /* ── Enemy attack bubble ─────────────── */
        #enemy-attack-bubble {
            display:none; position:absolute; top:-60px; left:50%;
            transform:translateX(-50%);
            background:#fff; border:3px solid var(--panel); border-radius:14px;
            padding:8px 16px; font-family:'Baloo 2',sans-serif;
            font-size:14px; font-weight:700; color:var(--ink);
            white-space:nowrap; z-index:30;
            animation:bubblePop 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        #enemy-attack-bubble::after {
            content:''; position:absolute; bottom:-10px; left:50%;
            transform:translateX(-50%); width:0; height:0;
            border-left:8px solid transparent; border-right:8px solid transparent;
            border-top:10px solid var(--panel);
        }

        /* ── Student hit ──────────────────────── */
        #student-damage-indicator {
            position:absolute; top:-30px; left:50%;
            transform:translateX(-50%);
            font-size:22px; font-weight:900; color:#FF3B3B;
            text-shadow:2px 2px 0 #fff;
            pointer-events:none; display:none; z-index:25;
        }

        /* ── Attack effect ───────────────────── */
        #attack-effect {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-50%);
            font-size:60px; pointer-events:none; opacity:0; z-index:20;
        }

        /* ── Score reveal ────────────────────── */
        #score-reveal {
            position:absolute; top:20%; left:50%;
            transform:translateX(-50%);
            z-index:30; text-align:center; display:none;
            animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .score-reveal-inner {
            background:var(--cream); border:5px solid var(--gold);
            border-radius:20px; padding:16px 30px;
            text-align:center; box-shadow:0 8px 0 rgba(0,0,0,.18);
        }
        .score-reveal-label { font-family:'Baloo 2',sans-serif; font-size:11px; color:var(--panel-light); font-weight:700; margin-bottom:4px; text-transform:uppercase; letter-spacing:.06em; }
        .score-reveal-value { font-family:'Baloo 2',sans-serif; font-size:36px; font-weight:800; margin-bottom:4px; }
        .score-reveal-damage { font-size:14px; font-weight:800; color:#E05B3C; }
        .score-reveal-transcript { font-size:11px; color:rgba(43,33,64,.55); margin-top:4px; }

        /* Damage floater */
        .damage-floater {
            position:absolute; top:-20px; left:50%;
            transform:translateX(-50%);
            font-family:'Baloo 2',sans-serif;
            font-size:28px; font-weight:800; color:var(--gold-dark);
            text-shadow:2px 2px 0 #fff; pointer-events:none;
            animation:floatUp 1.2s ease forwards;
            white-space:nowrap; z-index:25;
        }

        /* ── Battle message ──────────────────── */
        .battle-msg-wrap {
            position:absolute; bottom:92px; left:50%;
            transform:translateX(-50%); z-index:10; white-space:nowrap;
        }
        .battle-msg {
            background:var(--cream); border:3px solid var(--panel);
            border-radius:20px; padding:8px 20px;
            font-size:13px; font-weight:700; color:var(--ink); text-align:center;
            box-shadow:0 4px 0 rgba(0,0,0,.12);
        }
        .battle-transcript { font-size:11px; color:rgba(43,33,64,.6); margin-top:3px; display:none; }

        /* ── Done reading button ─────────────── */
        #done-reading-btn {
            display:none; margin-top:8px; padding:9px 22px;
            border-radius:20px; border:none;
            font-family:'Baloo 2',sans-serif;
            background:linear-gradient(180deg,#FF8FB8,var(--pink));
            color:#fff; font-size:13px; font-weight:700;
            cursor:pointer; box-shadow:0 4px 0 rgba(0,0,0,.18);
            animation:pulse 1.5s infinite;
        }

        /* ── Bottom panel ────────────────────── */
        .bottom-panel {
            background:var(--cream); border-top:4px solid var(--panel);
            padding:12px 24px; position:relative; z-index:10;
        }
        .bottom-inner {
            display:flex; align-items:center; gap:16px;
            max-width:900px; margin:0 auto; justify-content:center;
        }

        /* ── Recording hold button ───────────── */
        .hold-rec-wrap {
            display:flex; flex-direction:column; align-items:center; gap:8px;
        }
        .hold-rec-btn {
            width:72px; height:72px; border-radius:50%;
            background:linear-gradient(180deg,#FF8FB8,var(--pink));
            border:none; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 0 0 8px rgba(255,111,165,.2), 0 5px 0 rgba(0,0,0,.15);
            transition:all 0.15s; animation:micPulse 2s infinite;
            position:relative;
        }
        .hold-rec-btn.active {
            background:linear-gradient(180deg,#8F7AD1,var(--purple));
            box-shadow:0 0 0 12px rgba(124,58,237,.2),0 5px 0 rgba(0,0,0,.15);
            animation:none; transform:scale(1.08);
        }
        .hold-rec-btn.loading {
            background:linear-gradient(180deg,#FFA53C,var(--gold-dark));
            animation:none;
        }
        .hold-rec-btn i { color:#fff; font-size:28px; pointer-events:none; }
        .hold-rec-label {
            font-family:'Baloo 2',sans-serif;
            font-size:11px; font-weight:700; color:var(--panel);
            text-align:center;
        }
        .hold-rec-timer {
            font-family:'Baloo 2',sans-serif;
            font-size:18px; font-weight:800; color:var(--panel);
            display:none;
        }

        /* Waveform */
        .waveform-wrap {
            background:#fff; border:2px solid var(--panel);
            border-radius:10px; padding:8px 12px;
            display:none; align-items:center; gap:3px; height:38px;
        }
        .wv { width:4px; border-radius:3px; background:#D9D0F2; height:6px; transition:height 0.08s; }

        /* Loading state */
        .loading-wrap { display:none; align-items:center; gap:8px; }
        .loading-wrap span { font-size:12px; color:var(--ink); font-weight:700; font-family:'Baloo 2',sans-serif; }

        /* History */
        .history-panel {
            width:200px; display:flex; flex-direction:column;
            gap:6px; overflow-y:auto; max-height:110px;
        }
        .history-title {
            font-family:'Baloo 2',sans-serif;
            font-size:10px; font-weight:700; color:var(--panel-light);
            text-transform:uppercase; letter-spacing:0.07em; flex-shrink:0;
        }
        .history-item {
            background:#fff; border:2px solid #E6DEFA;
            border-radius:8px; padding:5px 8px; font-size:10px; color:var(--ink);
        }
        .history-item .hi-score { font-weight:800; }
        .history-item .hi-dmg { color:#E05B3C; font-weight:800; }

        /* Dots */
        .dots-wrap { display:flex; justify-content:center; gap:5px; margin-bottom:8px; }
        .dot { width:10px; height:10px; border-radius:50%; transition:all 0.3s; }

        /* ── Quit modal ────────────────────────── */
        .quit-modal {
            display:none; position:fixed; inset:0;
            background:rgba(20,15,40,0.75); z-index:99999;
            align-items:center; justify-content:center;
        }
        .quit-modal-card {
            background:var(--cream); border:5px solid var(--gold);
            border-radius:24px; padding:36px 32px; text-align:center;
            max-width:380px; width:90%; box-shadow:0 10px 0 rgba(0,0,0,.18);
            animation:popIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        .quit-modal-emoji { font-size:60px; margin-bottom:12px; }
        .quit-modal-title { font-family:'Baloo 2',sans-serif; font-size:22px; font-weight:800; color:var(--panel); margin-bottom:8px; }
        .quit-modal-sub { font-size:13px; color:var(--ink); line-height:1.5; margin-bottom:10px; }
        .quit-modal-note { font-size:11px; color:rgba(43,33,64,.55); margin-bottom:20px; font-style:italic; }
        .quit-modal-btns { display:flex; gap:10px; justify-content:center; }
        .btn-stay { padding:11px 22px; border-radius:14px; font-family:'Baloo 2',sans-serif; background:linear-gradient(180deg,#8F7AD1,var(--purple)); color:#fff; font-size:13px; font-weight:700; border:none; cursor:pointer; box-shadow:0 4px 0 rgba(0,0,0,.18); }
        .btn-quit-confirm { padding:11px 22px; border-radius:14px; font-family:'Baloo 2',sans-serif; background:#FFE1E1; color:#B3261E; font-size:13px; font-weight:700; border:2px solid #B3261E; cursor:pointer; text-decoration:none; display:inline-block; }

        /* ── Overlays ────────────────────────── */
        .overlay {
            display:none; position:fixed; inset:0;
            background:rgba(20,15,40,0.75); z-index:9999;
            align-items:center; justify-content:center; animation:fadeIn 0.3s ease;
        }
        .overlay-card {
            background:var(--cream); border:5px solid var(--gold);
            border-radius:26px; padding:40px 36px; text-align:center;
            max-width:420px; width:90%; box-shadow:0 10px 0 rgba(0,0,0,.18);
            animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .overlay-card.lose { border-color:#E05B3C; }
        .overlay-emoji { font-size:80px; margin-bottom:14px; }
        .overlay-title { font-family:'Baloo 2',sans-serif; font-size:26px; font-weight:800; color:var(--panel); margin-bottom:8px; }
        .overlay-sub { font-size:14px; color:var(--ink); margin-bottom:6px; }
        .overlay-pts { font-family:'Baloo 2',sans-serif; font-size:28px; font-weight:800; color:var(--gold-dark); margin:10px 0 24px; }
        .overlay-btns { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn-back { padding:11px 22px; border-radius:14px; font-family:'Baloo 2',sans-serif; background:#fff; color:var(--panel); font-size:13px; font-weight:700; text-decoration:none; border:2px solid var(--panel); }
        .btn-retry { padding:11px 22px; border-radius:14px; font-family:'Baloo 2',sans-serif; font-size:13px; font-weight:700; text-decoration:none; color:#fff; box-shadow:0 4px 0 rgba(0,0,0,.18); }
        .btn-retry.win  { background:linear-gradient(180deg,#8F7AD1,var(--purple)); }
        .btn-retry.lose { background:linear-gradient(180deg,#FF8F6B,#E05B3C); }

        /* ── Keyframes ────────────────────────── */
        @keyframes twinkle { from{opacity:0.3;} to{opacity:1;} }
        @keyframes countPop {
            0%{opacity:0;transform:scale(0.3);} 60%{opacity:1;transform:scale(1.15);}
            80%{transform:scale(0.95);} 100%{opacity:1;transform:scale(1);}
        }
        @keyframes wordPumpIn {
            0%{opacity:0;transform:scale(0.6) translateY(16px);}
            70%{transform:scale(1.06) translateY(-4px);}
            100%{opacity:1;transform:scale(1) translateY(0);}
        }
        @keyframes wordPumpOut {
            0%{opacity:1;transform:scale(1) translateY(0);}
            100%{opacity:0;transform:scale(0.7) translateY(-20px);}
        }
        @keyframes floatUp {
            0%{opacity:1;transform:translateX(-50%) translateY(0);}
            100%{opacity:0;transform:translateX(-50%) translateY(-70px);}
        }
        @keyframes bubblePop {
            0%{opacity:0;transform:translateX(-50%) scale(0.5);}
            70%{transform:translateX(-50%) scale(1.1);}
            100%{opacity:1;transform:translateX(-50%) scale(1);}
        }
        @keyframes hpShake {
            0%,100%{transform:translateX(0);} 20%{transform:translateX(-6px);}
            40%{transform:translateX(5px);} 60%{transform:translateX(-4px);}
            80%{transform:translateX(3px);}
        }
        @keyframes studentHit {
            0%,100%{transform:translateX(0);} 20%{transform:translateX(10px) rotate(3deg);}
            40%{transform:translateX(-8px) rotate(-2deg);} 60%{transform:translateX(6px);}
            80%{transform:translateX(-4px);}
        }
        @keyframes attackDash {
            0%{transform:translateX(0) scaleX(1);} 30%{transform:translateX(200px) scaleX(1.15);}
            55%{transform:translateX(190px) scaleX(0.9) rotate(-8deg);} 80%{transform:translateX(200px) scaleX(1);}
            100%{transform:translateX(0) scaleX(1);}
        }
        @keyframes attackJump {
            0%{transform:translateX(0) translateY(0);} 25%{transform:translateX(80px) translateY(-80px) rotate(10deg);}
            50%{transform:translateX(190px) translateY(0) scaleX(1.2);} 65%{transform:translateX(185px) translateY(10px) scaleX(0.9);}
            80%{transform:translateX(190px) translateY(0);} 100%{transform:translateX(0) translateY(0);}
        }
        @keyframes attackSpin {
            0%{transform:translateX(0) rotate(0deg) scale(1);} 30%{transform:translateX(100px) rotate(360deg) scale(1.2);}
            55%{transform:translateX(190px) rotate(720deg) scale(1.1);} 75%{transform:translateX(190px) rotate(720deg);}
            100%{transform:translateX(0) rotate(0deg) scale(1);}
        }
        @keyframes attackBlink {
            0%{transform:translateX(0);opacity:1;} 25%{transform:translateX(0);opacity:0;}
            26%{transform:translateX(190px);opacity:0;} 45%{transform:translateX(190px) scaleX(1.2);opacity:1;}
            70%{transform:translateX(190px);opacity:1;} 85%{transform:translateX(0);opacity:0;}
            100%{transform:translateX(0);opacity:1;}
        }
        @keyframes attackCharge {
            0%{transform:translateX(0) scale(1);filter:brightness(1);}
            20%{transform:translateX(-20px) scale(0.85);filter:brightness(2) hue-rotate(60deg);}
            40%{transform:translateX(-20px) scale(1.3);filter:brightness(3) hue-rotate(120deg);}
            60%{transform:translateX(200px) scale(1.1);filter:brightness(2);}
            75%{transform:translateX(190px) scale(0.9);filter:brightness(1.5);}
            90%{transform:translateX(190px);filter:brightness(1);}
            100%{transform:translateX(0) scale(1);filter:brightness(1);}
        }
        @keyframes enemyDash {
            0%{transform:translateX(0);} 30%{transform:translateX(-180px) scaleX(1.1);}
            55%{transform:translateX(-170px) scaleX(0.9);} 80%{transform:translateX(-180px);}
            100%{transform:translateX(0);}
        }
        @keyframes enemyLeap {
            0%{transform:translateX(0) translateY(0);} 25%{transform:translateX(-80px) translateY(-70px) rotate(-10deg);}
            50%{transform:translateX(-180px) translateY(0);} 65%{transform:translateX(-175px) translateY(8px);}
            80%{transform:translateX(-180px) translateY(0);} 100%{transform:translateX(0) translateY(0);}
        }
        @keyframes enemyZap {
            0%{transform:translateX(0);filter:brightness(1);} 15%{filter:brightness(3) hue-rotate(120deg);}
            30%{transform:translateX(-160px);filter:brightness(2);}
            50%{transform:translateX(-180px) scaleX(1.15);filter:brightness(1.5);}
            70%{transform:translateX(-180px);filter:brightness(1);}
            100%{transform:translateX(0);filter:brightness(1);}
        }
        @keyframes enemySwipe {
            0%{transform:translateX(0) rotate(0);} 20%{transform:translateX(-60px) rotate(-15deg);}
            45%{transform:translateX(-180px) rotate(5deg);} 65%{transform:translateX(-170px) rotate(-5deg) scaleX(1.1);}
            80%{transform:translateX(-180px) rotate(0);} 100%{transform:translateX(0) rotate(0);}
        }
        @keyframes shakeEnemy {
            0%,100%{transform:translateX(0) rotate(0);} 15%{transform:translateX(-14px) rotate(-4deg);}
            35%{transform:translateX(12px) rotate(3deg);} 55%{transform:translateX(-8px) rotate(-2deg);}
            75%{transform:translateX(6px) rotate(1deg);}
        }
        @keyframes enemyDefeat {
            0%{transform:scale(1) rotate(0);opacity:1;filter:brightness(1);}
            30%{transform:scale(1.4) rotate(10deg);filter:brightness(4) saturate(0);}
            60%{transform:scale(1.2) rotate(20deg);filter:brightness(2);}
            100%{transform:scale(0) rotate(40deg);opacity:0;}
        }
        @keyframes effectSlash {
            0%{opacity:0;transform:translate(-50%,-50%) scale(0.5) rotate(-30deg);}
            40%{opacity:1;transform:translate(-50%,-50%) scale(1.4) rotate(10deg);}
            100%{opacity:0;transform:translate(-50%,-50%) scale(0.8) rotate(20deg);}
        }
        @keyframes effectBoom {
            0%{opacity:0;transform:translate(-50%,-50%) scale(0.2);}
            40%{opacity:1;transform:translate(-50%,-50%) scale(1.6);}
            100%{opacity:0;transform:translate(-50%,-50%) scale(1);}
        }
        @keyframes effectSpin {
            0%{opacity:0;transform:translate(-50%,-50%) scale(0.5) rotate(0deg);}
            50%{opacity:1;transform:translate(-50%,-50%) scale(1.3) rotate(180deg);}
            100%{opacity:0;transform:translate(-50%,-50%) scale(0.8) rotate(360deg);}
        }
        @keyframes auraExcellent { 0%,100%{filter:drop-shadow(0 0 16px #57D67B) brightness(1.3);} 50%{filter:drop-shadow(0 0 32px #57D67B) brightness(1.6);} }
        @keyframes auraGood { 0%,100%{filter:drop-shadow(0 0 14px #5DA9FF) brightness(1.2);} 50%{filter:drop-shadow(0 0 28px #5DA9FF) brightness(1.5);} }
        @keyframes auraWeak { 0%,100%{filter:drop-shadow(0 0 10px #FF5C5C) brightness(1.1);} 50%{filter:drop-shadow(0 0 20px #FF5C5C) brightness(1.3);} }
        @keyframes popIn { 0%{transform:scale(0.5);opacity:0;} 70%{transform:scale(1.05);} 100%{transform:scale(1);opacity:1;} }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        @keyframes micPulse {
            0%,100%{box-shadow:0 0 0 8px rgba(255,111,165,.2),0 5px 0 rgba(0,0,0,.15);}
            50%{box-shadow:0 0 0 14px rgba(255,111,165,.08),0 5px 0 rgba(0,0,0,.15);}
        }
        @keyframes pulse {
            0%,100%{box-shadow:0 0 0 8px rgba(255,111,165,.2),0 4px 0 rgba(0,0,0,.18);}
            50%{box-shadow:0 0 0 14px rgba(255,111,165,.06),0 4px 0 rgba(0,0,0,.18);}
        }
        @keyframes lowHpFlash {
            0%,100%{border-color:#FF5C5C;} 50%{border-color:#FFE1E1;}
        }
    </style>
</head>
<body>

{{-- ── TOP BAR ──────────────────────────────────────────── --}}
<div class="top-bar">
    <button class="quit-btn" onclick="showQuitModal()">
        <i class="ti ti-logout"></i> Quit
    </button>
    <div class="round-badge">
        ⚔️ Round <span id="round-num">{{ $session->rounds_played + 1 }}</span>
        of {{ $totalWords }}
    </div>
    <div class="top-right">
        <span class="hp-display">💥 <span id="top-damage">{{ number_format($session->total_damage) }}</span></span>
        <span class="level-chip">⭐ Level {{ auth()->user()->student->current_level }}</span>
    </div>
</div>

{{-- ── COUNTDOWN OVERLAY ────────────────────────────────── --}}
<div id="countdown-overlay">
    <div class="countdown-number" id="countdown-number">3</div>
    <div class="countdown-label" id="countdown-label">GET READY!</div>
    <div class="countdown-sub" id="countdown-sub">Read the word when it appears</div>
</div>

{{-- ── ARENA ─────────────────────────────────────────────── --}}
<div class="arena">
    <div class="sun"></div>
    <div class="cloud c1"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
    <div class="cloud c2"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
    <div class="cloud c3"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
    <div class="mountains"></div>
    <div class="stars" id="stars"></div>
    <div class="ground"></div>

    {{-- HP section --}}
    <div class="hp-section">
        <div class="hp-block" id="student-hp-block">
            <div class="hp-name">
                <span>{{ auth()->user()->student->firstname }}</span>
                <span class="label">YOU</span>
            </div>
            <div class="hp-bar-bg" id="student-hp-bg">
                <div class="hp-bar-fill student" id="student-hp-bar" style="width:100%;"></div>
            </div>
            <div class="hp-text">
                ❤️ <span id="student-hp-current">{{ $session->enemy_max_hp }}</span>
                / <span id="student-hp-max">{{ $session->enemy_max_hp }}</span>
            </div>
        </div>

        <div class="center-info">
            <div class="vs-badge">VS</div>
            <div id="rounds-left-pill" class="rounds-left-pill">
                {{ $roundsLeft }} round(s) left
            </div>
            <div id="center-word-card">
                <div class="cw-label">📖 Read this aloud:</div>
                <div id="center-word-text" class="{{ $session->activity->level == 3 ? 'paragraph' : '' }}">
                    {{ $currentWord }}
                </div>
                <button id="done-reading-btn" onclick="onDoneReading()">
                    ✅ Done Reading — Attack!
                </button>
            </div>
        </div>

        <div class="hp-block" style="text-align:right;">
            <div class="hp-name" style="justify-content:flex-end;">
                <span class="label">ENEMY</span>
                <span>{{ $session->enemy->name }}</span>
            </div>
            <div class="hp-bar-bg">
                <div id="hp-bar" class="hp-bar-fill enemy" style="width:{{ $hpPercent }}%;margin-left:auto;"></div>
            </div>
            <div class="hp-text">
                ❤️ <span id="hp-current">{{ number_format($session->enemy_current_hp) }}</span>
                / {{ number_format($session->enemy_max_hp) }}
            </div>
        </div>
    </div>

    {{-- Battlefield --}}
    <div class="battlefield">
        <div class="character-wrap" id="student-wrap">
            <div class="character-label">{{ auth()->user()->student->firstname }}</div>
            <div class="char-platform"></div>
            <div class="character-sprite" id="student-sprite">
                <svg viewBox="0 0 200 240">
                    <path d="M60 150 Q100 260 140 150 L150 100 Q100 80 50 100 Z" fill="#3B7DDB"/>
                    <path d="M55 105 L40 150 Q45 165 60 155 Z" fill="#FF6FA5"/>
                    <path d="M145 105 L160 150 Q155 165 140 155 Z" fill="#FF6FA5"/>
                    <rect x="88" y="118" width="24" height="46" rx="8" fill="#FFC93C"/>
                    <circle cx="100" cy="70" r="42" fill="#FFD9B0"/>
                    <path d="M58 60 Q60 20 100 18 Q140 20 142 60 Q120 40 100 42 Q80 40 58 60Z" fill="#7A4A2A"/>
                    <circle cx="84" cy="72" r="6" fill="#2B2140"/>
                    <circle cx="116" cy="72" r="6" fill="#2B2140"/>
                    <path d="M86 92 Q100 100 114 92" stroke="#2B2140" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <circle cx="76" cy="82" r="7" fill="#FF9EBB" opacity=".6"/>
                    <circle cx="124" cy="82" r="7" fill="#FF9EBB" opacity=".6"/>
                    <path d="M44 96 Q20 60 40 30" stroke="#FFD9B0" stroke-width="14" fill="none" stroke-linecap="round"/>
                    <circle cx="38" cy="28" r="10" fill="#FFD9B0"/>
                    <g transform="translate(6,-6) rotate(-18 38 28)">
                        <rect x="30" y="-10" width="16" height="46" rx="6" fill="#FFC93C" stroke="#E0A11B" stroke-width="2"/>
                        <circle cx="38" cy="-14" r="10" fill="#FFF6C9" stroke="#E0A11B" stroke-width="2"/>
                    </g>
                    <path d="M158 96 Q182 66 160 34" stroke="#FFD9B0" stroke-width="14" fill="none" stroke-linecap="round"/>
                    <circle cx="162" cy="32" r="10" fill="#FFD9B0"/>
                </svg>
            </div>
            <div id="student-damage-indicator">💢</div>
        </div>

        <div id="score-reveal">
            <div class="score-reveal-inner">
                <div class="score-reveal-label">🤖 AI Score</div>
                <div class="score-reveal-value"      id="score-reveal-value"></div>
                <div class="score-reveal-damage"     id="score-reveal-damage"></div>
                <div class="score-reveal-transcript" id="score-reveal-transcript"></div>
            </div>
        </div>

        <div id="attack-effect"></div>

        <div class="battle-msg-wrap">
            <div class="battle-msg" id="battle-msg">
                ⏳ Get ready… countdown starting!
                <div class="battle-transcript" id="battle-transcript"></div>
            </div>
        </div>

        <div class="character-wrap" id="enemy-wrap">
            <div class="character-label">{{ $session->enemy->name }}</div>
            <div id="enemy-attack-bubble"></div>
            <div class="char-platform"></div>
            <div id="enemy-sprite" class="enemy-sprite">
                @php $enemyName = trim($session->enemy->name); @endphp
                @if($enemyName === 'Letter Goblin')
                <svg viewBox="0 0 200 240">
                    <path d="M55 150 Q100 250 145 150 L150 110 Q100 90 50 110 Z" fill="#6FBF5A"/>
                    <circle cx="100" cy="80" r="46" fill="#7ED45F"/>
                    <path d="M52 60 Q30 30 44 10 Q60 24 62 46Z" fill="#5AA83F"/>
                    <path d="M148 60 Q170 30 156 10 Q140 24 138 46Z" fill="#5AA83F"/>
                    <circle cx="82" cy="82" r="9" fill="#fff"/><circle cx="118" cy="82" r="9" fill="#fff"/>
                    <circle cx="84" cy="84" r="4" fill="#2B2140"/><circle cx="120" cy="84" r="4" fill="#2B2140"/>
                    <path d="M78 104 Q100 118 122 104" stroke="#2B2140" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <rect x="20" y="140" width="46" height="54" rx="8" fill="#FFC93C" stroke="#5A3E00" stroke-width="3" transform="rotate(-8 43 167)"/>
                    <text x="30" y="178" font-family="Baloo 2" font-size="34" font-weight="700" fill="#5A3E00" transform="rotate(-8 43 167)">A</text>
                </svg>
                @elseif($enemyName === 'Word Witch')
                <svg viewBox="0 0 200 240">
                    <path d="M55 145 Q100 250 145 145 L150 100 Q100 82 50 100 Z" fill="#7A56C9"/>
                    <circle cx="100" cy="75" r="40" fill="#F3D9C4"/>
                    <path d="M52 62 Q100 -10 148 62 Q130 50 100 52 Q70 50 52 62Z" fill="#3B2E63"/>
                    <circle cx="130" cy="10" r="8" fill="#FFC93C"/>
                    <circle cx="84" cy="78" r="6" fill="#2B2140"/><circle cx="116" cy="78" r="6" fill="#2B2140"/>
                    <path d="M88 96 Q100 90 112 96" stroke="#2B2140" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M150 150 Q190 130 185 90" stroke="#3B2E63" stroke-width="10" fill="none" stroke-linecap="round"/>
                    <circle cx="184" cy="86" r="7" fill="#FFC93C"/>
                    <circle cx="200" cy="60" r="4" fill="#FFC93C"/><circle cx="192" cy="40" r="3" fill="#FF6FA5"/>
                </svg>
                @elseif($enemyName === 'Story Dragon')
                <svg viewBox="0 0 200 240">
                    <path d="M40 150 Q10 110 45 90 Q60 120 70 140Z" fill="#E0A24C"/>
                    <path d="M160 150 Q190 110 155 90 Q140 120 130 140Z" fill="#E0A24C"/>
                    <path d="M50 150 Q100 245 150 150 L155 105 Q100 85 45 105 Z" fill="#E07A4C"/>
                    <circle cx="100" cy="78" r="44" fill="#EB8E5E"/>
                    <path d="M70 45 L80 20 L90 48Z" fill="#B3502A"/>
                    <path d="M110 48 L120 20 L130 45Z" fill="#B3502A"/>
                    <circle cx="83" cy="82" r="8" fill="#FFE9A8"/><circle cx="117" cy="82" r="8" fill="#FFE9A8"/>
                    <circle cx="85" cy="84" r="4" fill="#2B2140"/><circle cx="119" cy="84" r="4" fill="#2B2140"/>
                    <path d="M85 104 Q100 96 115 104" stroke="#5A2E14" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M96 106 Q100 116 104 106" fill="#FF6F3C"/>
                </svg>
                @else
                <div style="font-size:90px;line-height:1;">{{ $session->enemy->sprite }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── BOTTOM PANEL ──────────────────────────────────── --}}
    <div class="bottom-panel">
        <div class="dots-wrap">
            @foreach($allWords as $i => $w)
            <div id="dot-{{ $i }}" class="dot"
                 style="background:{{ $i < $roundIndex ? '#57D67B' : ($i == $roundIndex ? '#7C3AED' : '#E6DEFA') }};
                        {{ $i == $roundIndex ? 'box-shadow:0 0 0 3px rgba(124,58,237,0.3);' : '' }}">
            </div>
            @endforeach
        </div>

        <div class="bottom-inner">

            {{-- Waveform --}}
            <div class="waveform-wrap" id="waveform-wrap">
                @for($i = 0; $i < 18; $i++)<div class="wv"></div>@endfor
            </div>

            {{-- Hold to record button --}}
            <div class="hold-rec-wrap">
                <button class="hold-rec-btn" id="hold-rec-btn" disabled
                        onmousedown="startRecording()"
                        onmouseup="stopRecording()"
                        ontouchstart="startRecording(event)"
                        ontouchend="stopRecording(event)">
                    <i class="ti ti-microphone" id="hold-rec-icon"></i>
                </button>
                <div class="hold-rec-timer" id="hold-timer">0:00</div>
                <div class="hold-rec-label" id="hold-label">Hold to record</div>
            </div>

            {{-- Loading state --}}
            <div class="loading-wrap" id="loading-wrap">
                <div class="spinner-border" style="width:20px;height:20px;border-color:var(--purple);border-right-color:transparent;"></div>
                <span id="loading-text">🤖 AI analyzing…</span>
            </div>

            {{-- History --}}
            <div class="history-panel">
                <div class="history-title">📋 Round History</div>
                <div id="round-history">
                    @forelse($session->rounds->sortByDesc('created_at') as $round)
                    <div class="history-item">
                        <span class="hi-score"
                              style="color:{{ $round->ml_score >= 90 ? '#3FA86A' : ($round->ml_score >= 70 ? '#3D82D9' : ($round->ml_score >= 50 ? '#E0A11B' : '#E05B3C')) }}">
                            {{ $round->ml_score ?? '—' }}%
                        </span>
                        <span class="hi-dmg"> -{{ $round->damage_dealt }}HP</span>
                        <div style="color:rgba(43,33,64,.45);margin-top:2px;font-size:9px;">
                            "{{ Str::limit($round->word_or_passage, 14) }}"
                        </div>
                    </div>
                    @empty
                    <div style="font-size:10px;color:rgba(43,33,64,.4);text-align:center;padding:8px;">
                        No rounds yet. Start attacking! ⚔️
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── QUIT MODAL ───────────────────────────────────────── --}}
<div class="quit-modal" id="quit-modal">
    <div class="quit-modal-card">
        <div class="quit-modal-emoji">⚠️</div>
        <div class="quit-modal-title">Quit Battle?</div>
        <div class="quit-modal-sub">
            Are you sure you want to leave the battle against
            <strong style="color:#E05B3C;">{{ $session->enemy->name }}</strong>?
        </div>
        <div class="quit-modal-note">Your progress will be saved. You can continue this battle later.</div>
        <div class="quit-modal-btns">
            <button class="btn-stay" onclick="hideQuitModal()">⚔️ Stay & Fight!</button>
            <a href="{{ route('student.game.index') }}" class="btn-quit-confirm">🚪 Yes, Quit</a>
        </div>
    </div>
</div>

{{-- ── WIN OVERLAY ─────────────────────────────────────── --}}
<div class="overlay" id="win-overlay">
    <div class="overlay-card">
        <div class="overlay-emoji">🏆</div>
        <div class="overlay-title">Victory!</div>
        <div class="overlay-sub" id="win-msg"></div>
        <div class="overlay-sub" style="font-size:12px;color:rgba(43,33,64,.5);" id="win-transcript"></div>
        <div class="overlay-pts" id="win-pts"></div>
        <div class="overlay-btns">
            <a href="{{ route('student.game.index') }}" class="btn-back">🏰 Back to Arena</a>
            <a href="{{ route('student.game.start', $session->activity_id) }}" class="btn-retry win">⚔️ Battle Again</a>
        </div>
    </div>
</div>

{{-- ── LOSE OVERLAY ─────────────────────────────────────── --}}
<div class="overlay" id="lose-overlay">
    <div class="overlay-card lose">
        <div class="overlay-emoji">💀</div>
        <div class="overlay-title" id="lose-title">You Lost!</div>
        <div class="overlay-sub" id="lose-msg"></div>
        <div class="overlay-sub" style="font-size:12px;color:rgba(43,33,64,.5);" id="lose-rounds"></div>
        <div class="overlay-sub" style="font-size:12px;color:rgba(43,33,64,.5);margin-bottom:20px;" id="lose-hp"></div>
        <div class="overlay-btns">
            <a href="{{ route('student.game.index') }}" class="btn-back">🏰 Back to Arena</a>
            <a href="{{ route('student.game.start', $session->activity_id) }}" class="btn-retry lose">⚔️ Try Again!</a>
        </div>
    </div>
</div>

{{-- Hidden data --}}
<input type="hidden" id="session-id"          value="{{ $session->id }}">
<input type="hidden" id="current-word-value"  value="{{ $currentWord }}">
<input type="hidden" id="current-round-index" value="{{ $roundIndex }}">
<input type="hidden" id="total-words"         value="{{ $totalWords }}">
<input type="hidden" id="rounds-left-value"   value="{{ $roundsLeft }}">
<input type="hidden" id="enemy-max-hp"        value="{{ $session->enemy_max_hp }}">
<input type="hidden" id="is-paragraph"        value="{{ $session->activity->level == 3 ? '1' : '0' }}">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Audio context for sound effects ────────────────────────────
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let audioCtx = null;

function getAudioCtx() {
    if (!audioCtx) audioCtx = new AudioCtx();
    return audioCtx;
}

// Generate a simple synthesized sound
function playSound(type) {
    try {
        const ctx = getAudioCtx();

        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        const now = ctx.currentTime;

        if (type === 'excellent') {
            // Power burst — rising triumphant chord
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, now);
            osc.frequency.exponentialRampToValueAtTime(880, now + 0.15);
            osc.frequency.exponentialRampToValueAtTime(1320, now + 0.3);
            gain.gain.setValueAtTime(0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
            osc.start(now); osc.stop(now + 0.6);

            // Second oscillator for harmony
            const osc2 = ctx.createOscillator();
            const g2   = ctx.createGain();
            osc2.connect(g2); g2.connect(ctx.destination);
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(440, now);
            osc2.frequency.exponentialRampToValueAtTime(1760, now + 0.4);
            g2.gain.setValueAtTime(0.25, now);
            g2.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            osc2.start(now); osc2.stop(now + 0.5);

        } else if (type === 'great') {
            // Spin charge — swirling whoosh
            osc.type = 'sine';
            osc.frequency.setValueAtTime(300, now);
            osc.frequency.exponentialRampToValueAtTime(900, now + 0.2);
            osc.frequency.exponentialRampToValueAtTime(600, now + 0.4);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            osc.start(now); osc.stop(now + 0.5);

        } else if (type === 'good') {
            // Jump slam — thud
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(180, now);
            osc.frequency.exponentialRampToValueAtTime(60, now + 0.25);
            gain.gain.setValueAtTime(0.5, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
            osc.start(now); osc.stop(now + 0.35);

        } else if (type === 'ok') {
            // Blink strike — quick zap
            osc.type = 'square';
            osc.frequency.setValueAtTime(400, now);
            osc.frequency.exponentialRampToValueAtTime(100, now + 0.15);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
            osc.start(now); osc.stop(now + 0.2);

        } else if (type === 'weak') {
            // Dash strike — soft thwack
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(120, now);
            osc.frequency.exponentialRampToValueAtTime(80, now + 0.2);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
            osc.start(now); osc.stop(now + 0.25);

        } else if (type === 'enemy_hit') {
            // Student gets hit — low thud + noise
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(80, now);
            osc.frequency.exponentialRampToValueAtTime(40, now + 0.3);
            gain.gain.setValueAtTime(0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
            osc.start(now); osc.stop(now + 0.4);

        } else if (type === 'countdown') {
            // Countdown beep
            osc.type = 'sine';
            osc.frequency.setValueAtTime(660, now);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
            osc.start(now); osc.stop(now + 0.15);

        } else if (type === 'go') {
            // GO! — fanfare
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(523, now);
            osc.frequency.setValueAtTime(659, now + 0.1);
            osc.frequency.setValueAtTime(784, now + 0.2);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            osc.start(now); osc.stop(now + 0.5);

        } else if (type === 'win') {
            // Victory fanfare
            const notes = [523, 659, 784, 1047];
            notes.forEach((freq, i) => {
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.type = 'sawtooth';
                o.frequency.setValueAtTime(freq, now + i * 0.12);
                g.gain.setValueAtTime(0.3, now + i * 0.12);
                g.gain.exponentialRampToValueAtTime(0.001, now + i * 0.12 + 0.4);
                o.start(now + i * 0.12); o.stop(now + i * 0.12 + 0.4);
            });
            return;

        } else if (type === 'lose') {
            // Sad descend
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(400, now);
            osc.frequency.exponentialRampToValueAtTime(100, now + 0.8);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
            osc.start(now); osc.stop(now + 0.9);
        }

    } catch(e) { /* audio context blocked */ }
}

// Pick sound based on score
function soundForScore(score) {
    if (score === null) return 'weak';
    if (score >= 90) return 'excellent';
    if (score >= 75) return 'great';
    if (score >= 60) return 'good';
    if (score >= 40) return 'ok';
    return 'weak';
}

// ── Stars ──────────────────────────────────────────────────────
(function() {
    const c = document.getElementById('stars');
    for (let i = 0; i < 30; i++) {
        const s = document.createElement('div');
        s.className = 'star';
        const sz = Math.random() * 2.5 + 0.5;
        s.style.cssText = `width:${sz}px;height:${sz}px;
            top:${Math.random()*55}%;left:${Math.random()*100}%;
            animation-delay:${Math.random()*2}s;
            animation-duration:${1.5+Math.random()*2}s;`;
        c.appendChild(s);
    }
})();

// ── Quit modal ─────────────────────────────────────────────────
function showQuitModal() {
    document.getElementById('quit-modal').style.display = 'flex';
}
function hideQuitModal() {
    document.getElementById('quit-modal').style.display = 'none';
}
document.getElementById('quit-modal').addEventListener('click', function(e) {
    if (e.target === this) hideQuitModal();
});

// ── Attack pools ───────────────────────────────────────────────
const ENEMY_ATTACK_POOL = [
    'Mumbo Jumbo!','Abra Kadabra!','Fizzle Wizzle!','Blunder Blitz!',
    'Kerplunk!','Boo!','Grrr!','Roooaar!','Bwahahaha!','Zap!',
    '📖 Garble!','🌀 Confuse!','🔥 Burn!','💨 Whoosh!','⚡ Shock!',
    'Flibbertigibbet!','Zippity Zap!','Hocus Pocus!','Wacka Wacka!',
];
const ENEMY_ATTACK_STYLES = [
    { anim:'enemyDash',  dur:900,  effect:'💨', color:'#FF5C5C' },
    { anim:'enemyLeap',  dur:1000, effect:'💥', color:'#FFC93C' },
    { anim:'enemyZap',   dur:900,  effect:'⚡', color:'#5DA9FF' },
    { anim:'enemySwipe', dur:950,  effect:'🌀', color:'#B79CE0' },
];
const ATTACK_STYLES = [
    { name:'Dash Strike',  anim:'attackDash',   dur:900,  effect:'⚡', effectAnim:'effectSlash', effectDur:600, color:'#FFC93C' },
    { name:'Jump Slam',    anim:'attackJump',   dur:1000, effect:'💥', effectAnim:'effectBoom',  effectDur:700, color:'#FF5C5C' },
    { name:'Spin Charge',  anim:'attackSpin',   dur:1100, effect:'🌀', effectAnim:'effectSpin',  effectDur:800, color:'#B79CE0' },
    { name:'Blink Strike', anim:'attackBlink',  dur:900,  effect:'✨', effectAnim:'effectSlash', effectDur:500, color:'#5DA9FF' },
    { name:'Power Burst',  anim:'attackCharge', dur:1200, effect:'🔥', effectAnim:'effectBoom',  effectDur:900, color:'#57D67B' },
];
function pickAttackStyle(score) {
    if (score >= 90) return ATTACK_STYLES[4];
    if (score >= 75) return ATTACK_STYLES[2];
    if (score >= 60) return ATTACK_STYLES[1];
    if (score >= 40) return ATTACK_STYLES[3];
    return ATTACK_STYLES[0];
}
function pickRandom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

// ── Student HP state ───────────────────────────────────────────
const studentMaxHp   = parseInt(document.getElementById('enemy-max-hp').value);
let   studentCurrentHp = studentMaxHp;

function updateStudentHpBar(newHp) {
    studentCurrentHp = Math.max(0, newHp);
    const pct = Math.round((studentCurrentHp / studentMaxHp) * 100);
    const bar = document.getElementById('student-hp-bar');
    const bg  = document.getElementById('student-hp-bg');
    const blk = document.getElementById('student-hp-block');

    bar.style.width = pct + '%';
    document.getElementById('student-hp-current').textContent = studentCurrentHp;

    if (pct <= 25) {
        bar.style.background = 'linear-gradient(180deg,#FF8A8A,#FF5C5C)';
        blk.style.animation = 'lowHpFlash 0.5s infinite';
    } else if (pct <= 50) {
        bar.style.background = 'linear-gradient(180deg,#FFC57A,#FFA53C)';
        blk.style.animation = '';
    } else {
        bar.style.background = 'linear-gradient(180deg,#7CE79A,#57D67B)';
        blk.style.animation = '';
    }
}

// Enemy damage to student = random 8–15% of student max HP per hit
function calcEnemyDamage() {
    const pct = 0.08 + Math.random() * 0.07; // 8–15%
    return Math.max(1, Math.round(studentMaxHp * pct));
}

// ── State ──────────────────────────────────────────────────────
let mediaRecorder, audioChunks = [], isRecording = false;
let timerInterval, seconds = 0, waveInterval = null;
let battleLocked = false;
let gamePhase    = 'countdown';

// ── DOM refs ───────────────────────────────────────────────────
const holdBtn     = document.getElementById('hold-rec-btn');
const holdIcon    = document.getElementById('hold-rec-icon');
const holdLabel   = document.getElementById('hold-label');
const holdTimer   = document.getElementById('hold-timer');
const loadingWrap = document.getElementById('loading-wrap');
const loadingText = document.getElementById('loading-text');
const waveWrap    = document.getElementById('waveform-wrap');
const wvBars      = document.querySelectorAll('.wv');
const sessionId   = document.getElementById('session-id').value;
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
const enemyMaxHp  = parseInt(document.getElementById('enemy-max-hp').value);
const enemyName   = '{{ $session->enemy->name }}';
const totalWords  = parseInt(document.getElementById('total-words').value);
const wordCard    = document.getElementById('center-word-card');
const doneBtn     = document.getElementById('done-reading-btn');
const currentWord = document.getElementById('current-word-value').value;

// ── COUNTDOWN ──────────────────────────────────────────────────
function startCountdown() {
    const overlay = document.getElementById('countdown-overlay');
    const numEl   = document.getElementById('countdown-number');
    const lblEl   = document.getElementById('countdown-label');
    const subEl   = document.getElementById('countdown-sub');

    overlay.style.display = 'flex';
    setBattleMsg('⏳ Get ready…');
    let count = 3;

    function tick() {
        numEl.style.animation = 'none';
        void numEl.offsetWidth;
        numEl.style.animation = 'countPop 0.9s cubic-bezier(0.34,1.56,0.64,1) forwards';

        if (count > 0) {
            playSound('countdown');
            numEl.textContent = count;
            numEl.style.color = count === 3 ? '#5DA9FF' : count === 2 ? '#FFC93C' : '#FF5C5C';
            lblEl.textContent = count === 3 ? 'GET READY!' : count === 2 ? 'ALMOST…' : 'GO!';
            count--;
            setTimeout(tick, 950);
        } else {
            playSound('go');
            numEl.textContent = '⚔️';
            numEl.style.color = '#57D67B';
            lblEl.textContent = 'READ IT!';
            subEl.textContent = 'Read the passage aloud then press Done!';
            setTimeout(() => { overlay.style.display = 'none'; showWordCard(); }, 900);
        }
    }
    tick();
}

function showWordCard() {
    gamePhase = 'reading';
    wordCard.style.animation = 'none';
    void wordCard.offsetWidth;
    wordCard.style.animation = 'wordPumpIn 0.5s ease forwards';
    wordCard.style.display   = 'block';
    doneBtn.style.display    = 'inline-block';
    setBattleMsg('📖 Read the passage above, then press Done Reading!');
    holdBtn.disabled = false;
    holdLabel.textContent = 'Hold to record';
}

function hideWordCard(callback) {
    wordCard.style.animation = 'wordPumpOut 0.4s ease forwards';
    setTimeout(() => {
        wordCard.style.display = 'none';
        wordCard.style.animation = '';
        if (callback) callback();
    }, 400);
}

function onDoneReading() {
    if (gamePhase !== 'reading') return;
    gamePhase = 'recording';
    doneBtn.style.display = 'none';
    hideWordCard(() => {
        setBattleMsg('🎙️ Hold the mic button and read aloud!');
        holdLabel.textContent = 'Hold to record';
    });
}

// ── Hold-to-record ─────────────────────────────────────────────
function startRecording(e) {
    if (e) e.preventDefault();
    if (battleLocked || holdBtn.disabled || isRecording) return;

    // Unlock audio context on user gesture
    getAudioCtx();

    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        mediaRecorder  = new MediaRecorder(stream);
        audioChunks    = [];
        mediaRecorder.ondataavailable = ev => { if (ev.data.size > 0) audioChunks.push(ev.data); };
        mediaRecorder.onstop = () => submitRecording();
        mediaRecorder.start(100);
        isRecording = true;

        holdBtn.classList.add('active');
        holdIcon.className    = 'ti ti-player-stop';
        holdLabel.textContent = 'Release to stop';
        holdTimer.style.display = 'block';
        waveWrap.style.display  = 'flex';

        seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            holdTimer.textContent =
                Math.floor(seconds/60).toString().padStart(2,'0') + ':' +
                (seconds%60).toString().padStart(2,'0');
        }, 1000);
        animateWaveform();

    }).catch(() => alert('Microphone access denied! Please allow microphone access.'));
}

function stopRecording(e) {
    if (e) e.preventDefault();
    if (!isRecording || !mediaRecorder) return;

    mediaRecorder.stop();
    mediaRecorder.stream.getTracks().forEach(t => t.stop());
    isRecording = false;
    clearInterval(timerInterval);

    holdBtn.classList.remove('active');
    holdBtn.classList.add('loading');
    holdBtn.disabled      = true;
    holdIcon.className    = 'ti ti-loader';
    holdLabel.textContent = 'Analyzing…';
    holdTimer.style.display = 'none';
    waveWrap.style.display  = 'none';
    stopWaveform();
}

// ── Submit recording automatically ────────────────────────────
async function submitRecording() {
    if (!audioChunks.length) return;

    battleLocked = true;
    loadingWrap.style.display = 'flex';
    loadingText.textContent   = '🤖 AI is analyzing your reading…';

    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    const formData  = new FormData();
    formData.append('recording', new File([audioBlob], 'attack.webm', { type: 'audio/webm' }));
    formData.append('word_or_passage', currentWord);
    formData.append('_token', csrfToken);

    try {
        const res = await fetch(`/student/game/battle/${sessionId}/round`, {
            method: 'POST', body: formData
        });
        if (!res.ok) throw new Error(`Server error ${res.status}`);
        const data = await res.json();

        loadingWrap.style.display = 'none';
        holdBtn.classList.remove('loading');

        // 1. Play score sound
        playSound(soundForScore(data.ml_score));

        // 2. Show score reveal
        await showScoreReveal(data);

        // 3. Student aura
        applyStudentAura(data.ml_score);

        // 4. Student attacks
        const style = data.ml_score !== null ? pickAttackStyle(data.ml_score) : pickRandom(ATTACK_STYLES);
        await playStudentAttack(style);

        // 5. Enemy gets hit
        playEnemyHit(data.damage, data.ml_score, style);
        updateEnemyHpBar(data.enemy_hp ?? 0, data.hp_percent ?? 0);
        updateStats(data);
        addHistory(data, currentWord);
        if (data.rounds_left !== undefined) updateRoundsLeft(data.rounds_left);

        // 6. Win / Lose / Ongoing
        if (data.status === 'won') {
            setBattleMsg('🎉 Final blow landed!');
            playSound('win');
            await delay(600);
            await playEnemyDefeat();
            setTimeout(() => showWin(data), 400);

        } else if (data.status === 'lost') {
            showBattleMsg(data);
            await delay(600);
            // Enemy does FINAL attack — depletes student HP fully
            await playEnemyFinalBlow();
            setTimeout(() => { playSound('lose'); showLose(data); }, 800);

        } else if (data.status === 'ongoing') {
            showBattleMsg(data);
            await delay(600);
            // 7. Enemy counterattack with HP damage to student
            await playEnemyCounterAttack(false);
            await delay(400);
            moveToNext();
        } else {
            setBattleMsg('⏳ Recording submitted! Waiting for AI scoring…');
            battleLocked = false;
            resetHoldBtn();
        }

    } catch(err) {
        loadingWrap.style.display = 'none';
        holdBtn.classList.remove('loading');
        console.error(err);
        setBattleMsg('❌ Something went wrong. Try again!');
        battleLocked = false;
        resetHoldBtn();
    }
}

// ── Score reveal ───────────────────────────────────────────────
function showScoreReveal(data) {
    return new Promise(resolve => {
        const panel   = document.getElementById('score-reveal');
        const valEl   = document.getElementById('score-reveal-value');
        const dmgEl   = document.getElementById('score-reveal-damage');
        const transEl = document.getElementById('score-reveal-transcript');

        const s = data.ml_score;
        let color = '#9CA3AF', label = '😐 Keep trying!';
        if      (s === null) { color = '#9CA3AF'; label = '⏳ Pending'; }
        else if (s >= 90)    { color = '#3FA86A'; label = '🔥 Excellent!'; }
        else if (s >= 75)    { color = '#3D82D9'; label = '⚔️ Great!'; }
        else if (s >= 60)    { color = '#E0A11B'; label = '👍 Good!'; }
        else if (s >= 40)    { color = '#E07A2C'; label = '💪 OK!'; }
        else                 { color = '#E05B3C'; label = '😅 Try harder!'; }

        valEl.style.color   = color;
        valEl.textContent   = s !== null ? `${s}% — ${label}` : '— Pending';
        dmgEl.textContent   = `💥 ${data.damage} damage dealt!`;
        transEl.textContent = data.transcript ? `🎙️ "${data.transcript}"` : '';

        panel.style.display = 'block';
        setTimeout(() => { panel.style.display = 'none'; resolve(); }, 1400);
    });
}

// ── Student attack ─────────────────────────────────────────────
function playStudentAttack(style) {
    return new Promise(resolve => {
        const wrap = document.getElementById('student-wrap');
        wrap.style.animation = 'none';
        void wrap.offsetWidth;
        wrap.style.animation = `${style.anim} ${style.dur}ms cubic-bezier(0.34,1.56,0.64,1) forwards`;
        setBattleMsg(`⚔️ ${style.name}!`);
        setTimeout(() => { wrap.style.animation = ''; resolve(); }, style.dur);
    });
}

// ── Enemy hit ──────────────────────────────────────────────────
function playEnemyHit(damage, score, style) {
    const sprite = document.getElementById('enemy-sprite');
    const wrap   = document.getElementById('enemy-wrap');

    sprite.style.animation = 'none';
    void sprite.offsetWidth;
    sprite.style.animation = 'shakeEnemy 0.6s ease';
    sprite.style.filter    = 'brightness(4) saturate(0)';
    setTimeout(() => {
        sprite.style.filter    = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
        sprite.style.animation = '';
    }, 600);

    const effect = document.getElementById('attack-effect');
    effect.textContent = style.effect;
    effect.style.animation = 'none';
    void effect.offsetWidth;
    const eRect = wrap.getBoundingClientRect();
    const aRect = document.querySelector('.arena').getBoundingClientRect();
    effect.style.cssText = `
        position:absolute;font-size:64px;pointer-events:none;z-index:25;
        top:${eRect.top - aRect.top + 10}px;
        left:${eRect.left - aRect.left + eRect.width/2}px;
        animation:${style.effectAnim} ${style.effectDur}ms ease forwards;
    `;
    setTimeout(() => { effect.textContent = ''; }, style.effectDur + 100);

    const floater = document.createElement('div');
    floater.className   = 'damage-floater';
    floater.style.color = style.color;
    floater.textContent = `-${damage} ${style.effect}`;
    wrap.appendChild(floater);
    setTimeout(() => floater.remove(), 1300);
}

// ── Enemy counterattack (mid-battle) ──────────────────────────
function playEnemyCounterAttack(isFinal = false) {
    return new Promise(resolve => {
        const enemySprite   = document.getElementById('enemy-sprite');
        const studentSprite = document.getElementById('student-sprite');
        const bubble        = document.getElementById('enemy-attack-bubble');
        const hpBg          = document.getElementById('student-hp-bg');
        const dmgInd        = document.getElementById('student-damage-indicator');

        const style   = pickRandom(ENEMY_ATTACK_STYLES);
        const word    = pickRandom(ENEMY_ATTACK_POOL);
        const dmgText = ['💢','😵','💫','🌀','😤'][Math.floor(Math.random()*5)];

        // Enemy damage to student
        const enemyDmg = calcEnemyDamage();

        bubble.textContent   = word;
        bubble.style.display = 'block';

        setTimeout(() => {
            enemySprite.style.animation = 'none';
            void enemySprite.offsetWidth;
            enemySprite.style.animation = `${style.anim} ${style.dur}ms cubic-bezier(0.34,1.56,0.64,1) forwards`;
            enemySprite.style.filter    = `drop-shadow(0 0 20px ${style.color})`;
            setBattleMsg(`${enemyName} counters: "${word}"`);
        }, 300);

        setTimeout(() => {
            // Student takes damage
            playSound('enemy_hit');
            studentSprite.style.animation = 'none';
            void studentSprite.offsetWidth;
            studentSprite.style.animation = 'studentHit 0.5s ease';
            studentSprite.style.filter    = 'brightness(3) saturate(0)';

            hpBg.classList.remove('shake');
            void hpBg.offsetWidth;
            hpBg.classList.add('shake');

            // Update student HP
            updateStudentHpBar(studentCurrentHp - enemyDmg);

            // Damage indicator on student
            dmgInd.textContent     = `-${enemyDmg} ${dmgText}`;
            dmgInd.style.display   = 'block';
            dmgInd.style.animation = 'none';
            void dmgInd.offsetWidth;
            dmgInd.style.animation = 'floatUp 1s ease forwards';

            setTimeout(() => {
                studentSprite.style.filter    = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
                studentSprite.style.animation = '';
                hpBg.classList.remove('shake');
                dmgInd.style.display = 'none';
            }, 600);
        }, style.dur * 0.55);

        setTimeout(() => {
            enemySprite.style.animation = '';
            enemySprite.style.filter    = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
            bubble.style.display        = 'none';
            resolve();
        }, style.dur + 200);
    });
}

// ── Enemy FINAL blow — depletes student HP fully ───────────────
function playEnemyFinalBlow() {
    return new Promise(async resolve => {
        const enemySprite   = document.getElementById('enemy-sprite');
        const studentSprite = document.getElementById('student-sprite');
        const bubble        = document.getElementById('enemy-attack-bubble');
        const hpBg          = document.getElementById('student-hp-bg');
        const dmgInd        = document.getElementById('student-damage-indicator');

        const style = pickRandom(ENEMY_ATTACK_STYLES);

        bubble.textContent   = '🔥 FINAL BLOW!';
        bubble.style.display = 'block';

        await delay(400);

        // Enemy charges with all styles back to back
        for (let i = 0; i < 2; i++) {
            const s = ENEMY_ATTACK_STYLES[i];
            enemySprite.style.animation = 'none';
            void enemySprite.offsetWidth;
            enemySprite.style.animation = `${s.anim} ${s.dur}ms cubic-bezier(0.34,1.56,0.64,1) forwards`;
            enemySprite.style.filter    = `drop-shadow(0 0 30px #FF5C5C)`;
            await delay(s.dur * 0.5);
        }

        // Student takes full HP damage
        playSound('enemy_hit');
        studentSprite.style.animation = 'none';
        void studentSprite.offsetWidth;
        studentSprite.style.animation = 'studentHit 0.6s ease';
        studentSprite.style.filter    = 'brightness(4) saturate(0)';

        hpBg.classList.remove('shake');
        void hpBg.offsetWidth;
        hpBg.classList.add('shake');

        // Drain HP to 0
        const startHp = studentCurrentHp;
        const steps   = 12;
        for (let i = 1; i <= steps; i++) {
            await delay(60);
            updateStudentHpBar(Math.round(startHp * (1 - i / steps)));
        }

        dmgInd.textContent     = `💀 KO!`;
        dmgInd.style.display   = 'block';
        dmgInd.style.animation = 'none';
        void dmgInd.offsetWidth;
        dmgInd.style.animation = 'floatUp 1.2s ease forwards';

        setTimeout(() => {
            bubble.style.display        = 'none';
            enemySprite.style.animation = '';
            enemySprite.style.filter    = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
            studentSprite.style.filter  = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
            studentSprite.style.animation = '';
            dmgInd.style.display = 'none';
            resolve();
        }, 1200);
    });
}

// ── Enemy defeat ───────────────────────────────────────────────
function playEnemyDefeat() {
    return new Promise(resolve => {
        const sprite = document.getElementById('enemy-sprite');
        sprite.style.animation = 'enemyDefeat 0.9s ease forwards';
        setTimeout(resolve, 900);
    });
}

// ── Student aura ───────────────────────────────────────────────
function applyStudentAura(score) {
    const sprite = document.getElementById('student-sprite');
    sprite.style.animation = 'none';
    void sprite.offsetWidth;
    if      (score >= 80) sprite.style.animation = 'auraExcellent 0.6s ease 3';
    else if (score >= 55) sprite.style.animation = 'auraGood 0.6s ease 2';
    else if (score !== null) sprite.style.animation = 'auraWeak 0.6s ease 2';
    setTimeout(() => {
        sprite.style.animation = '';
        sprite.style.filter    = 'drop-shadow(0 8px 6px rgba(0,0,0,.25))';
    }, 2000);
}

// ── HP bar updates ──────────────────────────────────────────────
function updateEnemyHpBar(newHp, hpPercent) {
    const bar = document.getElementById('hp-bar');
    bar.style.width = Math.max(0, hpPercent) + '%';
    document.getElementById('hp-current').textContent = newHp.toLocaleString();
    if      (hpPercent <= 25) bar.style.background = 'linear-gradient(180deg,#FF8A8A,#FF5C5C)';
    else if (hpPercent <= 50) bar.style.background = 'linear-gradient(180deg,#FFC57A,#FFA53C)';
    else                      bar.style.background = 'linear-gradient(180deg,#FF8A8A,#FF5C5C)';
}

function updateStats(data) {
    const oldDmg = parseInt(document.getElementById('top-damage').textContent.replace(/,/g,''));
    const oldRnd = parseInt(document.getElementById('round-num').textContent);
    document.getElementById('top-damage').textContent = (oldDmg + data.damage).toLocaleString();
    document.getElementById('round-num').textContent  = Math.min(oldRnd + 1, totalWords);
}

function updateRoundsLeft(left) {
    const pill = document.getElementById('rounds-left-pill');
    pill.textContent = `${left} round(s) left`;
    if (left <= 2) {
        pill.style.background = '#FFE1E1';
        pill.style.color      = '#B3261E';
        pill.style.borderColor= '#B3261E';
    } else if (left <= 4) {
        pill.style.background = '#FFF1D6';
        pill.style.color      = '#B3720A';
        pill.style.borderColor= '#E0A11B';
    }
}

function showBattleMsg(data) {
    document.getElementById('battle-msg').childNodes[0].textContent = data.message;
    const trans = document.getElementById('battle-transcript');
    if (data.transcript) {
        trans.style.display = 'block';
        trans.textContent   = `🎙️ AI heard: "${data.transcript}"`;
    } else {
        trans.style.display = 'none';
    }
}

function setBattleMsg(text) {
    document.getElementById('battle-msg').childNodes[0].textContent = text;
    document.getElementById('battle-transcript').style.display = 'none';
}

function addHistory(data, word) {
    const hist  = document.getElementById('round-history');
    const empty = hist.querySelector('[style*="No rounds"]');
    if (empty) empty.remove();
    const sc = data.ml_score;
    const c  = sc >= 90 ? '#3FA86A' : sc >= 70 ? '#3D82D9' : sc >= 50 ? '#E0A11B' : '#E05B3C';
    const div = document.createElement('div');
    div.className = 'history-item';
    div.innerHTML = `
        <span class="hi-score" style="color:${c}">${sc !== null ? sc + '%' : '—'}</span>
        <span class="hi-dmg"> -${data.damage}HP</span>
        ${data.transcript ? `<div style="color:rgba(43,33,64,.5);margin-top:2px;font-size:9px;">🎙️ "${data.transcript.substring(0,16)}"</div>` : ''}
        <div style="color:rgba(43,33,64,.4);font-size:9px;">"${word.substring(0,14)}"</div>
    `;
    hist.insertBefore(div, hist.firstChild);
}

function moveToNext() {
    const idx = parseInt(document.getElementById('current-round-index').value);
    const dot  = document.getElementById(`dot-${idx}`);
    if (dot) { dot.style.background = '#57D67B'; dot.style.boxShadow = 'none'; }
    window.location.reload();
}

function showWin(data) {
    document.getElementById('win-msg').textContent  = data.message;
    document.getElementById('win-pts').textContent  = `+${data.points} ⭐ pts!`;
    if (data.transcript)
        document.getElementById('win-transcript').textContent = `🎙️ Last read: "${data.transcript}"`;
    document.getElementById('win-overlay').style.display = 'flex';
}

function showLose(data) {
    document.getElementById('lose-title').textContent  = `You Lost to ${data.enemy_name}!`;
    document.getElementById('lose-msg').textContent    = data.message;
    document.getElementById('lose-rounds').textContent = `You used all ${data.rounds_used} round(s) but couldn't defeat the enemy.`;
    document.getElementById('lose-hp').textContent     = `Enemy had ${data.enemy_hp} HP remaining.`;
    document.getElementById('lose-overlay').style.display = 'flex';
}

function resetHoldBtn() {
    holdBtn.disabled      = false;
    holdBtn.classList.remove('active','loading');
    holdIcon.className    = 'ti ti-microphone';
    holdLabel.textContent = 'Hold to record';
    holdTimer.style.display = 'none';
}

// ── Waveform ────────────────────────────────────────────────────
function animateWaveform() {
    let t = 0;
    waveInterval = setInterval(() => {
        wvBars.forEach((b,i) => {
            const h = Math.abs(Math.sin((t+i)*0.35))*28+4;
            b.style.height     = h + 'px';
            b.style.background = '#7C3AED';
        });
        t++;
    }, 80);
}
function stopWaveform() {
    if (waveInterval) clearInterval(waveInterval);
    wvBars.forEach(b => { b.style.height = '6px'; b.style.background = '#D9D0F2'; });
}

function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

// ── Auto-start countdown ────────────────────────────────────────
window.addEventListener('load', () => {
    holdBtn.disabled = true;
    setTimeout(startCountdown, 600);
});
</script>
</body>
</html>