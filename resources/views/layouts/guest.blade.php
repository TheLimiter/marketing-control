<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Masuk') — {{ config('app.name','Marketing Control') }}</title>

  {{-- === Sama seperti layouts.app === --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  {{-- urutan penting: Bootstrap → Tailwind/app.css → theme.css (override) --}}
  @vite(['resources/css/app.css','resources/css/theme.css','resources/js/app.js'])

  <style>
    body{min-height:100vh;display:grid;place-items:center;background:var(--bs-body-bg,#F7F8FC)}
    .auth-card{width:min(980px,96vw);border-radius:18px;box-shadow:var(--shadow,0 8px 20px rgba(38,43,64,.06))}
    .auth-left{
      background:
        radial-gradient(1200px 600px at 0% 0%, rgba(13,110,253,.10), transparent 60%),
        radial-gradient(1200px 600px at 100% 100%, rgba(25,135,84,.10), transparent 60%);
    }
    .brand-badge{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;
      background:linear-gradient(135deg,var(--bs-primary),var(--bs-info));color:#fff}
  </style>
  @stack('styles')
</head>
<body>
  @yield('content')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
