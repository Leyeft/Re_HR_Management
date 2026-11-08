// Look of the sign-in pages (CSS + the animated Crewly brand panel).
// Shared by the SPA login and the old Inertia login until the latter is removed.

export const loginCss = `
  .page { min-height: 100vh; display: flex; flex-wrap: wrap; font-family: 'Figtree', system-ui, sans-serif; }

  .page a { color: var(--color-accent-text); }
  .page a:hover { color: var(--color-primary); }

  /* ---------- Left brand panel ---------- */
  .brand {
    flex: 1 1 480px; min-width: 0;
    background: var(--color-primary); color: #fff;
    display: flex; flex-direction: column; justify-content: space-between; gap: 32px;
    padding: 40px 48px; position: relative; overflow: hidden;
  }
  .brand-logo { display: flex; align-items: center; gap: 12px; }
  .brand-logo span { font-size: 26px; font-weight: 600; letter-spacing: -0.5px; }
  .brand h2 { margin: 0 0 8px; font-size: 30px; font-weight: 600; letter-spacing: -0.5px; line-height: 1.2; }
  .brand p { margin: 0; font-size: 16px; line-height: 1.6; color: #B8C7D9; max-width: 440px; }

  .stage-wrap { display: flex; justify-content: center; align-items: center; padding: 24px 0; }
  .stage { position: relative; width: min(420px, 74vw); aspect-ratio: 1 / 1; }
  .ring-outer { position: absolute; inset: 0; border-radius: 50%; border: 1px dashed rgba(255,255,255,.22); }
  .ring-inner { position: absolute; inset: 19%; border-radius: 50%; border: 1px solid rgba(46,196,182,.28); }
  .orbit { position: absolute; inset: 0; animation: spin 48s linear infinite; }
  .orbit-in { position: absolute; inset: 19%; animation: spin-rev 30s linear infinite; }
  .dot { position: absolute; border-radius: 50%; }

  .av {
    position: absolute; border-radius: 50%; border: 3px solid var(--color-primary);
    display: flex; align-items: center; justify-content: center; font-weight: 600;
    animation: spin-rev 48s linear infinite;
  }
  .av.lg { width: 52px; height: 52px; margin: -26px 0 0 -26px; font-size: 16px; }
  .av.md { width: 48px; height: 48px; margin: -24px 0 0 -24px; font-size: 15px; }
  .av.sm { width: 44px; height: 44px; margin: -22px 0 0 -22px; font-size: 14px; }

  .mark { position: absolute; left: 50%; top: 50%; width: 34%; aspect-ratio: 1 / 1; transform: translate(-50%, -50%); }
  .mark svg { position: absolute; inset: 0; width: 100%; height: 100%; }
  .pulse { position: absolute; inset: 0; border-radius: 28%; background: var(--color-accent); animation: pulse 3.2s ease-out 1.6s infinite; opacity: 0; }
  .arc { stroke-dasharray: 160; animation: draw 1.6s cubic-bezier(.6,0,.2,1) .2s both; }
  .person { transform-origin: 50px 52px; animation: pop .6s cubic-bezier(.3,1.5,.5,1) 1.1s both; }

  .float-card {
    position: absolute; background: #fff; color: var(--color-text);
    border-radius: 12px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(5,20,40,.35);
    animation: float 6s ease-in-out infinite;
    font-size: 13px; line-height: 1.3;
  }
  .float-card strong { display: block; font-weight: 600; }
  .float-card small { color: var(--color-text-muted); font-size: 13px; }
  .float-card .icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

  /* ---------- Right form panel ---------- */
  .form-side { flex: 1 1 420px; min-width: 0; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
  .form-box { width: 100%; max-width: 400px; }
  .page form { display: flex; flex-direction: column; gap: 20px; }
  .form-box h1 { margin: 0 0 8px; font-size: 32px; font-weight: 600; letter-spacing: -0.8px; color: var(--color-primary); }
  .sub { margin: 0; font-size: 16px; color: var(--color-text-muted); }

  .status { font-size: 14px; font-weight: 500; color: var(--color-accent-text); }

  .field { display: flex; flex-direction: column; gap: 8px; }
  .field label { font-size: 14px; font-weight: 500; }
  .field-row { display: flex; justify-content: space-between; align-items: baseline; }
  .field-row a { font-size: 14px; font-weight: 500; text-decoration: none; }
  .input-wrap { position: relative; }
  .input-wrap > svg { position: absolute; left: 14px; top: 15px; pointer-events: none; }
  .input {
    width: 100%; height: 48px; padding: 0 14px 0 44px;
    font: inherit; font-size: 16px; color: var(--color-text);
    background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: 10px;
    transition: border-color .2s, box-shadow .2s;
  }
  .input.has-toggle { padding-right: 52px; }
  .input:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 4px rgba(46,196,182,.22); }
  .input.invalid { border-color: var(--color-danger); }
  .error { font-size: 13px; color: var(--color-danger); min-height: 0; }
  .error:empty { display: none; }

  .eye {
    position: absolute; right: 2px; top: 2px; width: 44px; height: 44px;
    border: 0; background: transparent; cursor: pointer; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
  }

  .remember { display: inline-flex; align-items: center; gap: 10px; font-size: 14px; color: var(--color-text-muted); cursor: pointer; min-height: 44px; }
  .remember input { width: 18px; height: 18px; accent-color: var(--color-primary); margin: 0; }

  .btn {
    height: 50px; border-radius: 10px; font: inherit; font-size: 16px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;
  }
  .btn-primary { border: 0; background: var(--color-primary); color: #fff; font-weight: 600; transition: background .2s, transform .15s; }
  .btn-primary:hover { background: var(--color-primary-hover); }
  .btn-primary:active { transform: scale(.98); }
  .btn-primary:disabled { opacity: .7; cursor: default; }
  .btn-sso {
    border: 1.5px dashed #C5D0DC; background: var(--color-bg); color: var(--color-text-muted);
    font-weight: 500; cursor: not-allowed;
  }
  .tag { font-size: 12px; font-weight: 600; letter-spacing: .3px; color: var(--color-warning); background: var(--color-warning-bg); padding: 3px 8px; border-radius: 999px; }
  .divider { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--color-text-muted); }
  .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--color-border); }
  .footer-note { margin: 8px 0 0; font-size: 14px; color: var(--color-text-muted); text-align: center; }
  .footer-note a { font-weight: 500; }

  .spinner { animation: spin .8s linear infinite; }
  .hidden { display: none !important; }

  .fade-up { animation: up .7s cubic-bezier(.2,.7,.2,1) both; }

  .btn:focus-visible, .eye:focus-visible, .page a:focus-visible { outline: 3px solid var(--color-accent); outline-offset: 2px; }

  /* ---------- Animations ---------- */
  @keyframes spin { to { transform: rotate(360deg); } }
  @keyframes spin-rev { to { transform: rotate(-360deg); } }
  @keyframes draw { from { stroke-dashoffset: 160; } to { stroke-dashoffset: 0; } }
  @keyframes pop { 0% { opacity: 0; transform: scale(.6); } 100% { opacity: 1; transform: scale(1); } }
  @keyframes pulse { 0% { transform: scale(.85); opacity: .45; } 70% { transform: scale(1.35); opacity: 0; } 100% { opacity: 0; } }
  @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
  @keyframes up { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

  @media (prefers-reduced-motion: reduce) {
    .page *, .page *::before, .page *::after { animation: none !important; transition: none !important; }
    .pulse { display: none; }
  }
  @media (max-width: 520px) {
    .brand { padding: 32px 24px; }
    .float-card { display: none; }
  }
`;

export const brandPanel = `
  <div class="brand-logo">
    <svg width="40" height="40" viewBox="0 0 100 100" aria-hidden="true"><path d="M73 30A31 31 0 1 0 73 70" fill="none" stroke="#2EC4B6" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="40" r="9" fill="#fff"/><path d="M35 67a15 15 0 0 1 30 0" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round"/></svg>
    <span>Crewly</span>
  </div>

  <div class="stage-wrap" aria-hidden="true">
    <div class="stage">
      <div class="ring-outer"></div>
      <div class="ring-inner"></div>

      <div class="orbit-in">
        <div class="dot" style="left:50%;top:-5px;margin-left:-5px;width:10px;height:10px;background:#2EC4B6"></div>
        <div class="dot" style="left:6%;top:72%;width:8px;height:8px;background:rgba(255,255,255,.6)"></div>
        <div class="dot" style="left:90%;top:76%;width:6px;height:6px;background:#2EC4B6"></div>
      </div>

      <div class="orbit">
        <div class="av lg" style="left:50%;top:0%;background:#F5B971;color:#5A3300">AN</div>
        <div class="av sm" style="left:97.6%;top:34.5%;background:#9FD8F5;color:#0B3A57;width:46px;height:46px;margin:-23px 0 0 -23px">KP</div>
        <div class="av lg" style="left:79.3%;top:90.5%;background:#2EC4B6;color:#053B36">MS</div>
        <div class="av sm" style="left:20.7%;top:90.5%;background:#F4A3B8;color:#5C1426">JL</div>
        <div class="av md" style="left:2.4%;top:34.5%;background:#C9B8F7;color:#2E1B66">TR</div>
      </div>

      <div class="mark">
        <div class="pulse"></div>
        <svg viewBox="0 0 100 100">
          <rect width="100" height="100" rx="24" fill="#0C2342"/>
          <path class="arc" d="M73 30A31 31 0 1 0 73 70" fill="none" stroke="#2EC4B6" stroke-width="7" stroke-linecap="round"/>
          <g class="person"><circle cx="50" cy="40" r="8.5" fill="#fff"/><path d="M35 67a15 15 0 0 1 30 0" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"/></g>
        </svg>
      </div>

      <div class="float-card" style="left:-6%;top:8%">
        <div class="icon" style="background:#E3F4EA"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#187A46" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></div>
        <div><strong>Leave approved</strong><small>Annual leave</small></div>
      </div>
      <div class="float-card" style="right:-8%;bottom:14%;animation-delay:-3s">
        <div class="icon" style="background:#E0F6F3"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B7A72" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M18 8v6M15 11h6"/></svg></div>
        <div><strong>New teammate</strong><small>Onboarding started</small></div>
      </div>
    </div>
  </div>

  <div>
    <h2>Your whole team, in one place.</h2>
    <p>Hiring, leave, payroll and people records &mdash; all in one calm workspace.</p>
  </div>
`;
