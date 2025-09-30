{{-- resources/views/bestRising/login/index.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Masuk | Best Rising</title>

  {{-- AdminLTE + FontAwesome (sesuaikan path kalau beda) --}}
  <link rel="stylesheet" href="{{ asset('adminLTE/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminLTE/dist/css/adminlte.min.css') }}">

  <style>
    :root{ --brand:#28a745; --brand-soft:#eaf6ee; --text:#2b2f33; }
    html,body{ height:100%; }
    body{
      min-height:100%; margin:0;
      background:
        radial-gradient(1000px 400px at -10% -10%, #f2fff6 0, transparent 60%),
        radial-gradient(800px 400px at 110% 10%, #eefaf1 0, transparent 60%),
        #f7fafc;
      display:flex; align-items:center; justify-content:center;
      padding:24px;
      font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans","Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    }

    /* ====== Splash Screen ====== */
    .splash{
      position:fixed; inset:0; z-index:9999;
      background:
        radial-gradient(1200px 600px at 20% -10%, #f2fff6 0, transparent 60%),
        radial-gradient(1000px 600px at 110% 20%, #eefaf1 0, transparent 60%),
        #ffffff;
      display:flex; align-items:center; justify-content:center;
      transition:opacity .6s ease, visibility .6s ease; /* fade sedikit lebih lama */
    }
    .splash.is-hidden{ opacity:0; visibility:hidden; }

    .splash-box{
      background:#fff; border:1px solid #edf2f7; border-radius:18px;
      box-shadow:0 10px 30px rgba(40,167,69,.12);
      padding:24px 28px; text-align:center; min-width:260px;
    }
    .splash-logo{
      width:58px; height:58px; border-radius:50%;
      background:var(--brand-soft); display:flex; align-items:center; justify-content:center;
      margin:0 auto 12px; box-shadow:inset 0 0 0 2px rgba(40,167,69,.15);
    }
    .splash-title{ font-weight:800; color:#1f2937; letter-spacing:.2px; }
    .splash-sub{ color:#6b7280; font-size:.9rem; }

    .spinner{
      width:26px; height:26px; border-radius:50%;
      border:3px solid rgba(40,167,69,.18); border-top-color:var(--brand);
      margin:12px auto 6px; animation:spin 1s linear infinite;
    }
    @keyframes spin{ to{ transform:rotate(360deg); } }

    .dots{ display:inline-flex; gap:4px; margin-top:6px; }
    .dots i{
      width:6px; height:6px; border-radius:50%; background:var(--brand);
      opacity:.25; animation:bounce 1.2s infinite ease-in-out;
    }
    .dots i:nth-child(2){ animation-delay:.2s; }
    .dots i:nth-child(3){ animation-delay:.4s; }
    @keyframes bounce{
      0%, 80%, 100%{ transform:translateY(0); opacity:.25; }
      40%{ transform:translateY(-6px); opacity:1; }
    }

    @media (prefers-reduced-motion: reduce) {
      .spinner, .dots i { animation: none; }
      .splash{ transition:none; }
    }

    /* ====== Login Card ====== */
    .login-card{
      width:100%; max-width:460px; background:#fff;
      border:1px solid #edf2f7; border-radius:18px; overflow:hidden;
      box-shadow:0 10px 30px rgba(40,167,69,.10);
      opacity:0; transform:translateY(6px);
      transition:opacity .35s ease, transform .35s ease;
    }
    .login-card.is-ready{ opacity:1; transform:translateY(0); }

    .login-header{
      padding:24px 22px;
      background:linear-gradient(135deg,#f0fff4 0%,#ffffff 60%);
      border-bottom:1px solid #edf2f7;
    }
    .brand{ display:flex; align-items:center; gap:12px; text-decoration:none; color:var(--text); }
    .brand-logo{
      width:42px; height:42px; border-radius:50%;
      background:var(--brand-soft); color:var(--brand);
      display:flex; align-items:center; justify-content:center;
      box-shadow:inset 0 0 0 2px rgba(40,167,69,.15);
    }
    .brand-title{ font-weight:800; letter-spacing:.3px; }
    .small-muted{ color:#6b7280; font-size:.9rem; }

    .login-body{ padding:22px; }
    .form-label{ font-weight:700; color:#374151; }

    /* === Input group sinkron === */
    .br-input .input-group-text,
    .br-input .form-control{ border-color:#e5e7eb; height:48px; }
    .br-input .input-group-text{ background:#fff; color:#9ca3af; }
    .br-input .form-control{
      border-top-right-radius:12px; border-bottom-right-radius:12px; padding-left:12px;
    }
    .br-input .input-group-prepend .input-group-text{
      border-top-left-radius:12px; border-bottom-left-radius:12px;
    }
    .br-input .input-group-append .input-group-text{
      border-top-right-radius:12px; border-bottom-right-radius:12px; cursor:pointer;
    }
    .br-input .form-control:focus{
      box-shadow:0 0 0 .15rem rgba(40,167,69,.15);
      border-color:#8fd19e;
    }

    .btn-brand{
      background:var(--brand); border-color:var(--brand);
      border-radius:12px; font-weight:800;
      box-shadow:0 10px 16px rgba(40,167,69,.18);
    }
    .btn-brand:hover{ filter:brightness(.95); }

    .alert-soft{
      background:#f8fffb; border:1px solid #e3f7ea; color:#1f7a3b;
      border-radius:12px; padding:10px 12px; font-size:.9rem;
    }
    .invalid-feedback{ display:block; }
    .link{ color:var(--brand); text-decoration:none; }
    .link:hover{ text-decoration:underline; }
  </style>
</head>
<body>

  {{-- ===== Splash Screen ===== --}}
  <div class="splash" id="splash">
    <div class="splash-box">
      <div class="splash-logo">
        <img src="/images/mandau.png" alt="Logo" style="width:40px;height:40px;border-radius:50%;">
      </div>
      <div class="splash-title">Mandau Racing Stars</div>
      <div class="splash-sub">Menyiapkan halaman...</div>
      <div class="spinner" aria-hidden="true"></div>
      <div class="dots" aria-hidden="true"><i></i><i></i><i></i></div>
    </div>
  </div>

  {{-- ===== Login Card ===== --}}
  <div class="login-card" id="loginCard" aria-hidden="true">
    <div class="login-header">
      <a href="{{ url('/') }}" class="brand">
        <div class="brand-logo">
          <img src="/images/mandau.png" alt="" style="width: 50px; height: 50px;">
        </div>
        <div>
          <div class="brand-title">Mandau Racing Stars</div>
          <div class="small-muted">Silakan masuk ke akun anda</div>
        </div>
      </a>
    </div>

    <div class="login-body">
      @if (session('status'))
        <div class="alert-soft mb-3">
          <i class="fas fa-info-circle mr-1"></i> {{ session('status') }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" id="formLogin" novalidate>
        @csrf

        {{-- Email --}}
        <div class="form-group mb-3">
          <label for="email" class="form-label">Email</label>
          <div class="input-group input-group-lg br-input">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            </div>
            <input id="email" type="email" name="email"
                   value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@email.com" required autofocus>
          </div>
          @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        {{-- Password --}}
        <div class="form-group mb-2">
          <label for="password" class="form-label">Kata sandi</label>
          <div class="input-group input-group-lg br-input">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
            </div>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required>
            <div class="input-group-append">
              <span class="input-group-text" id="togglePass" title="Lihat/Sembunyikan">
                <i class="far fa-eye"></i>
              </span>
            </div>
          </div>
          @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember')?'checked':'' }}>
            <label class="form-check-label small-muted" for="remember">Ingat saya</label>
          </div>
          @if (Route::has('password.request'))
            <a class="small-muted link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
          @endif
        </div>

        <button type="submit" class="btn btn-brand btn-block btn-lg">
          <i class="fas fa-sign-in-alt mr-1"></i> Masuk
        </button>
      </form>

      @if (Route::has('register'))
        <div class="text-center mt-3 small-muted">
          Belum punya akun? <a href="{{ route('register') }}" class="link">Daftar</a>
        </div>
      @endif
    </div>
  </div>

  <script src="{{ asset('adminLTE/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('adminLTE/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script>
    // ====== Splash logic (dengan minimum durasi lebih lama dikit) ======
    (function(){
      const splash = document.getElementById('splash');
      const card   = document.getElementById('loginCard');

      const MIN_DURATION = 1000; // << lama minimum tampil splash (ms) — dibikin lebih lama dikit
      const MAX_DURATION = 5000; // << batas maksimum tunggu (ms) untuk safety
      const AFTER_FADE_DELAY = 240; // jeda kecil setelah splash hilang sebelum card muncul
      const start = performance.now();

      let hidden = false;
      function hideSplash(){
        if (hidden) return;
        hidden = true;
        splash.classList.add('is-hidden');
        setTimeout(function(){
          card.classList.add('is-ready');
          card.removeAttribute('aria-hidden');
          const email = document.getElementById('email'); if (email) email.focus();
        }, AFTER_FADE_DELAY);
      }

      // Saat semua asset selesai
      window.addEventListener('load', function(){
        const elapsed = performance.now() - start;
        const waitMore = Math.max(0, MIN_DURATION - elapsed);
        setTimeout(hideSplash, waitMore);
      });

      // Safety: apapun yang terjadi, jangan lebih dari MAX_DURATION
      setTimeout(hideSplash, MAX_DURATION);
    })();

    // ====== Toggle show/hide password + submit state ======
    document.addEventListener('DOMContentLoaded', function(){
      const toggle = document.getElementById('togglePass');
      if(toggle){
        toggle.addEventListener('click', function(){
          const input = document.getElementById('password');
          const icon  = this.querySelector('i');
          if(input.type === 'password'){
            input.type = 'text';
            icon.classList.replace('fa-eye','fa-eye-slash');
          }else{
            input.type = 'password';
            icon.classList.replace('fa-eye-slash','fa-eye');
          }
        });
      }

      const form = document.getElementById('formLogin');
      if(form){
        form.addEventListener('submit', function(){
          const btn = this.querySelector('button[type=submit]');
          if(btn){
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
          }
        });
      }
    });
  </script>
</body>
</html>
