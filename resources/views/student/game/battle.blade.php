<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle! — Readify Kids</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #1E0A3C;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Top bar ─────────────────────────── */
        .top-bar {
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative; z-index: 10;
        }
        .quit-btn {
            display: flex; align-items: center; gap: 6px;
            color: #F87171; font-size: 13px; font-weight: 600;
            text-decoration: none; padding: 6px 14px;
            border-radius: 8px; border: 1px solid rgba(248,113,113,0.4);
            cursor: pointer; background: transparent; transition: all 0.2s;
        }
        .quit-btn:hover { background: rgba(248,113,113,0.15); }
        .round-badge {
            background: #7C3AED; color: #fff;
            font-size: 12px; font-weight: 700;
            padding: 5px 16px; border-radius: 20px;
        }
        .top-right { display: flex; align-items: center; gap: 12px; }
        .hp-display { font-size: 13px; font-weight: 700; color: #FCA5A5; }
        .level-chip {
            background: #7C3AED; color: #fff;
            font-size: 11px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
        }

        /* ── Arena ───────────────────────────── */
        .arena {
            background: linear-gradient(180deg, #3B0764 0%, #1E0A3C 60%, #0F0520 100%);
            min-height: calc(100vh - 52px);
            display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .stars { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
        .star  { position: absolute; background: #fff; border-radius: 50%; animation: twinkle 2s infinite alternate; }
        .ground {
            position: absolute; bottom: 0; left: 0; right: 0; height: 80px;
            background: linear-gradient(180deg, #2D1B69 0%, #1A0F3C 100%);
            border-top: 2px solid rgba(124,58,237,0.4); z-index: 1;
        }

        /* ── HP section ──────────────────────── */
        .hp-section {
            display: flex; align-items: flex-start;
            justify-content: space-between;
            padding: 14px 24px 0;
            position: relative; z-index: 5;
        }
        .hp-block { width: 200px; }
        .hp-name {
            font-size: 13px; font-weight: 700; color: #fff;
            margin-bottom: 5px; display: flex; align-items: center; gap: 6px;
        }
        .hp-name .label {
            font-size: 10px; background: rgba(255,255,255,0.15);
            padding: 1px 8px; border-radius: 20px; font-weight: 500;
        }
        .hp-bar-bg {
            background: rgba(0,0,0,0.4); border-radius: 8px; height: 14px;
            overflow: hidden; border: 1px solid rgba(255,255,255,0.1);
        }
        .hp-bar-bg.shake { animation: hpShake 0.4s ease; }
        .hp-bar-fill { height: 14px; border-radius: 8px; transition: width 0.8s ease; }
        .hp-bar-fill.student { background: linear-gradient(90deg,#4ADE80,#22C55E); }
        .hp-bar-fill.enemy   { background: linear-gradient(90deg,#EF4444,#FCA5A5); }
        .hp-text {
            font-size: 10px; color: rgba(255,255,255,0.7);
            margin-top: 3px; text-align: right;
        }

        /* ── Center info (VS + word card) ─────── */
        .center-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 16px;
            position: relative;
            z-index: 5;
        }
        .vs-badge {
            font-size: 20px; font-weight: 900; color: #FBBF24;
            text-shadow: 0 0 20px rgba(251,191,36,0.6);
            margin-bottom: 6px;
        }
        .rounds-left-pill {
            font-size: 10px; padding: 2px 10px; border-radius: 20px;
            font-weight: 600; margin-bottom: 8px;
            display: inline-block; transition: all 0.3s;
        }

        /* ── Center word card (between VS and characters) ── */
        #center-word-card {
            display: none;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(167,139,250,0.7);
            border-radius: 14px;
            padding: 12px 20px;
            text-align: center;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(6px);
            position: relative;
            z-index: 10;
            animation: wordPumpIn 0.5s ease;
        }
        .cw-label {
            font-size: 10px; color: #C4B5FD;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; margin-bottom: 6px;
        }
        #center-word-text {
            font-size: 28px; font-weight: 800; color: #fff;
            line-height: 1.4;
        }
        #center-word-text.paragraph { font-size: 15px; line-height: 1.7; }

        /* ── Countdown overlay ─────────────────── */
        #countdown-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 8888;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }
        .countdown-number {
            font-size: 140px;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 60px rgba(124,58,237,0.9), 0 0 20px rgba(255,255,255,0.5);
            animation: countPop 0.9s cubic-bezier(0.34,1.56,0.64,1) forwards;
            line-height: 1;
        }
        .countdown-label {
            font-size: 18px; font-weight: 700;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }
        .countdown-sub {
            font-size: 13px; color: rgba(255,255,255,0.4);
            margin-top: 4px;
        }

        /* ── Battlefield ─────────────────────── */
        .battlefield {
            flex: 1; display: flex; align-items: flex-end;
            justify-content: space-between;
            padding: 0 80px 90px;
            position: relative; z-index: 2;
        }
        .character-wrap {
            display: flex; flex-direction: column;
            align-items: center; gap: 8px; position: relative;
        }
        .character-label {
            font-size: 11px; font-weight: 700;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .character-sprite {
            font-size: 90px; line-height: 1;
            filter: drop-shadow(0 0 16px rgba(124,58,237,0.6));
        }
        .enemy-sprite {
            font-size: 90px; line-height: 1;
            filter: drop-shadow(0 0 16px rgba(239,68,68,0.6));
            transition: transform 0.3s, filter 0.3s;
        }

        /* ── Enemy attack bubble ──────────────── */
        #enemy-attack-bubble {
            display: none;
            position: absolute; top: -60px; left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #EF4444, #DC2626);
            border: 2px solid #FCA5A5;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 14px; font-weight: 800; color: #fff;
            white-space: nowrap;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
            z-index: 30;
            animation: bubblePop 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        #enemy-attack-bubble::after {
            content: '';
            position: absolute; bottom: -10px; left: 50%;
            transform: translateX(-50%);
            width: 0; height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 10px solid #DC2626;
        }

        /* ── Student hit ──────────────────────── */
        #student-damage-indicator {
            position: absolute; top: -30px; left: 50%;
            transform: translateX(-50%);
            font-size: 22px; font-weight: 900; color: #F87171;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.8);
            pointer-events: none; display: none; z-index: 25;
        }

        /* ── Attack effect ───────────────────── */
        #attack-effect {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            font-size: 60px; pointer-events: none;
            opacity: 0; z-index: 20;
        }

        /* ── Score reveal ────────────────────── */
        #score-reveal {
            position: absolute; top: 20%; left: 50%;
            transform: translateX(-50%);
            z-index: 30; text-align: center; display: none;
            animation: popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .score-reveal-inner {
            background: rgba(0,0,0,0.85);
            border: 2px solid rgba(124,58,237,0.6);
            border-radius: 16px; padding: 14px 28px;
            text-align: center; backdrop-filter: blur(8px);
        }
        .score-reveal-label     { font-size:11px; color:rgba(255,255,255,0.6); margin-bottom:4px; }
        .score-reveal-value     { font-size:36px; font-weight:900; margin-bottom:4px; }
        .score-reveal-damage    { font-size:14px; font-weight:700; color:#FCA5A5; }
        .score-reveal-transcript{ font-size:11px; color:rgba(255,255,255,0.5); margin-top:4px; }

        /* Damage floater */
        .damage-floater {
            position: absolute; top: -20px; left: 50%;
            transform: translateX(-50%);
            font-size: 28px; font-weight: 900; color: #FBBF24;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
            pointer-events: none;
            animation: floatUp 1.2s ease forwards;
            white-space: nowrap; z-index: 25;
        }

        /* ── Battle message (bottom center) ──── */
        .battle-msg-wrap {
            position: absolute; bottom: 92px; left: 50%;
            transform: translateX(-50%);
            z-index: 10; white-space: nowrap;
        }
        .battle-msg {
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(124,58,237,0.5);
            border-radius: 20px; padding: 8px 20px;
            font-size: 13px; color: #fff; text-align: center;
            backdrop-filter: blur(4px);
        }
        .battle-transcript {
            font-size: 11px; color: rgba(255,255,255,0.6);
            margin-top: 3px; display: none;
        }

        /* ── Done reading button ──────────────── */
        #done-reading-btn {
            display: none;
            margin-top: 8px;
            padding: 7px 20px;
            border-radius: 20px;
            border: none;
            background: linear-gradient(135deg,#7C3AED,#4F46E5);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            animation: pulse 1.5s infinite;
        }

        /* ── Recording panel (bottom) ─────────── */
        .bottom-panel {
            background: rgba(0,0,0,0.5);
            border-top: 1px solid rgba(124,58,237,0.3);
            backdrop-filter: blur(8px);
            padding: 12px 24px;
            position: relative; z-index: 10;
        }
        .bottom-inner {
            display: flex; align-items: stretch; gap: 16px;
            max-width: 900px; margin: 0 auto;
        }

        /* recording left info */
        .rec-info {
            flex: 1;
            display: flex; flex-direction: column;
            justify-content: center; gap: 4px;
        }
        .rec-info-title {
            font-size: 11px; color: #A78BFA;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .rec-info-sub {
            font-size: 12px; color: rgba(255,255,255,0.6);
        }

        .rec-panel { width: 320px; display: flex; flex-direction: column; gap: 8px; }
        .waveform-wrap {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; padding: 8px 12px;
            display: flex; align-items: center; gap: 3px; height: 38px;
        }
        .wv {
            width: 4px; border-radius: 3px;
            background: rgba(255,255,255,0.2); height: 6px; transition: height 0.08s;
        }
        .rec-timer {
            font-size: 13px; font-weight: 700; color: #fff;
            margin-left: auto; flex-shrink: 0;
        }
        .rec-actions { display: flex; gap: 8px; align-items: center; }
        .rec-btn {
            width: 46px; height: 46px; border-radius: 50%;
            background: #7C3AED; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 0 0 6px rgba(124,58,237,0.2);
            transition: all 0.2s; animation: pulse 2s infinite;
        }
        .rec-btn i { color: #fff; font-size: 20px; }
        .rec-btn.recording {
            background: #EF4444;
            box-shadow: 0 0 0 6px rgba(239,68,68,0.25);
            animation: none;
        }
        .rec-btn:disabled { opacity: 0.4; cursor: not-allowed; animation: none; }
        .attack-btn {
            flex: 1; padding: 10px; border-radius: 10px; border: none;
            background: linear-gradient(135deg,#7C3AED,#4F46E5);
            color: #fff; font-size: 13px; font-weight: 700;
            cursor: pointer; display: none;
        }
        .rerecord-btn {
            padding: 8px 12px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
            background: transparent; color: rgba(255,255,255,0.7);
            font-size: 12px; cursor: pointer; display: none;
        }
        .loading-wrap {
            display: none; align-items: center; gap: 8px; padding: 6px 0;
        }
        .loading-wrap span { font-size: 12px; color: rgba(255,255,255,0.7); }
        .audio-preview { display: none; }
        .audio-preview audio { width: 100%; height: 28px; border-radius: 6px; }

        /* history */
        .history-panel {
            width: 200px; display: flex; flex-direction: column;
            gap: 6px; overflow-y: auto; max-height: 110px;
        }
        .history-title {
            font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.5);
            text-transform: uppercase; letter-spacing: 0.07em; flex-shrink: 0;
        }
        .history-item {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px; padding: 5px 8px; font-size: 10px;
            color: rgba(255,255,255,0.8);
        }
        .history-item .hi-score { font-weight: 700; }
        .history-item .hi-dmg   { color: #FCA5A5; font-weight: 700; }

        /* dots */
        .dots-wrap { display:flex; justify-content:center; gap:5px; margin-bottom:8px; }
        .dot { width:10px; height:10px; border-radius:50%; transition:all 0.3s; }

        /* ── Quit modal ────────────────────────── */
        .quit-modal {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.85); z-index: 99999;
            align-items: center; justify-content: center;
        }
        .quit-modal-card {
            background: #1E0A3C;
            border: 2px solid rgba(248,113,113,0.5);
            border-radius: 20px; padding: 36px 32px;
            text-align: center; max-width: 380px; width: 90%;
            animation: popIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        .quit-modal-emoji  { font-size: 60px; margin-bottom: 12px; }
        .quit-modal-title  { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .quit-modal-sub    { font-size: 13px; color: rgba(255,255,255,0.6); line-height: 1.5; margin-bottom: 10px; }
        .quit-modal-note   { font-size: 11px; color: rgba(255,255,255,0.35); margin-bottom: 20px; font-style: italic; }
        .quit-modal-btns   { display: flex; gap: 10px; justify-content: center; }
        .btn-stay {
            padding: 10px 22px; border-radius: 10px;
            background: linear-gradient(135deg,#7C3AED,#4F46E5);
            color: #fff; font-size: 13px; font-weight: 700; border: none; cursor: pointer;
        }
        .btn-quit-confirm {
            padding: 10px 22px; border-radius: 10px;
            background: rgba(239,68,68,0.15); color: #F87171;
            font-size: 13px; font-weight: 600;
            border: 1px solid rgba(239,68,68,0.4);
            cursor: pointer; text-decoration: none; display: inline-block;
        }

        /* ── Win / Lose overlays ─────────────── */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.85); z-index: 9999;
            align-items: center; justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        .overlay-card {
            background: #1E0A3C;
            border: 2px solid rgba(124,58,237,0.5);
            border-radius: 24px; padding: 40px 36px;
            text-align: center; max-width: 420px; width: 90%;
            animation: popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .overlay-card.lose { border-color: rgba(239,68,68,0.5); }
        .overlay-emoji { font-size: 80px; margin-bottom: 14px; }
        .overlay-title { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .overlay-sub   { font-size: 14px; color: rgba(255,255,255,0.65); margin-bottom: 6px; }
        .overlay-pts   { font-size: 28px; font-weight: 800; color: #FBBF24; margin: 10px 0 24px; }
        .overlay-btns  { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn-back  { padding:11px 22px; border-radius:12px; background:rgba(255,255,255,0.1); color:#fff; font-size:13px; font-weight:600; text-decoration:none; border:1px solid rgba(255,255,255,0.2); }
        .btn-retry { padding:11px 22px; border-radius:12px; font-size:13px; font-weight:700; text-decoration:none; color:#fff; }
        .btn-retry.win  { background: linear-gradient(135deg,#7C3AED,#4F46E5); }
        .btn-retry.lose { background: linear-gradient(135deg,#EF4444,#DC2626); }

        /* ── Keyframes ────────────────────────── */
        @keyframes twinkle {
            from { opacity:0.3; } to { opacity:1; }
        }
        @keyframes countPop {
            0%   { opacity:0; transform:scale(0.3); }
            60%  { opacity:1; transform:scale(1.15); }
            80%  { transform:scale(0.95); }
            100% { opacity:1; transform:scale(1); }
        }
        @keyframes wordPumpIn {
            0%   { opacity:0; transform:scale(0.6) translateY(16px); }
            70%  { transform:scale(1.06) translateY(-4px); }
            100% { opacity:1; transform:scale(1) translateY(0); }
        }
        @keyframes wordPumpOut {
            0%   { opacity:1; transform:scale(1) translateY(0); }
            100% { opacity:0; transform:scale(0.7) translateY(-20px); }
        }
        @keyframes floatUp {
            0%   { opacity:1; transform:translateX(-50%) translateY(0); }
            100% { opacity:0; transform:translateX(-50%) translateY(-70px); }
        }
        @keyframes bubblePop {
            0%   { opacity:0; transform:translateX(-50%) scale(0.5); }
            70%  { transform:translateX(-50%) scale(1.1); }
            100% { opacity:1; transform:translateX(-50%) scale(1); }
        }
        @keyframes hpShake {
            0%,100% { transform:translateX(0); }
            20%     { transform:translateX(-6px); }
            40%     { transform:translateX(5px); }
            60%     { transform:translateX(-4px); }
            80%     { transform:translateX(3px); }
        }
        @keyframes studentHit {
            0%,100% { transform:translateX(0); }
            20%     { transform:translateX(10px) rotate(3deg); }
            40%     { transform:translateX(-8px) rotate(-2deg); }
            60%     { transform:translateX(6px); }
            80%     { transform:translateX(-4px); }
        }

        /* ── Student attacks ──────────────────── */
        @keyframes attackDash {
            0%   { transform:translateX(0) scaleX(1); }
            30%  { transform:translateX(200px) scaleX(1.15); }
            55%  { transform:translateX(190px) scaleX(0.9) rotate(-8deg); }
            80%  { transform:translateX(200px) scaleX(1); }
            100% { transform:translateX(0) scaleX(1); }
        }
        @keyframes attackJump {
            0%   { transform:translateX(0) translateY(0); }
            25%  { transform:translateX(80px) translateY(-80px) rotate(10deg); }
            50%  { transform:translateX(190px) translateY(0) scaleX(1.2); }
            65%  { transform:translateX(185px) translateY(10px) scaleX(0.9); }
            80%  { transform:translateX(190px) translateY(0); }
            100% { transform:translateX(0) translateY(0); }
        }
        @keyframes attackSpin {
            0%   { transform:translateX(0) rotate(0deg) scale(1); }
            30%  { transform:translateX(100px) rotate(360deg) scale(1.2); }
            55%  { transform:translateX(190px) rotate(720deg) scale(1.1); }
            75%  { transform:translateX(190px) rotate(720deg); }
            100% { transform:translateX(0) rotate(0deg) scale(1); }
        }
        @keyframes attackBlink {
            0%   { transform:translateX(0); opacity:1; }
            25%  { transform:translateX(0); opacity:0; }
            26%  { transform:translateX(190px); opacity:0; }
            45%  { transform:translateX(190px) scaleX(1.2); opacity:1; }
            70%  { transform:translateX(190px); opacity:1; }
            85%  { transform:translateX(0); opacity:0; }
            100% { transform:translateX(0); opacity:1; }
        }
        @keyframes attackCharge {
            0%   { transform:translateX(0) scale(1); filter:brightness(1); }
            20%  { transform:translateX(-20px) scale(0.85); filter:brightness(2) hue-rotate(60deg); }
            40%  { transform:translateX(-20px) scale(1.3); filter:brightness(3) hue-rotate(120deg); }
            60%  { transform:translateX(200px) scale(1.1); filter:brightness(2); }
            75%  { transform:translateX(190px) scale(0.9); filter:brightness(1.5); }
            90%  { transform:translateX(190px); filter:brightness(1); }
            100% { transform:translateX(0) scale(1); filter:brightness(1); }
        }

        /* ── Enemy attacks ────────────────────── */
        @keyframes enemyDash {
            0%   { transform:translateX(0); }
            30%  { transform:translateX(-180px) scaleX(1.1); }
            55%  { transform:translateX(-170px) scaleX(0.9); }
            80%  { transform:translateX(-180px); }
            100% { transform:translateX(0); }
        }
        @keyframes enemyLeap {
            0%   { transform:translateX(0) translateY(0); }
            25%  { transform:translateX(-80px) translateY(-70px) rotate(-10deg); }
            50%  { transform:translateX(-180px) translateY(0); }
            65%  { transform:translateX(-175px) translateY(8px); }
            80%  { transform:translateX(-180px) translateY(0); }
            100% { transform:translateX(0) translateY(0); }
        }
        @keyframes enemyZap {
            0%   { transform:translateX(0); filter:brightness(1); }
            15%  { filter:brightness(3) hue-rotate(120deg); }
            30%  { transform:translateX(-160px); filter:brightness(2); }
            50%  { transform:translateX(-180px) scaleX(1.15); filter:brightness(1.5); }
            70%  { transform:translateX(-180px); filter:brightness(1); }
            100% { transform:translateX(0); filter:brightness(1); }
        }
        @keyframes enemySwipe {
            0%   { transform:translateX(0) rotate(0); }
            20%  { transform:translateX(-60px) rotate(-15deg); }
            45%  { transform:translateX(-180px) rotate(5deg); }
            65%  { transform:translateX(-170px) rotate(-5deg) scaleX(1.1); }
            80%  { transform:translateX(-180px) rotate(0); }
            100% { transform:translateX(0) rotate(0); }
        }

        @keyframes shakeEnemy {
            0%,100% { transform:translateX(0) rotate(0); }
            15%     { transform:translateX(-14px) rotate(-4deg); }
            35%     { transform:translateX(12px) rotate(3deg); }
            55%     { transform:translateX(-8px) rotate(-2deg); }
            75%     { transform:translateX(6px) rotate(1deg); }
        }
        @keyframes enemyDefeat {
            0%   { transform:scale(1) rotate(0); opacity:1; filter:brightness(1); }
            30%  { transform:scale(1.4) rotate(10deg); filter:brightness(4) saturate(0); }
            60%  { transform:scale(1.2) rotate(20deg); filter:brightness(2); }
            100% { transform:scale(0) rotate(40deg); opacity:0; }
        }

        /* ── Effect animations ────────────────── */
        @keyframes effectSlash {
            0%   { opacity:0; transform:translate(-50%,-50%) scale(0.5) rotate(-30deg); }
            40%  { opacity:1; transform:translate(-50%,-50%) scale(1.4) rotate(10deg); }
            100% { opacity:0; transform:translate(-50%,-50%) scale(0.8) rotate(20deg); }
        }
        @keyframes effectBoom {
            0%   { opacity:0; transform:translate(-50%,-50%) scale(0.2); }
            40%  { opacity:1; transform:translate(-50%,-50%) scale(1.6); }
            100% { opacity:0; transform:translate(-50%,-50%) scale(1); }
        }
        @keyframes effectSpin {
            0%   { opacity:0; transform:translate(-50%,-50%) scale(0.5) rotate(0deg); }
            50%  { opacity:1; transform:translate(-50%,-50%) scale(1.3) rotate(180deg); }
            100% { opacity:0; transform:translate(-50%,-50%) scale(0.8) rotate(360deg); }
        }

        @keyframes auraExcellent {
            0%,100% { filter:drop-shadow(0 0 16px #4ADE80) brightness(1.3); }
            50%     { filter:drop-shadow(0 0 32px #4ADE80) brightness(1.6); }
        }
        @keyframes auraGood {
            0%,100% { filter:drop-shadow(0 0 14px #60A5FA) brightness(1.2); }
            50%     { filter:drop-shadow(0 0 28px #60A5FA) brightness(1.5); }
        }
        @keyframes auraWeak {
            0%,100% { filter:drop-shadow(0 0 10px #F87171) brightness(1.1); }
            50%     { filter:drop-shadow(0 0 20px #F87171) brightness(1.3); }
        }
        @keyframes popIn {
            0%   { transform:scale(0.5); opacity:0; }
            70%  { transform:scale(1.05); }
            100% { transform:scale(1); opacity:1; }
        }
        @keyframes fadeIn {
            from { opacity:0; } to { opacity:1; }
        }
        @keyframes pulse {
            0%,100% { box-shadow:0 0 0 6px rgba(124,58,237,0.2); }
            50%     { box-shadow:0 0 0 12px rgba(124,58,237,0.08); }
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
        <span class="hp-display">
            💥 <span id="top-damage">{{ number_format($session->total_damage) }}</span>
        </span>
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
    <div class="stars" id="stars"></div>
    <div class="ground"></div>

    {{-- HP section --}}
    <div class="hp-section">
        {{-- Student HP --}}
        <div class="hp-block">
            <div class="hp-name">
                <span>{{ auth()->user()->student->firstname }}</span>
                <span class="label">YOU</span>
            </div>
            <div class="hp-bar-bg" id="student-hp-bg">
                <div class="hp-bar-fill student" id="student-hp-bar" style="width:100%;"></div>
            </div>
            <div class="hp-text">⭐ {{ number_format(auth()->user()->student->total_points) }} pts</div>
        </div>

        {{-- CENTER: VS + rounds pill + WORD CARD ─────────── --}}
        <div class="center-info">
            <div class="vs-badge">VS</div>
            <div id="rounds-left-pill" class="rounds-left-pill"
                 style="background:{{ $roundsLeft <= 2 ? 'rgba(239,68,68,0.3)' : 'rgba(124,58,237,0.3)' }};
                        color:{{ $roundsLeft <= 2 ? '#FCA5A5' : '#C4B5FD' }};">
                {{ $roundsLeft }} round(s) left
            </div>

            {{-- ★ Word card lives here now ★ --}}
            <div id="center-word-card">
                <div class="cw-label">📖 Read this aloud:</div>
                <div id="center-word-text"
                     class="{{ $session->activity->level == 3 ? 'paragraph' : '' }}">
                    {{ $currentWord }}
                </div>
                <button id="done-reading-btn" onclick="onDoneReading()">
                    ✅ Done Reading — Attack!
                </button>
            </div>
        </div>

        {{-- Enemy HP --}}
        <div class="hp-block" style="text-align:right;">
            <div class="hp-name" style="justify-content:flex-end;">
                <span class="label">ENEMY</span>
                <span>{{ $session->enemy->name }}</span>
            </div>
            <div class="hp-bar-bg">
                <div id="hp-bar" class="hp-bar-fill enemy"
                     style="width:{{ $hpPercent }}%;margin-left:auto;"></div>
            </div>
            <div class="hp-text">
                ❤️ <span id="hp-current">{{ number_format($session->enemy_current_hp) }}</span>
                / {{ number_format($session->enemy_max_hp) }}
            </div>
        </div>
    </div>

    {{-- Battlefield --}}
    <div class="battlefield">

        {{-- Student character --}}
        <div class="character-wrap" id="student-wrap">
            <div class="character-label">{{ auth()->user()->student->firstname }}</div>
            <div class="character-sprite" id="student-sprite">🧒</div>
            <div id="student-damage-indicator">💢</div>
        </div>

        {{-- Score reveal --}}
        <div id="score-reveal">
            <div class="score-reveal-inner">
                <div class="score-reveal-label">🤖 AI Score</div>
                <div class="score-reveal-value"       id="score-reveal-value"></div>
                <div class="score-reveal-damage"      id="score-reveal-damage"></div>
                <div class="score-reveal-transcript"  id="score-reveal-transcript"></div>
            </div>
        </div>

        {{-- Attack effect --}}
        <div id="attack-effect"></div>

        {{-- Battle message --}}
        <div class="battle-msg-wrap">
            <div class="battle-msg" id="battle-msg">
                ⏳ Get ready… countdown starting!
                <div class="battle-transcript" id="battle-transcript"></div>
            </div>
        </div>

        {{-- Enemy --}}
        <div class="character-wrap" id="enemy-wrap">
            <div class="character-label">{{ $session->enemy->name }}</div>
            <div id="enemy-attack-bubble"></div>
            <div class="enemy-sprite" id="enemy-sprite">{{ $session->enemy->sprite }}</div>
        </div>
    </div>

    {{-- ── BOTTOM PANEL ──────────────────────────────────── --}}
    <div class="bottom-panel">

        <div class="dots-wrap">
            @foreach($allWords as $i => $w)
            <div id="dot-{{ $i }}" class="dot"
                 style="background:{{ $i < $roundIndex ? '#22C55E' : ($i == $roundIndex ? '#A78BFA' : 'rgba(255,255,255,0.2)') }};
                        {{ $i == $roundIndex ? 'box-shadow:0 0 0 3px rgba(167,139,250,0.4);' : '' }}">
            </div>
            @endforeach
        </div>

        <div class="bottom-inner">

            {{-- Recording info --}}
            <div class="rec-info">
                <div class="rec-info-title">🎙️ Voice Recording</div>
                <div class="rec-info-sub" id="rec-status">
                    Waiting for countdown…
                </div>
            </div>

            {{-- Recording panel --}}
            <div class="rec-panel">
                <div class="waveform-wrap">
                    @for($i = 0; $i < 18; $i++)<div class="wv"></div>@endfor
                    <span class="rec-timer" id="rec-timer">0:00</span>
                </div>
                <div class="audio-preview" id="audio-preview">
                    <audio id="playback-audio" controls></audio>
                </div>
                <div class="rec-actions">
                    <button class="rec-btn" id="rec-btn" type="button" disabled>
                        <i class="ti ti-microphone" id="rec-icon"></i>
                    </button>
                    <button class="rerecord-btn" id="rerecord-btn">
                        <i class="ti ti-refresh"></i> Redo
                    </button>
                    <button class="attack-btn" id="attack-btn">⚔️ Submit!</button>
                </div>
                <div class="loading-wrap" id="loading-wrap">
                    <div class="spinner-border text-light" style="width:16px;height:16px;"></div>
                    <span id="loading-text">🤖 AI analyzing your reading...</span>
                </div>
            </div>

            {{-- History --}}
            <div class="history-panel">
                <div class="history-title">📋 Round History</div>
                <div id="round-history">
                    @forelse($session->rounds->sortByDesc('created_at') as $round)
                    <div class="history-item">
                        <span class="hi-score"
                              style="color:{{ $round->ml_score >= 90 ? '#4ADE80' : ($round->ml_score >= 70 ? '#60A5FA' : ($round->ml_score >= 50 ? '#FBBF24' : '#F87171')) }}">
                            {{ $round->ml_score ?? '—' }}%
                        </span>
                        <span class="hi-dmg"> -{{ $round->damage_dealt }}HP</span>
                        <div style="color:rgba(255,255,255,0.3);margin-top:2px;font-size:9px;">
                            "{{ Str::limit($round->word_or_passage, 14) }}"
                        </div>
                    </div>
                    @empty
                    <div style="font-size:10px;color:rgba(255,255,255,0.3);text-align:center;padding:8px;">
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
            <strong style="color:#FCA5A5;">{{ $session->enemy->name }}</strong>?
        </div>
        <div class="quit-modal-note">
            Your progress will be saved. You can continue this battle later from the Arena.
        </div>
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
        <div class="overlay-sub" style="font-size:12px;color:rgba(255,255,255,0.4);" id="win-transcript"></div>
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
        <div class="overlay-sub" style="font-size:12px;color:rgba(255,255,255,0.4);" id="lose-rounds"></div>
        <div class="overlay-sub" style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:20px;" id="lose-hp"></div>
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
// ── Stars ──────────────────────────────────────────────────────
(function() {
    const c = document.getElementById('stars');
    for (let i = 0; i < 60; i++) {
        const s = document.createElement('div');
        s.className = 'star';
        const sz = Math.random() * 2.5 + 0.5;
        s.style.cssText = `width:${sz}px;height:${sz}px;
            top:${Math.random()*70}%;left:${Math.random()*100}%;
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
    'Mumbo Jumbo!','Abra Kadabra!','Fizzle Wizzle!',
    'Blunder Blitz!','Snazzle Frazzle!','Kerplunk!',
    'Boo!','Ugh!','Grrr!','Roooaar!','Bwahahaha!','Zap!',
    '📖 Garble!','🌀 Confuse!','🔥 Burn!',
    '💨 Whoosh!','⚡ Shock!','🌊 Splash!',
    'Flibbertigibbet!','Supercalifragilistic!','Bibbidi Bobbidi!',
    'Zippity Zap!','Hocus Pocus!','Wacka Wacka!',
];
const ENEMY_ATTACK_STYLES = [
    { anim:'enemyDash',  dur:900,  effect:'💨', color:'#F87171' },
    { anim:'enemyLeap',  dur:1000, effect:'💥', color:'#FBBF24' },
    { anim:'enemyZap',   dur:900,  effect:'⚡', color:'#60A5FA' },
    { anim:'enemySwipe', dur:950,  effect:'🌀', color:'#C084FC' },
];
const ATTACK_STYLES = [
    { name:'Dash Strike',  anim:'attackDash',   dur:900,  effect:'⚡', effectAnim:'effectSlash', effectDur:600, color:'#FBBF24' },
    { name:'Jump Slam',    anim:'attackJump',   dur:1000, effect:'💥', effectAnim:'effectBoom',  effectDur:700, color:'#F87171' },
    { name:'Spin Charge',  anim:'attackSpin',   dur:1100, effect:'🌀', effectAnim:'effectSpin',  effectDur:800, color:'#A78BFA' },
    { name:'Blink Strike', anim:'attackBlink',  dur:900,  effect:'✨', effectAnim:'effectSlash', effectDur:500, color:'#60A5FA' },
    { name:'Power Burst',  anim:'attackCharge', dur:1200, effect:'🔥', effectAnim:'effectBoom',  effectDur:900, color:'#4ADE80' },
];
function pickAttackStyle(score) {
    if (score >= 90) return ATTACK_STYLES[4];
    if (score >= 75) return ATTACK_STYLES[2];
    if (score >= 60) return ATTACK_STYLES[1];
    if (score >= 40) return ATTACK_STYLES[3];
    return ATTACK_STYLES[0];
}
function pickRandom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

// ── State ──────────────────────────────────────────────────────
let mediaRecorder, audioChunks=[], isRecording=false;
let timerInterval, seconds=0, audioBlob=null, waveInterval=null;
let battleLocked = false;
// Track flow phase:
// 'countdown' → 'reading' → 'recording' → 'submitted' → 'enemy_attack' → 'next'
let gamePhase = 'countdown';

// ── DOM refs ───────────────────────────────────────────────────
const recBtn        = document.getElementById('rec-btn');
const recIcon       = document.getElementById('rec-icon');
const recStatus     = document.getElementById('rec-status');
const recTimer      = document.getElementById('rec-timer');
const rerecordBtn   = document.getElementById('rerecord-btn');
const attackBtn     = document.getElementById('attack-btn');
const loadingWrap   = document.getElementById('loading-wrap');
const loadingText   = document.getElementById('loading-text');
const audioPreview  = document.getElementById('audio-preview');
const playbackAud   = document.getElementById('playback-audio');
const wvBars        = document.querySelectorAll('.wv');
const sessionId     = document.getElementById('session-id').value;
const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
const enemyMaxHp    = parseInt(document.getElementById('enemy-max-hp').value);
const enemyName     = '{{ $session->enemy->name }}';
const totalWords    = parseInt(document.getElementById('total-words').value);
const wordCard      = document.getElementById('center-word-card');
const wordText      = document.getElementById('center-word-text');
const doneBtn       = document.getElementById('done-reading-btn');
const currentWord   = document.getElementById('current-word-value').value;
const isParagraph   = document.getElementById('is-paragraph').value === '1';

// ── COUNTDOWN → show word ──────────────────────────────────────
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
            numEl.textContent = count;
            numEl.style.color = count === 3 ? '#60A5FA' : count === 2 ? '#FBBF24' : '#F87171';
            lblEl.textContent = count === 3 ? 'GET READY!'
                              : count === 2 ? 'ALMOST…'
                              : 'GO!';
            count--;
            setTimeout(tick, 950);
        } else {
            // Show GO!
            numEl.textContent = '⚔️';
            numEl.style.color = '#4ADE80';
            lblEl.textContent = 'READ IT!';
            subEl.textContent = 'Read the passage aloud then press Done!';

            setTimeout(() => {
                overlay.style.display = 'none';
                // Reveal the word card
                showWordCard();
            }, 900);
        }
    }
    tick();
}

// ── Show word card in center ───────────────────────────────────
function showWordCard() {
    gamePhase = 'reading';
    wordCard.style.animation = 'none';
    void wordCard.offsetWidth;
    wordCard.style.animation = 'wordPumpIn 0.5s ease forwards';
    wordCard.style.display   = 'block';
    doneBtn.style.display    = 'inline-block';

    setBattleMsg('📖 Read the passage above, then press Done Reading!');
    recStatus.textContent = '📖 Read the word/passage above first…';

    // Enable mic after word appears
    recBtn.disabled = false;
    recStatus.textContent = '🎙️ Record yourself reading the word above, then press Done!';
}

// ── Hide word card (animate out) ──────────────────────────────
function hideWordCard(callback) {
    wordCard.style.animation = 'wordPumpOut 0.4s ease forwards';
    setTimeout(() => {
        wordCard.style.display = 'none';
        wordCard.style.animation = '';
        if (callback) callback();
    }, 400);
}

// ── Student presses "Done Reading" ────────────────────────────
function onDoneReading() {
    if (gamePhase !== 'reading') return;
    gamePhase = 'recording';
    doneBtn.style.display = 'none';

    // Slide the word card out
    hideWordCard(() => {
        setBattleMsg('🎙️ Now record yourself and press Submit!');
        recStatus.textContent = '🎙️ Press the mic button to record your reading!';
    });
}

// ── Record button ──────────────────────────────────────────────
recBtn.addEventListener('click', async () => {
    if (battleLocked || recBtn.disabled) return;
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks   = [];
            mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = () => {
                audioBlob                  = new Blob(audioChunks, { type: 'audio/webm' });
                playbackAud.src            = URL.createObjectURL(audioBlob);
                audioPreview.style.display = 'block';
                rerecordBtn.style.display  = 'inline-block';
                attackBtn.style.display    = 'block';
                stopWaveform();
                recStatus.textContent = '✅ Recording done! Press Submit to attack!';
            };
            mediaRecorder.start(100);
            isRecording = true;
            recBtn.classList.add('recording');
            recIcon.className          = 'ti ti-player-stop';
            recStatus.textContent      = '🔴 Recording… Tap again to stop.';
            audioPreview.style.display = 'none';
            rerecordBtn.style.display  = 'none';
            attackBtn.style.display    = 'none';
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                recTimer.textContent =
                    Math.floor(seconds/60).toString().padStart(2,'0') + ':' +
                    (seconds%60).toString().padStart(2,'0');
            }, 1000);
            animateWaveform();
        } catch(e) {
            alert('Microphone access denied! Please allow microphone access.');
        }
    } else {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop());
        isRecording = false;
        clearInterval(timerInterval);
        recBtn.classList.remove('recording');
        recIcon.className = 'ti ti-microphone';
    }
});

rerecordBtn.addEventListener('click', resetRec);

// ── Submit / Attack button ─────────────────────────────────────
attackBtn.addEventListener('click', async () => {
    if (!audioBlob || battleLocked) return;

    battleLocked = true;
    gamePhase    = 'submitted';
    attackBtn.style.display    = 'none';
    rerecordBtn.style.display  = 'none';
    audioPreview.style.display = 'none';
    loadingWrap.style.display  = 'flex';
    loadingText.textContent    = '🤖 AI is analyzing your reading…';
    recBtn.disabled            = true;
    recStatus.textContent      = '';

    const formData = new FormData();
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

        // ── 1. Show AI score reveal ────────────────────────
        await showScoreReveal(data);

        // ── 2. Student aura ────────────────────────────────
        applyStudentAura(data.ml_score);

        // ── 3. Student attacks ─────────────────────────────
        const style = data.ml_score !== null
            ? pickAttackStyle(data.ml_score)
            : pickRandom(ATTACK_STYLES);
        await playStudentAttack(style);

        // ── 4. Enemy gets hit ──────────────────────────────
        playEnemyHit(data.damage, data.ml_score, style);
        updateEnemyHpBar(data.enemy_hp ?? 0, data.hp_percent ?? 0);
        updateStats(data);
        addHistory(data, currentWord);
        if (data.rounds_left !== undefined) updateRoundsLeft(data.rounds_left);

        // ── 5. Win / Lose / Ongoing ────────────────────────
        if (data.status === 'won') {
            setBattleMsg('🎉 Final blow landed!');
            await delay(600);
            await playEnemyDefeat();
            setTimeout(() => showWin(data), 400);

        } else if (data.status === 'lost') {
            showBattleMsg(data);
            await delay(600);
            await playEnemyCounterAttack(true);
            setTimeout(() => showLose(data), 800);

        } else if (data.status === 'ongoing') {
            showBattleMsg(data);
            await delay(600);
            // ── 6. Enemy counterattacks ────────────────────
            await playEnemyCounterAttack(false);
            await delay(400);
            // ── 7. New word pumps up after enemy attacks ───
            moveToNext();
        } else {
            setBattleMsg('⏳ Recording submitted! Waiting for AI scoring…');
            battleLocked = false;
            setTimeout(resetRec, 1500);
        }

    } catch(err) {
        loadingWrap.style.display = 'none';
        console.error(err);
        setBattleMsg('❌ Something went wrong. Try again!');
        battleLocked = false;
        recBtn.disabled = false;
        setTimeout(resetRec, 2000);
    }
});

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
        else if (s >= 90)    { color = '#4ADE80'; label = '🔥 Excellent!'; }
        else if (s >= 75)    { color = '#60A5FA'; label = '⚔️ Great!'; }
        else if (s >= 60)    { color = '#FBBF24'; label = '👍 Good!'; }
        else if (s >= 40)    { color = '#F97316'; label = '💪 OK!'; }
        else                 { color = '#F87171'; label = '😅 Try harder!'; }

        valEl.style.color   = color;
        valEl.textContent   = s !== null ? `${s}% — ${label}` : '— Pending';
        dmgEl.textContent   = `💥 ${data.damage} damage dealt!`;
        transEl.textContent = data.transcript ? `🎙️ "${data.transcript}"` : '';

        panel.style.display = 'block';
        setTimeout(() => { panel.style.display = 'none'; resolve(); }, 1400);
    });
}

// ── Student attack animation ───────────────────────────────────
function playStudentAttack(style) {
    return new Promise(resolve => {
        const wrap = document.getElementById('student-wrap');
        wrap.style.animation = 'none';
        void wrap.offsetWidth;
        wrap.style.animation = `${style.anim} ${style.dur}ms cubic-bezier(0.34,1.56,0.64,1) forwards`;
        recStatus.textContent = `⚔️ ${style.name}!`;
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
        sprite.style.filter    = 'drop-shadow(0 0 16px rgba(239,68,68,0.6))';
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

// ── Enemy counterattack ────────────────────────────────────────
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

        // Step 1: Show enemy word bubble
        bubble.textContent   = isFinal ? '🔥 FINAL BLOW!' : word;
        bubble.style.display = 'block';

        setTimeout(() => {
            // Step 2: Enemy charges
            enemySprite.style.animation = 'none';
            void enemySprite.offsetWidth;
            enemySprite.style.animation = `${style.anim} ${style.dur}ms cubic-bezier(0.34,1.56,0.64,1) forwards`;
            enemySprite.style.filter    = `drop-shadow(0 0 20px ${style.color})`;
            setBattleMsg(`${enemyName} counters: "${word}"`);
        }, 300);

        setTimeout(() => {
            // Step 3: Student takes hit
            studentSprite.style.animation = 'none';
            void studentSprite.offsetWidth;
            studentSprite.style.animation = 'studentHit 0.5s ease';
            studentSprite.style.filter    = 'brightness(3) saturate(0)';

            hpBg.classList.remove('shake');
            void hpBg.offsetWidth;
            hpBg.classList.add('shake');

            dmgInd.textContent   = dmgText;
            dmgInd.style.display = 'block';
            dmgInd.style.animation = 'none';
            void dmgInd.offsetWidth;
            dmgInd.style.animation = 'floatUp 1s ease forwards';

            setTimeout(() => {
                studentSprite.style.filter    = 'drop-shadow(0 0 16px rgba(124,58,237,0.6))';
                studentSprite.style.animation = '';
                hpBg.classList.remove('shake');
                dmgInd.style.display = 'none';
            }, 600);
        }, style.dur * 0.55);

        setTimeout(() => {
            // Step 4: Enemy retreats + bubble gone
            enemySprite.style.animation = '';
            enemySprite.style.filter    = 'drop-shadow(0 0 16px rgba(239,68,68,0.6))';
            bubble.style.display        = 'none';
            resolve();
        }, style.dur + 200);
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
        sprite.style.filter    = 'drop-shadow(0 0 16px rgba(124,58,237,0.6))';
    }, 2000);
}

// ── HP bar updates ──────────────────────────────────────────────
function updateEnemyHpBar(newHp, hpPercent) {
    const bar = document.getElementById('hp-bar');
    bar.style.width = Math.max(0, hpPercent) + '%';
    document.getElementById('hp-current').textContent = newHp.toLocaleString();
    if      (hpPercent <= 25) bar.style.background = '#EF4444';
    else if (hpPercent <= 50) bar.style.background = 'linear-gradient(90deg,#F59E0B,#FCD34D)';
    else                      bar.style.background = 'linear-gradient(90deg,#EF4444,#FCA5A5)';
}

// ── Stats ───────────────────────────────────────────────────────
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
        pill.style.background = 'rgba(239,68,68,0.3)';
        pill.style.color      = '#FCA5A5';
    } else if (left <= 4) {
        pill.style.background = 'rgba(245,158,11,0.3)';
        pill.style.color      = '#FCD34D';
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
    const c  = sc >= 90 ? '#4ADE80' : sc >= 70 ? '#60A5FA' : sc >= 50 ? '#FBBF24' : '#F87171';
    const div = document.createElement('div');
    div.className = 'history-item';
    div.innerHTML = `
        <span class="hi-score" style="color:${c}">${sc !== null ? sc + '%' : '—'}</span>
        <span class="hi-dmg"> -${data.damage}HP</span>
        ${data.transcript ? `<div style="color:rgba(255,255,255,0.4);margin-top:2px;font-size:9px;">🎙️ "${data.transcript.substring(0,16)}"</div>` : ''}
        <div style="color:rgba(255,255,255,0.3);font-size:9px;">"${word.substring(0,14)}"</div>
    `;
    hist.insertBefore(div, hist.firstChild);
}

// ── Move to next round — NEW WORD PUMPS UP after enemy attack ──
function moveToNext() {
    // Mark current dot
    const idx = parseInt(document.getElementById('current-round-index').value);
    const dot  = document.getElementById(`dot-${idx}`);
    if (dot) { dot.style.background = '#22C55E'; dot.style.boxShadow = 'none'; }

    // Reload page — server picks next word and starts countdown again
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

function resetRec() {
    audioBlob                  = null;
    battleLocked               = false;
    recBtn.disabled            = false;
    audioPreview.style.display = 'none';
    rerecordBtn.style.display  = 'none';
    attackBtn.style.display    = 'none';
    recTimer.textContent       = '0:00';
    recBtn.classList.remove('recording');
    recIcon.className = 'ti ti-microphone';
    stopWaveform();
}

function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

// ── Waveform ────────────────────────────────────────────────────
function animateWaveform() {
    let t = 0;
    waveInterval = setInterval(() => {
        wvBars.forEach((b,i) => {
            const h = Math.abs(Math.sin((t+i)*0.35))*28+4;
            b.style.height     = h + 'px';
            b.style.background = '#A78BFA';
        });
        t++;
    }, 80);
}
function stopWaveform() {
    if (waveInterval) clearInterval(waveInterval);
    wvBars.forEach(b => {
        b.style.height     = '6px';
        b.style.background = 'rgba(255,255,255,0.2)';
    });
}

// ── Auto-start countdown on page load ──────────────────────────
window.addEventListener('load', () => {
    // Disable mic until countdown finishes
    recBtn.disabled = true;
    setTimeout(startCountdown, 600);
});
</script>
</body>
</html>