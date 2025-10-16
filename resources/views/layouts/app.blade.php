<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name','Marketing Control') }}</title>

    {{-- Bootstrap CSS & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Fonts & Custom Styles --}}
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/theme.css')
    @vite(['resources/css/app.css','resources/js/app.js'])
    @stack('styles')
</head>

@include('partials.file-preview')
<body data-bs-theme="light">
@php
    use App\Models\MasterSekolah as MS;
    // fallback nav stats (keys baru)
    $navStats = $navStats ?? [
        'shb'=>0, 'slth'=>0, 'mou'=>0, 'tlmou'=>0, 'tolak'=>0, 'mouNoFile'=>0, 'aktivitasNow'=>0
    ];
    $is = fn($name) => request()->routeIs($name);
@endphp

{{-- TOPBAR --}}
<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">
            <i class="bi bi-kanban"></i> Marketing Control
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto">
            {{-- Toggle mini sidebar --}}
            <button id="btnMiniToggle" class="btn btn-ghost round" type="button" title="Kecilkan sidebar">
                <i class="bi bi-layout-sidebar-inset-reverse"></i>
            </button>

            @auth
                <span class="small text-muted d-none d-md-inline">Hi, {{ auth()->user()->name }}</span>
                <form id="logout-form-top" action="{{ route('logout') }}" method="POST" class="d-none d-lg-inline">@csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger round">Logout</button>
                </form>
            @endauth
        </div>
    </div>
</header>

<script>
(function(){
    const KEY = 'mcSidebarMini';
    const apply = (state) => document.body.classList.toggle('mc-mini', !!state);
    try { apply(localStorage.getItem(KEY) === '1'); } catch(e){}
    const btn = document.getElementById('btnMiniToggle');
    if(btn){
        btn.addEventListener('click', function(){
            const now = !document.body.classList.contains('mc-mini');
            apply(now);
            try { localStorage.setItem(KEY, now ? '1' : '0'); } catch(e){}
        });
    }
})();
</script>

<div class="mc-layout">
    {{-- ===== SIDEBAR (fixed) ===== --}}
    <aside id="mcSidebar" class="mc-sidebar d-flex flex-column">
        <div class="mc-sidebar-body p-0 d-flex flex-column">
            <nav class="nav flex-column mc-nav py-2">
                {{-- Dashboard --}}
                <a class="nav-link {{ $is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>

                {{-- Sekolah (Pipeline) --}}
                @if(Route::has('master.index'))
                    <a class="nav-link {{ $is('master.*') && !request('stage') ? 'active' : '' }}" href="{{ route('master.index') }}" title="Sekolah (Pipeline)">
                        <i class="bi bi-columns-gap"></i> <span>Sekolah (Pipeline)</span>
                    </a>
                @endif

                {{-- Quick Stage (skema baru) --}}
                @if(Route::has('master.index'))
                    <a class="nav-link {{ $is('master.*') && (string)request('stage')===(string)MS::ST_SHB ? 'active' : '' }}"
                       href="{{ route('master.index', ['stage'=>MS::ST_SHB]) }}" title="sudah dihubungi">
                        <i class="bi bi-person-lines-fill"></i> <span>Sudah Dihubungi</span>
                        <span class="badge bg-secondary rounded-pill ms-auto">{{ $navStats['shb'] ?? 0 }}</span>
                    </a>

                    <a class="nav-link {{ $is('master.*') && (string)request('stage')===(string)MS::ST_SLTH ? 'active' : '' }}"
                       href="{{ route('master.index', ['stage'=>MS::ST_SLTH]) }}" title="sudah dilatih">
                        <i class="bi bi-chat-dots"></i> <span>Sudah Dilatih</span>
                        <span class="badge bg-secondary rounded-pill ms-auto">{{ $navStats['slth'] ?? 0 }}</span>
                    </a>

                    <a class="nav-link {{ $is('master.*') && (string)request('stage')===(string)MS::ST_MOU ? 'active' : '' }}"
                       href="{{ route('master.index', ['stage'=>MS::ST_MOU]) }}" title="MOU Aktif">
                        <i class="bi bi-file-earmark-text"></i> <span>MOU Aktif</span>
                        <span class="badge bg-secondary rounded-pill ms-auto">{{ $navStats['mou'] ?? 0 }}</span>
                    </a>

                    <a class="nav-link {{ $is('master.*') && (string)request('stage')===(string)MS::ST_TLMOU ? 'active' : '' }}"
                       href="{{ route('master.index', ['stage'=>MS::ST_TLMOU]) }}" title="Tindak lanjut MOU">
                        <i class="bi bi-people"></i> <span>Tindak Lanjut MOU</span>
                        <span class="badge bg-secondary rounded-pill ms-auto">{{ $navStats['tlmou'] ?? 0 }}</span>
                        @if(($navStats['mouNoFile'] ?? 0) > 0)
                            <span class="badge bg-warning rounded-pill ms-2" title="MOU tahap aktif/tindak lanjut tanpa file">{{ $navStats['mouNoFile'] }}</span>
                        @endif
                    </a>

                    <a class="nav-link {{ $is('master.*') && (string)request('stage')===(string)MS::ST_TOLAK ? 'active' : '' }}"
                       href="{{ route('master.index', ['stage'=>MS::ST_TOLAK]) }}" title="Ditolak">
                        <i class="bi bi-x-octagon"></i> <span>Ditolak</span>
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $navStats['tolak'] ?? 0 }}</span>
                    </a>
                @endif

                <div class="mc-sep my-2"></div>

                {{-- Modul & Progress --}}
                @if(Route::has('modul.index'))
                <a class="nav-link {{ $is('modul.*') ? 'active' : '' }}" href="{{ route('modul.index') }}" title="Modul">
                    <i class="bi bi-box-seam"></i> <span>Modul</span>
                </a>
                @endif

                @if(Route::has('penggunaan-modul.index'))
                <a class="nav-link {{ $is('penggunaan-modul.*') ? 'active' : '' }}" href="{{ route('penggunaan-modul.index') }}" title="Penggunaan Modul">
                    <i class="bi bi-diagram-3"></i> <span>Penggunaan Modul</span>
                </a>
                @endif

                @if(Route::has('progress.index'))
                <a class="nav-link {{ $is('progress.index') ? 'active' : '' }}" href="{{ route('progress.index') }}" title="Progress Modul">
                    <i class="bi bi-graph-up-arrow"></i> <span>Progress Modul</span>
                </a>
                @endif

                @if(Route::has('progress.matrix'))
                <a class="nav-link {{ $is('progress.matrix') ? 'active' : '' }}" href="{{ route('progress.matrix') }}" title="Progress Modul (1-9)">
                    <i class="bi bi-grid-1x2"></i> <span>Progress Modul (1-9)</span>
                </a>
                @endif

                <div class="mc-sep my-2"></div>

                {{-- Aktivitas Global --}}
                @if(Route::has('aktivitas.index'))
                    <a class="nav-link {{ $is('aktivitas.*') ? 'active' : '' }}" href="{{ route('aktivitas.index') }}" title="Aktivitas">
                        <i class="bi bi-calendar2-check"></i> <span>Aktivitas</span>
                        <span class="badge bg-info rounded-pill ms-auto">{{ $navStats['aktivitasNow'] ?? 0 }}</span>
                    </a>
                @endif

                {{-- Tagihan & Admin --}}
                @if(Route::has('tagihan.index'))
                <a class="nav-link {{ $is('tagihan.*') ? 'active' : '' }}"
                   href="{{ route('tagihan.index') }}" title="Tagihan">
                   <i class="bi bi-receipt"></i> <span>Tagihan</span>
                </a>
                @endif

                @role('admin')
                  <li class="nav-item">
                    <a class="nav-link {{ $is('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" title="Pengguna">
                      <i class="bi bi-person-gear"></i> <span>Pengguna</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link {{ $is('logs.index') ? 'active' : '' }}" href="{{ route('logs.index') }}" title="Logs">
                      <i class="bi bi-clipboard-check"></i> <span>Logs</span>
                    </a>
                  </li>
                @endrole
            </nav>

            {{-- Sidebar Helper --}}
            <div class="mt-4 px-3 d-flex flex-column gap-2">
                <button class="btn btn-sm btn-ghost round d-flex justify-content-between align-items-center"
                        type="button" data-bs-toggle="collapse" data-bs-target="#collapseAktivitas" aria-expanded="false" aria-controls="collapseAktivitas">
                    <span class="small text-muted">Keterangan Aktivitas</span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <div class="collapse" id="collapseAktivitas">
                    <ul class="list-unstyled small text-muted p-0">
                        <li><span class="badge bg-secondary">MODUL_ATTACH</span> = Lampiran modul</li>
                        <li><span class="badge bg-dark">STAGE_CHANGE</span> = Perubahan stage sekolah</li>
                        <li><span class="badge bg-info">MODUL_PROGRESS</span> = Progres penggunaan modul</li>
                        <li><span class="badge bg-success">MODUL_DONE</span> = Modul selesai digunakan</li>
                        <li><span class="badge bg-warning">MODUL_REOPEN</span> = Modul dibuka ulang</li>
                        <li><span class="badge bg-primary">KUNJUNGAN</span> = Kunjungan langsung</li>
                        <li><span class="badge bg-secondary">MEETING</span> = Pertemuan tatap muka / online</li>
                        <li><span class="badge bg-success">WHATSAPP</span> = Komunikasi via WhatsApp</li>
                        <li><span class="badge bg-secondary">EMAIL</span> = Komunikasi via email</li>
                        <li><span class="badge bg-light text-dark">LAINNYA</span> = Aktivitas lain-lain</li>
                    </ul>
                </div>
            </div>

            <div class="mt-auto p-3 border-top">
                <form id="logout-form-side" action="{{ route('logout') }}" method="POST" class="w-100">@csrf
                    <button type="submit" class="btn btn-outline-danger round w-100">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- KONTEN --}}
    <main class="mc-content">
        <div class="container-fluid py-3">
            @if(session('ok'))
                <div class="alert alert-success alert-dismissible fade show elev-1" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('ok') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show elev-1" role="alert">
                    <i class="bi bi-x-circle me-1"></i>
                    <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @php
                // Stage map baru untuk breadcrumb
                $stageMap = [
                    MS::ST_CALON   => 'Calon',
                    MS::ST_SHB     => 'sudah dihubungi',
                    MS::ST_SLTH    => 'sudah dilatih',
                    MS::ST_MOU     => 'MOU Aktif',
                    MS::ST_TLMOU   => 'Tindak lanjut MOU',
                    MS::ST_TOLAK   => 'Ditolak',
                ];
                $routeName = optional(request()->route())->getName();
                $crumbs = [ ['label'=>'Dashboard','url'=> route('dashboard')] ];

                if ($routeName && str_starts_with($routeName,'master.')) {
                    $label = 'Sekolah (Pipeline)';
                    $url   = Route::has('master.index') ? route('master.index') : null;

                    if (request()->has('stage')) {
                        $s = (int) request('stage');
                        $label = $stageMap[$s] ?? ('Stage '.$s);
                        $url   = Route::has('master.index') ? route('master.index',['stage'=>$s]) : null;
                    }

                    $crumbs[] = ['label'=>$label,'url'=>$url];

                    if ($routeName === 'master.aktivitas.index')      $crumbs[] = ['label'=>'Aktivitas Sekolah','url'=>null];
                    elseif ($routeName === 'master.mou.form')         $crumbs[] = ['label'=>'MOU & TTD','url'=>null];
                    elseif ($routeName === 'master.create')           $crumbs[] = ['label'=>'Tambah Sekolah','url'=>null];
                    elseif ($routeName === 'master.edit')             $crumbs[] = ['label'=>'Ubah Sekolah','url'=>null];
                }
                elseif ($routeName && str_starts_with($routeName,'aktivitas.'))            $crumbs[] = ['label'=>'Aktivitas','url'=>null];
                elseif ($routeName && str_starts_with($routeName,'tagihan.'))              $crumbs[] = ['label'=>'Tagihan','url'=>null];
                elseif ($routeName && str_starts_with($routeName,'modul.'))                $crumbs[] = ['label'=>'Modul/Produk','url'=>null];
                elseif ($routeName && str_starts_with($routeName,'penggunaan-modul.'))     $crumbs[] = ['label'=>'Penggunaan Modul','url'=>null];
                elseif ($routeName && str_starts_with($routeName,'progress.'))             $crumbs[] = ['label'=>'Progress Modul','url'=>null];
                elseif ($routeName && (str_starts_with($routeName,'users.') || str_starts_with($routeName,'admin.users.'))) $crumbs[] = ['label'=>'User Control','url'=>null];
            @endphp

            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    @foreach($crumbs as $i => $c)
                        @php $isLast = $i === count($crumbs) - 1; @endphp
                        @if(!$isLast && !empty($c['url']))
                            <li class="breadcrumb-item"><a href="{{ $c['url'] }}">{{ $c['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $c['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>

            @yield('content')
        </div>
    </main>
</div>

{{-- Preview gambar (reusable) --}}
<div class="modal fade" id="imgPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="imgPreviewTitle">Pratinjau Gambar</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imgPreviewEl" src="" alt="" class="img-fluid rounded border">
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
<script>
function previewImage(url, title){
    try{
        document.getElementById('imgPreviewEl').src = url;
        document.getElementById('imgPreviewTitle').textContent = title || 'Pratinjau Gambar';
        const m = new bootstrap.Modal(document.getElementById('imgPreviewModal'));
        m.show();
    }catch(e){ console.error(e); }
}
</script>

<script>
(function(){
    const sb = document.getElementById('mcSidebar');
    if(!sb) return;
    let t = null;
    sb.addEventListener('scroll', () => {
        sb.classList.add('is-scrolling');
        clearTimeout(t);
        t = setTimeout(() => sb.classList.remove('is-scrolling'), 600);
    }, { passive: true });
})();
</script>

</body>
</html>
