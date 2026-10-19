<?php
  require("../../src/server/helper/print_alert.php");
  require("../../src/server/helper/auth.php");

  if (auth()) {
    header("Location: ../../");
    exit;
  }
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">

  <link rel="manifest" href="../../manifest.json" type="application/json">
  <link rel="icon" href="../../src/public/images/icons/icon-144x144.png"
        sizes="144x144" type="image/png">

  <link href="../../node_modules/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <link rel="stylesheet"
        href="../../node_modules/@fortawesome/fontawesome-free/css/all.css">

  <title>Login | POS Dashboard</title>

  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --text-main: #0f172a;
      --text-muted: #64748b;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: Inter, "Segoe UI", Arial, sans-serif;
      background: #f8fafc;
      color: var(--text-main);
    }

    .login-page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .login-brand {
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 56px;
      color: #fff;
      background:
        radial-gradient(circle at 15% 15%, #818cf8 0, transparent 35%),
        linear-gradient(145deg, #0f172a 5%, #312e81 58%, #4338ca 100%);
    }

    .login-brand::before,
    .login-brand::after {
      content: "";
      position: absolute;
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 50%;
      pointer-events: none;
    }

    .login-brand::before {
      width: 420px;
      height: 420px;
      right: -160px;
      top: 15%;
    }

    .login-brand::after {
      width: 300px;
      height: 300px;
      right: -100px;
      top: 23%;
    }

    .brand-content,
    .brand-footer,
    .brand-logo {
      position: relative;
      z-index: 1;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 13px;
      font-size: 23px;
      font-weight: 750;
      letter-spacing: -.5px;
    }

    .brand-logo-icon {
      display: grid;
      place-items: center;
      width: 48px;
      height: 48px;
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 15px;
      background: rgba(255,255,255,.13);
      font-size: 21px;
    }

    .brand-content {
      max-width: 470px;
      margin: 70px 0;
    }

    .brand-tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 13px;
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 999px;
      background: rgba(255,255,255,.08);
      color: #e0e7ff;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .6px;
      text-transform: uppercase;
    }

    .brand-content h1 {
      margin: 25px 0 18px;
      font-size: clamp(36px, 4vw, 54px);
      font-weight: 800;
      line-height: 1.12;
      letter-spacing: -1.8px;
    }

    .brand-content p {
      max-width: 400px;
      color: #c7d2fe;
      font-size: 16px;
      line-height: 1.8;
    }

    .brand-features {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 35px;
    }

    .brand-feature {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 11px 13px;
      border-radius: 12px;
      background: rgba(255,255,255,.09);
      color: #eef2ff;
      font-size: 13px;
    }

    .brand-feature i {
      color: #a5f3fc;
    }

    .brand-footer {
      color: #c7d2fe;
      font-size: 12px;
    }

    .login-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 28px;
      background: #fff;
    }

    .login-form-wrap {
      width: 100%;
      max-width: 410px;
    }

    .mobile-logo {
      display: none;
    }

    .welcome-label {
      margin-bottom: 12px;
      color: var(--primary);
      font-size: 12px;
      font-weight: 750;
      letter-spacing: 1.5px;
      text-transform: uppercase;
    }

    .login-title {
      margin-bottom: 12px;
      font-size: 34px;
      font-weight: 800;
      letter-spacing: -1.2px;
    }

    .login-description {
      margin-bottom: 34px;
      color: var(--text-muted);
      font-size: 14px;
      line-height: 1.7;
    }

    .form-label {
      margin-bottom: 9px;
      color: #334155;
      font-size: 13px;
      font-weight: 650;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap > i {
      position: absolute;
      top: 50%;
      left: 16px;
      transform: translateY(-50%);
      color: #94a3b8;
      pointer-events: none;
    }

    .form-control {
      min-height: 52px;
      padding: 13px 15px 13px 45px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      color: var(--text-main);
      font-size: 14px;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .form-control:focus {
      border-color: var(--primary);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    .password-toggle {
      position: absolute;
      top: 50%;
      right: 13px;
      transform: translateY(-50%);
      padding: 5px;
      border: 0;
      background: transparent;
      color: #64748b;
      cursor: pointer;
    }

    .password-input {
      padding-right: 45px;
    }

    .login-button {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      min-height: 52px;
      margin-top: 26px;
      border: 0;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      box-shadow: 0 8px 18px rgba(79,70,229,.22);
      transition: transform .2s, box-shadow .2s;
    }

    .login-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 22px rgba(79,70,229,.28);
      color: #fff;
    }

    .login-button:active {
      transform: translateY(0);
    }

    .login-note {
      margin-top: 25px;
      color: #94a3b8;
      font-size: 12px;
      line-height: 1.7;
      text-align: center;
    }

    .login-note i {
      margin-right: 5px;
      color: #10b981;
    }

    @media (max-width: 900px) {
      .login-page {
        grid-template-columns: 0.85fr 1.15fr;
      }

      .login-brand {
        padding: 35px;
      }

      .brand-content h1 {
        font-size: 38px;
      }

      .brand-features {
        flex-direction: column;
      }
    }

    @media (max-width: 680px) {
      .login-page {
        display: block;
        background: #f8fafc;
      }

      .login-brand {
        display: none;
      }

      .login-panel {
        min-height: 100vh;
        padding: 35px 24px;
        background:
          radial-gradient(circle at top right, #e0e7ff 0, transparent 35%),
          #f8fafc;
      }

      .login-form-wrap {
        max-width: 430px;
        padding: 30px 24px;
        border: 1px solid #eef2f7;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 18px 50px rgba(15,23,42,.07);
      }

      .mobile-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 32px;
        font-size: 19px;
        font-weight: 800;
      }

      .mobile-logo-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        border-radius: 13px;
        background: #eef2ff;
        color: var(--primary);
      }

      .login-title {
        font-size: 30px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        transition: none !important;
      }
    }
  </style>
</head>

<body>
  <?php print_alert(); ?>

  <main class="login-page">
    <section class="login-brand">
      <div class="brand-logo">
        <div class="brand-logo-icon">
          <i class="fas fa-cubes"></i>
        </div>
        <span>POS Dashboard</span>
      </div>

      <div class="brand-content">
        <span class="brand-tag">
          <i class="fas fa-bolt"></i>
          Point of Sale System
        </span>

        <h1>Kelola bisnis lebih mudah.</h1>

        <p>
          Kelola transaksi dan pantau produk dalam satu tempat.
          Mulai operasional dengan sistem kasir yang praktis dan terintegrasi.
        </p>

        <div class="brand-features">
          <div class="brand-feature">
            <i class="fas fa-receipt"></i>
            Transaksi praktis
          </div>
          <div class="brand-feature">
            <i class="fas fa-boxes-stacked"></i>
            Data produk terintegrasi
          </div>
          <div class="brand-feature">
            <i class="fas fa-chart-line"></i>
            Operasional terorganisir
          </div>
        </div>
      </div>

      <div class="brand-footer">
        &copy; <?php echo date("Y"); ?> POS Dashboard
      </div>
    </section>

    <section class="login-panel">
      <div class="login-form-wrap">
        <div class="mobile-logo">
          <div class="mobile-logo-icon">
            <i class="fas fa-cubes"></i>
          </div>
          <span>POS Dashboard</span>
        </div>

        <div class="welcome-label">Selamat datang kembali</div>
        <h2 class="login-title">Login akun</h2>
        <p class="login-description">
          Masukkan username dan password untuk melanjutkan ke dashboard.
        </p>

        <form action="../../src/server/helper/login.php" method="POST">
          <div class="mb-4">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
              <i class="fas fa-user"></i>
              <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                placeholder="Masukkan username"
                autocomplete="username"
                required
                autofocus
              >
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label" for="password">Password</label>
            <div class="input-wrap">
              <i class="fas fa-lock"></i>
              <input
                type="password"
                id="password"
                name="password"
                class="form-control password-input"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
              >
              <button
                class="password-toggle"
                type="button"
                id="togglePassword"
                aria-label="Tampilkan password"
                aria-pressed="false"
              >
                <i class="fas fa-eye" id="passwordIcon"></i>
              </button>
            </div>
          </div>

          <button class="btn login-button" name="login" type="submit">
            <span>Masuk ke Dashboard</span>
            <i class="fas fa-arrow-right"></i>
          </button>
        </form>

        <div class="login-note">
          <i class="fas fa-shield-alt"></i>
          Akses sistem menggunakan akun yang terdaftar.
          <br>
          Kelompok 5
        </div>
      </div>
    </section>
  </main>

  <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const passwordIcon = document.getElementById("passwordIcon");

    togglePassword.addEventListener("click", function () {
      const showPassword = passwordInput.type === "password";

      passwordInput.type = showPassword ? "text" : "password";
      passwordIcon.className = showPassword ? "fas fa-eye-slash" : "fas fa-eye";
      togglePassword.setAttribute("aria-pressed", String(showPassword));
      togglePassword.setAttribute(
        "aria-label",
        showPassword ? "Sembunyikan password" : "Tampilkan password"
      );
    });
  </script>
</body>
</html>