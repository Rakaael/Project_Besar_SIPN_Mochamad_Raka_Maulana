<?php
// Petakan parameter URL ke pesan status yang ditampilkan di halaman login.
$errorMessages = [
    'invalid' => 'Username atau password salah.',
    'required' => 'Silakan login terlebih dahulu.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
$registered = ($_GET['registered'] ?? '') === '1';
$loggedOut = ($_GET['logout'] ?? '') === '1';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --login-ink: #17345c; --login-blue: #0b4aa2; --login-deep: #073779; --login-border: #d5e5fb; }
        body { position: relative; isolation: isolate; background: radial-gradient(ellipse at 16% 18%, rgba(42,143,155,0.16), transparent 32rem), linear-gradient(135deg, #e2edfc 0%, #f8fbff 58%, #e7f2f5 100%); color: var(--login-ink); font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif; -webkit-font-smoothing: antialiased; }
        body::before { position: fixed; z-index: -1; inset: 0; background-image: linear-gradient(rgba(11,74,162,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(11,74,162,0.035) 1px, transparent 1px); background-size: 36px 36px; mask-image: linear-gradient(to bottom, #000 0%, transparent 90%); content: ""; pointer-events: none; }
        .login-shell { min-height: 100vh; }
        .login-layout { display: grid; width: min(100%, 1050px); grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.8fr); align-items: center; gap: clamp(2rem, 7vw, 6rem); }
        .login-intro { position: relative; isolation: isolate; padding: clamp(1rem, 3vw, 2.5rem) 0; }
        .login-intro::before { position: absolute; z-index: -1; top: 50%; left: -3rem; width: min(34rem, 90vw); aspect-ratio: 1; border: 1px solid rgba(11,74,162,0.07); border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.54), rgba(42,143,155,0.045) 58%, transparent 70%); content: ""; transform: translateY(-50%); }
        .login-intro-eyebrow { display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; padding: 0.45rem 0.75rem; border: 1px solid rgba(11,74,162,0.12); border-radius: 999px; background: rgba(255,255,255,0.72); color: var(--login-blue); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
        .login-intro-eyebrow::before { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #19a0ad; box-shadow: 0 0 10px rgba(25,160,173,0.65); content: ""; }
        .login-intro h1 { max-width: 14ch; margin-bottom: 1rem; color: var(--login-deep); font-size: clamp(2.6rem, 5.5vw, 4.5rem); font-weight: 780; letter-spacing: -0.065em; line-height: 1.02; }
        .login-intro h1 span { color: var(--login-blue); }
        .login-intro-copy { max-width: 38rem; margin-bottom: 2rem; color: #62748a; font-size: 1.08rem; line-height: 1.75; }
        .login-features { display: grid; gap: 0.9rem; max-width: 28rem; margin: 0; padding: 0; list-style: none; }
        .login-features li { display: flex; align-items: center; gap: 0.8rem; color: #24466f; font-size: 0.94rem; font-weight: 600; }
        .login-feature-icon { display: grid; width: 2.4rem; height: 2.4rem; flex: 0 0 auto; place-items: center; border: 1px solid rgba(11,74,162,0.1); border-radius: 9px; background: rgba(255,255,255,0.78); color: var(--login-blue); box-shadow: 0 4px 12px rgba(16,64,130,0.07); transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease; }
        .login-feature-icon svg { width: 1.15rem; height: 1.15rem; }
        .login-card { max-width: 460px; justify-self: end; overflow: hidden; border: 1px solid var(--login-border) !important; border-radius: 16px; background: rgba(255,255,255,0.94); box-shadow: 0 24px 70px rgba(16,64,130,0.17), 0 0 28px rgba(25,160,173,0.09) !important; transition: box-shadow 260ms ease, border-color 260ms ease; backdrop-filter: blur(12px); }
        .login-card::before { display: block; height: 5px; background: linear-gradient(90deg, var(--login-deep), #0b67ba 56%, #19a0ad); box-shadow: 0 0 14px rgba(25,160,173,0.35); content: ""; }
        .brand-lockup { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 2rem; }
        .brand-symbol { display: grid; width: 3.25rem; height: 3.25rem; flex: 0 0 auto; place-items: center; border: 1px solid rgba(25,160,173,0.28); border-radius: 11px; background: linear-gradient(145deg, var(--login-deep), #0b58b5 68%, #1685a3); color: #fff; box-shadow: 0 6px 18px rgba(7,55,121,0.22), 0 0 16px rgba(25,160,173,0.18); }
        .brand-symbol svg { width: 1.8rem; height: 1.8rem; }
        .brand-name { display: block; color: var(--login-deep); font-size: 1.05rem; font-weight: 800; line-height: 1.15; }
        .brand-description { display: block; margin-top: 0.25rem; color: #62748a; font-size: 0.76rem; }
        .login-heading { margin-bottom: 1.5rem; }
        .login-heading h1 { margin-bottom: 0.4rem; color: var(--login-deep); font-size: 1.8rem; font-weight: 750; letter-spacing: -0.035em; }
        .login-heading p { margin: 0; color: #62748a; }
        .form-label { color: #24466f; font-size: 0.92rem; font-weight: 650; }
        .login-card .form-label { margin-bottom: 0.5rem; }
        .login-input { border-radius: 9px; transition: transform 220ms ease, box-shadow 220ms ease; }
        .login-input .input-group-text { border-color: #c7d7eb; background: #f2f7ff; color: #52749e; transition: border-color 220ms ease, color 220ms ease, background-color 220ms ease; }
        .login-input .form-control { min-height: 50px; border-color: #c7d7eb; background: #fbfdff; color: var(--login-ink); transition: border-color 220ms ease, box-shadow 220ms ease, background-color 220ms ease; }
        .login-input .input-group-text:first-child { border-radius: 9px 0 0 9px; }
        .login-input .form-control:last-child { border-radius: 0 9px 9px 0; }
        .login-input:focus-within { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(16,64,130,0.08); }
        .login-input:focus-within .input-group-text, .login-input:focus-within .form-control { border-color: #4c8ed8; background-color: #fff; }
        .login-input:focus-within .input-group-text { color: var(--login-blue); }
        .login-input .form-control::placeholder { color: #8799ad; opacity: 1; }
        .login-input .form-control:focus { z-index: 3; border-color: #4c8ed8; box-shadow: 0 0 0 0.2rem rgba(36,112,196,0.15), 0 0 12px rgba(25,160,173,0.13); }
        .btn { min-height: 48px; border-radius: 9px; font-weight: 650; }
        .btn-primary { --bs-btn-bg: #0b4aa2; --bs-btn-border-color: #0b4aa2; --bs-btn-hover-bg: #073779; --bs-btn-hover-border-color: #073779; --bs-btn-active-bg: #062b60; --bs-btn-active-border-color: #062b60; }
        .login-submit { box-shadow: 0 7px 18px rgba(11,74,162,0.2); transition: transform 220ms ease, box-shadow 220ms ease, background-color 220ms ease, border-color 220ms ease; }
        .login-submit:hover { transform: translateY(-2px); box-shadow: 0 11px 24px rgba(11,74,162,0.24), 0 0 16px rgba(25,160,173,0.14); }
        .login-submit:active { transform: translateY(0) scale(0.985); box-shadow: 0 3px 9px rgba(11,74,162,0.18); }
        .login-submit:focus-visible, .register-link:focus-visible { outline: 3px solid rgba(25,160,173,0.55); outline-offset: 3px; }
        .login-submit svg, .register-link svg { width: 1rem; height: 1rem; }
        .login-submit svg { transition: transform 220ms ease; }
        .login-submit:hover svg { transform: translateX(3px); }
        .register-prompt { color: #62748a; font-size: 0.92rem; }
        .register-link { display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 650; text-decoration: none; transition: color 200ms ease; }
        .register-link:hover { text-decoration: underline; }
        .alert { border-radius: 9px; }
        .alert-info { color: #15506b; background: #e9f5fb; border-color: #c8e3f0; }
        .alert-success { color: #176448; background: #eaf6f0; border-color: #c5e5d5; }
        .alert-danger { color: #963448; background: #fff0f2; border-color: #f0c7ce; }
        .login-card .alert { border-left-width: 4px; box-shadow: 0 4px 14px rgba(16,64,130,0.045); }
        .login-card .alert-info { border-left-color: #3199b0; }
        .login-card .alert-success { border-left-color: #36a77b; }
        .login-card .alert-danger { border-left-color: #d05b6c; }
        a { color: #0b58b5; }
        a:hover { color: #073779; }
        .register-link { border-radius: 4px; }
        .register-link svg { transition: transform 220ms ease; }
        .register-link:hover svg { transform: translate(2px, -2px); }
        @media (hover: hover) {
            .login-card:hover { border-color: #c4daf5 !important; box-shadow: 0 28px 76px rgba(16,64,130,0.19), 0 0 32px rgba(25,160,173,0.11) !important; }
            .login-feature-icon:hover { transform: translateY(-2px); border-color: rgba(11,74,162,0.2); box-shadow: 0 7px 16px rgba(16,64,130,0.11); }
        }
        :root { --login-ink: #253a34; --login-blue: #08765a; --login-deep: #073d31; --login-border: #d9e2dc; --login-metal: #bdc7c2; }
        body { background: radial-gradient(ellipse at 12% 18%, rgba(16,145,105,0.12), transparent 31rem), radial-gradient(ellipse at 88% 82%, rgba(189,199,194,0.24), transparent 30rem), linear-gradient(135deg, #e9f0eb 0%, #f8f7f2 58%, #eef1eb 100%); }
        body::before { background-image: linear-gradient(rgba(8,118,90,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(8,118,90,0.035) 1px, transparent 1px); }
        .login-intro::before { border-color: rgba(8,118,90,0.09); background: radial-gradient(circle, rgba(255,254,250,0.66), rgba(189,199,194,0.10) 58%, transparent 70%); }
        .login-intro-eyebrow { border-color: rgba(8,118,90,0.16); background: rgba(255,254,250,0.78); color: #08765a; }
        .login-intro-eyebrow::before { background: #16936d; box-shadow: 0 0 10px rgba(22,147,109,0.46); }
        .login-intro h1 span { background: linear-gradient(110deg, #075640, #15936d 58%, #8b9c92); background-clip: text; -webkit-background-clip: text; color: transparent; }
        .login-intro-copy, .brand-description, .login-heading p, .register-prompt { color: #687a73; }
        .login-features li { color: #38564a; }
        .login-feature-icon { border-color: rgba(8,118,90,0.12); background: rgba(255,254,250,0.82); color: #08765a; box-shadow: 0 4px 12px rgba(24,62,48,0.07); }
        .login-card { border-color: #d9e2dc !important; background: rgba(255,254,250,0.96); box-shadow: 0 24px 70px rgba(24,62,48,0.14), 0 0 24px rgba(189,199,194,0.2) !important; }
        .login-card::before { background: linear-gradient(90deg, #bdc7c2, #fff 32%, #15936d 72%, #bdc7c2); box-shadow: 0 0 12px rgba(189,199,194,0.4); }
        .brand-symbol { border-color: rgba(230,237,232,0.55); background: linear-gradient(145deg, #073d31, #08765a 70%, #25896b); box-shadow: 0 6px 18px rgba(7,61,49,0.2), 0 0 13px rgba(189,199,194,0.22); }
        .brand-name, .login-heading h1 { color: #073d31; }
        .form-label { color: #38564a; }
        .login-input .input-group-text { border-color: #cbd8cf; background: #edf3ee; color: #587367; }
        .login-input .form-control { border-color: #cbd8cf; background: #fffefa; color: #253a34; }
        .login-input:focus-within { box-shadow: 0 5px 14px rgba(24,62,48,0.08), 0 0 10px rgba(189,199,194,0.24); }
        .login-input:focus-within .input-group-text, .login-input:focus-within .form-control { border-color: #4b9d7e; background-color: #fff; }
        .login-input:focus-within .input-group-text { color: #08765a; }
        .login-input .form-control:focus { border-color: #4b9d7e; box-shadow: 0 0 0 0.2rem rgba(16,145,105,0.14), 0 0 9px rgba(189,199,194,0.24); }
        .btn-primary { --bs-btn-bg: #08765a; --bs-btn-border-color: #08765a; --bs-btn-hover-bg: #075b46; --bs-btn-hover-border-color: #075b46; --bs-btn-active-bg: #064735; --bs-btn-active-border-color: #064735; }
        .login-submit { box-shadow: 0 7px 18px rgba(8,118,90,0.18), 0 0 10px rgba(189,199,194,0.2); }
        .login-submit:hover { box-shadow: 0 11px 24px rgba(8,118,90,0.22), 0 0 14px rgba(189,199,194,0.32); }
        .login-submit:focus-visible, .register-link:focus-visible { outline-color: rgba(16,145,105,0.65); }
        .register-link, a { color: #08765a; }
        .register-link:hover, a:hover { color: #064735; }
        .alert-info { color: #28584d; background: #eaf5f1; border-color: #cfe4dc; }
        .alert-success { color: #245b45; background: #edf6ef; border-color: #cfe4d3; }
        .alert-danger { color: #873e49; background: #fff0f2; border-color: #f0c7ce; }
        .login-card .alert-info { border-left-color: #4e9e83; }
        .login-card .alert-success { border-left-color: #4a9b70; }
        .login-card .alert-danger { border-left-color: #d05b6c; }
        @media (hover: hover) {
            .login-card:hover { border-color: rgba(8,118,90,0.3) !important; box-shadow: 0 38px 90px rgba(24,62,48,0.18), 0 12px 26px rgba(8,118,90,0.08), inset 0 0 0 1px rgba(255,255,255,0.9) !important; }
            .login-feature-icon:hover { border-color: rgba(8,118,90,0.28); box-shadow: 0 7px 16px rgba(24,62,48,0.1), 0 0 10px rgba(189,199,194,0.28); }
        }
        .login-card { transition: box-shadow 280ms ease, border-color 280ms ease; }
        .login-input:hover:not(:focus-within) { box-shadow: 0 4px 12px rgba(24,62,48,0.055); }
        .login-intro, .login-card { animation: login-enter 1000ms cubic-bezier(0.22, 1, 0.36, 1) both; }
        .login-card { animation-delay: 120ms; }
        @keyframes login-enter { from { opacity: 0; translate: 0 8px; } to { opacity: 1; translate: 0 0; } }
        .login-intro-eyebrow { animation: login-enter-top 700ms cubic-bezier(0.22, 1, 0.36, 1) 100ms both; }
        .login-intro h1 { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) 600ms both; }
        .login-intro-copy { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) 1100ms both; }
        .login-features li { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) both; }
        .login-features li:nth-child(1) { animation-delay: 1600ms; }
        .login-features li:nth-child(2) { animation-delay: 2100ms; }
        .brand-lockup { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) 200ms both; }
        .login-heading > * { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) both; }
        .login-heading > :nth-child(1) { animation-delay: 700ms; }
        .login-heading > :nth-child(2) { animation-delay: 1000ms; }
        .login-heading > :nth-child(3) { animation-delay: 1300ms; }
        .login-card .alert { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) 1600ms both; }
        .login-card form > * { animation: login-enter-right 700ms cubic-bezier(0.22, 1, 0.36, 1) both; }
        .login-card form > :nth-child(1) { animation-delay: 1800ms; }
        .login-card form > :nth-child(2) { animation-delay: 2300ms; }
        .login-card form > :nth-child(3) { animation-delay: 2800ms; }
        .register-prompt { animation: login-soft-enter 700ms cubic-bezier(0.22, 1, 0.36, 1) 3300ms both; }
        @keyframes login-soft-enter { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes login-enter-top { from { opacity: 0; transform: translateY(-7px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes login-enter-right { from { opacity: 0; transform: translateX(8px); } to { opacity: 1; transform: translateX(0); } }
        @media (max-width: 991.98px) { .login-layout { max-width: 480px; grid-template-columns: minmax(0, 1fr); gap: 0.5rem; } .login-intro { padding: 0.5rem 0 1rem; } .login-intro::before { top: 0; left: -8rem; transform: none; } .login-intro-eyebrow { margin-bottom: 0.75rem; } .login-intro h1 { max-width: 16ch; font-size: clamp(2.25rem, 10vw, 3.2rem); } .login-intro-copy { margin-bottom: 1rem; font-size: 0.96rem; } .login-features { display: none; } .login-card { justify-self: stretch; max-width: none; } }
        @media (max-width: 575.98px) { .login-card .card-body { padding: 1.5rem !important; } .brand-lockup { margin-bottom: 1.5rem; } }
        .login-shell { padding-block: clamp(2rem, 6vh, 4.5rem) !important; }
        .login-layout { width: min(100%, 1100px); grid-template-columns: minmax(0, 1.1fr) minmax(370px, 0.82fr); gap: clamp(2.5rem, 8vw, 7rem); }
        .login-intro { padding-inline: clamp(0rem, 1.5vw, 1.25rem); }
        .login-intro::before { left: -2rem; width: min(32rem, 88vw); border-color: rgba(8,118,90,0.075); background: radial-gradient(circle, rgba(255,254,250,0.48), rgba(189,199,194,0.08) 58%, transparent 70%); }
        .login-intro-eyebrow { padding: 0.48rem 0.85rem; border-color: rgba(8,118,90,0.18); background: rgba(255,254,250,0.82); color: #075b46; font-size: 0.74rem; letter-spacing: 0.09em; box-shadow: 0 4px 14px rgba(24,62,48,0.045); }
        .login-intro h1 { max-width: 12ch; margin-bottom: 1.15rem; color: #123d32; font-size: clamp(2.7rem, 5vw, 4.25rem); font-weight: 740; letter-spacing: -0.055em; line-height: 1.04; }
        .login-intro h1 span { background: linear-gradient(105deg, #075640 8%, #15936d 58%, #80958a 100%); background-clip: text; -webkit-background-clip: text; color: transparent; }
        .login-intro-copy { max-width: 35rem; margin-bottom: 2.25rem; color: #62736b; font-size: 1.02rem; line-height: 1.8; }
        .login-features { gap: 0.85rem; }
        .login-features li { color: #38564a; font-size: 0.92rem; font-weight: 550; }
        .login-feature-icon { width: 2.65rem; height: 2.65rem; border-color: #e0e7e1; border-radius: 12px; background: rgba(255,254,250,0.9); color: #08765a; box-shadow: 0 5px 15px rgba(24,62,48,0.07), inset 0 1px rgba(255,255,255,0.9); }
        .login-card { width: 100%; max-width: 440px; border-color: #dce5de !important; border-radius: 18px; background: rgba(255,254,251,0.98); box-shadow: 0 28px 75px rgba(24,62,48,0.13), 0 3px 12px rgba(24,62,48,0.04) !important; }
        .login-card::before { height: 4px; background: linear-gradient(90deg, #bdc7c2, #f8faf7 22%, #168565 62%, #bdc7c2); box-shadow: 0 1px 8px rgba(8,118,90,0.16); }
        .login-card .card-body { padding: clamp(1.75rem, 3vw, 2.5rem) !important; }
        .brand-lockup { gap: 0.9rem; margin-bottom: 2.1rem; }
        .brand-symbol { width: 3.1rem; height: 3.1rem; border: 1px solid rgba(230,237,232,0.66); border-radius: 12px; background: linear-gradient(145deg, #073d31, #08765a 72%, #25896b); box-shadow: 0 7px 18px rgba(7,61,49,0.17), inset 0 1px rgba(255,255,255,0.23); }
        .brand-name { color: #073d31; font-size: 1.02rem; letter-spacing: 0.045em; }
        .brand-description { color: #718078; font-size: 0.75rem; }
        .login-heading { margin-bottom: 1.65rem; }
        .login-heading h1 { margin-bottom: 0.45rem; color: #173f34; font-size: 1.75rem; font-weight: 720; letter-spacing: -0.04em; }
        .login-heading p { color: #718078; font-size: 0.94rem; }
        .login-card .form-label { margin-bottom: 0.55rem; color: #38564a; font-size: 0.86rem; font-weight: 650; }
        .login-input { border-radius: 10px; }
        .login-input .input-group-text { min-width: 48px; justify-content: center; border-color: #d2ddd5; background: #f0f4f0; color: #628073; }
        .login-input .form-control { min-height: 51px; border-color: #d2ddd5; background: #fffefa; font-size: 0.94rem; }
        .login-input .input-group-text:first-child { border-radius: 10px 0 0 10px; }
        .login-input .form-control:last-child { border-radius: 0 10px 10px 0; }
        .login-input:focus-within { box-shadow: 0 5px 16px rgba(24,62,48,0.08); }
        .login-input:focus-within .input-group-text, .login-input:focus-within .form-control { border-color: #47987a; }
        .login-input .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(16,145,105,0.13); }
        .login-input .form-control::placeholder { color: #98a39c; }
        .login-card form .mb-3 { margin-bottom: 1.15rem !important; }
        .login-card form .mb-4 { margin-bottom: 1.5rem !important; }
        .login-card .btn { min-height: 50px; border-radius: 10px; font-size: 0.96rem; }
        .login-submit { border-color: #08765a; background: linear-gradient(105deg, #075b46, #0a8060); box-shadow: 0 8px 18px rgba(8,118,90,0.18); }
        .login-submit:hover { border-color: #075b46; background: linear-gradient(105deg, #064735, #08765a); box-shadow: 0 10px 22px rgba(8,118,90,0.22); }
        .login-submit:active { background: #064735; }
        .register-prompt { margin-top: 1.4rem !important; color: #76847d; font-size: 0.86rem; }
        .register-link, a { color: #08765a; }
        .register-link { font-weight: 700; }
        .register-link:hover, a:hover { color: #064735; }
        .login-card .alert { border-radius: 10px; }
        @media (max-width: 991.98px) {
            .login-layout { width: min(100%, 500px); grid-template-columns: minmax(0, 1fr); gap: 0.65rem; }
            .login-intro { padding: 0.25rem 0 0.75rem; }
            .login-intro::before { left: -5rem; width: min(28rem, 90vw); }
            .login-intro h1 { max-width: 16ch; font-size: clamp(2.35rem, 8vw, 3.3rem); }
            .login-intro-copy { max-width: 38rem; margin-bottom: 0; }
            .login-features { display: none; }
            .login-card { justify-self: stretch; max-width: none; }
            .brand-lockup { margin-bottom: 1.75rem; }
        }
        @media (max-width: 575.98px) {
            .login-shell { align-items: flex-start !important; padding-block: 1.5rem !important; }
            .login-intro h1 { font-size: clamp(2.2rem, 10vw, 2.8rem); }
            .login-intro-copy { font-size: 0.93rem; line-height: 1.65; }
            .login-card .card-body { padding: 1.4rem !important; }
            .login-heading h1 { font-size: 1.55rem; }
        }
        body::before { opacity: 1; background-image: linear-gradient(rgba(8,118,90,0.13) 1px, transparent 1px), linear-gradient(90deg, rgba(8,118,90,0.13) 1px, transparent 1px); background-size: 40px 40px; mask-image: linear-gradient(to bottom, #000 0%, rgba(0,0,0,0.86) 72%, transparent 100%); }
        .login-intro-eyebrow { border-color: rgba(8,118,90,0.14); box-shadow: 0 5px 18px rgba(24,62,48,0.055), inset 0 1px rgba(255,255,255,0.9); }
        .login-intro h1 { max-width: 13ch; font-size: clamp(2.65rem, 4.8vw, 4rem); letter-spacing: -0.06em; line-height: 1.08; }
        .login-intro h1 span { background: linear-gradient(105deg, #075640 5%, #168565 64%, #93a49a 100%); background-clip: text; -webkit-background-clip: text; color: transparent; }
        .login-intro-copy { color: #65766e; }
        .login-card { position: relative; border-color: rgba(189,199,194,0.72) !important; box-shadow: 0 30px 80px rgba(24,62,48,0.12), 0 2px 8px rgba(24,62,48,0.035), inset 0 0 0 1px rgba(255,255,255,0.8) !important; }
        .login-card::before { height: 3px; background: linear-gradient(90deg, #b7c2bc, #fff 24%, #8fb4a2 50%, #08765a 72%, #c5cdc8); box-shadow: 0 1px 8px rgba(8,118,90,0.12); }
        .brand-lockup { padding-bottom: 1.35rem; border-bottom: 1px solid #e9eeea; }
        .brand-symbol { border-radius: 13px; box-shadow: 0 8px 20px rgba(7,61,49,0.16), inset 0 1px rgba(255,255,255,0.28); }
        .login-kicker { display: inline-flex; align-items: center; gap: 0.55rem; margin-bottom: 0.55rem; color: #708178; font-size: 0.69rem; font-weight: 750; letter-spacing: 0.12em; text-transform: uppercase; }
        .login-kicker::before { width: 1.2rem; height: 1px; background: linear-gradient(90deg, #08765a, #bdc7c2); content: ""; }
        .login-heading h1 { font-size: 1.8rem; letter-spacing: -0.045em; }
        .login-heading p { color: #77857e; }
        .login-card .form-label { color: #405c50; }
        .login-input .input-group-text { border-color: #d5dfd8; background: linear-gradient(145deg, #f2f5f1, #ebf1ec); }
        .login-input .form-control { border-color: #d5dfd8; }
        .login-input:focus-within { box-shadow: 0 6px 18px rgba(24,62,48,0.075); }
        .login-submit { background: linear-gradient(105deg, #075b46, #08765a 65%, #138368); box-shadow: 0 9px 20px rgba(8,118,90,0.17), inset 0 1px rgba(255,255,255,0.15); }
        .login-submit:hover { background: linear-gradient(105deg, #064735, #076b51 65%, #0d7559); box-shadow: 0 11px 24px rgba(8,118,90,0.2); }
        .register-prompt { padding-top: 0.25rem; color: #7b8781; }
        .register-link { text-underline-offset: 3px; }
        @media (max-width: 991.98px) {
            .login-intro h1 { font-size: clamp(2.35rem, 8vw, 3.25rem); }
        }
        @media (max-width: 575.98px) {
            .login-intro h1 { font-size: clamp(2.2rem, 10vw, 2.75rem); }
            .brand-lockup { padding-bottom: 1.1rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
            .login-intro-eyebrow, .login-intro h1, .login-intro-copy, .login-features li,
            .brand-lockup, .login-heading > *, .login-card .alert,
            .login-card form > *, .register-prompt { animation: none !important; }
            .login-card, .login-intro { animation: none; }
        }
    </style>
</head>
<body>
<main class="container login-shell d-flex align-items-center justify-content-center py-5">
    <div class="login-layout">
    <section class="login-intro" aria-label="Tentang SIPN">
        <div class="login-intro-eyebrow">Portal Akademik</div>
        <h1>Informasi akademik, <span>lebih terarah.</span></h1>
        <p class="login-intro-copy">Selamat datang di Sistem Informasi Pengelolaan Nilai. Masuk untuk mengakses layanan akademik sesuai dengan peran akun Anda.</p>
        <ul class="login-features">
            <li><span class="login-feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5m0 14h16M8 15v-4m4 4V7m4 8v-6" /></svg></span>Ringkasan akademik dalam satu dashboard</li>
            <li><span class="login-feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 4.5 2.8 7.8 7 10 4.2-2.2 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></svg></span>Akses menu sesuai peran akun</li>
        </ul>
    </section>
    <section class="card login-card w-100">
        <div class="card-body p-4 p-md-5">
            <div class="brand-lockup">
                <span class="brand-symbol" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-5 9 5-9 5-9-5Z" /><path d="M7 11.2V16c2.8 2.2 7.2 2.2 10 0v-4.8M21 9v6" />
                    </svg>
                </span>
                <span><span class="brand-name">SIPN</span><span class="brand-description">Sistem Informasi Pengelolaan Nilai</span></span>
            </div>
            <div class="login-heading">
                <span class="login-kicker">Akses portal akademik</span>
                <h1>Selamat Datang</h1>
                <p>Masuk ke portal akademik SIPN.</p>
            </div>
            <?php if ($registered) { ?>
                <div class="alert alert-success" role="status">Pendaftaran berhasil. Silakan login.</div>
            <?php } ?>
            <?php if ($loggedOut) { ?>
                <div class="alert alert-info" role="status">Anda berhasil keluar.</div>
            <?php } ?>
            <?php if ($error) { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error); ?></div>
            <?php } ?>
            <form action="Aksi_login.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group login-input">
                        <span class="input-group-text" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5" /><path d="M5 20a7 7 0 0 1 14 0" /></svg></span>
                        <input type="text" class="form-control" id="username" name="username" maxlength="50" autocomplete="username" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group login-input">
                        <span class="input-group-text" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" /></svg></span>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary login-submit w-100 d-flex align-items-center justify-content-center gap-2">
                    Masuk
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </button>
            </form>
            <p class="register-prompt text-center mt-4 mb-0">Belum punya akun? <a class="register-link" href="Tambah_user.php">Daftar sebagai siswa <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg></a></p>
        </div>
    </section>
    </div>
</main>
</body>
</html>
