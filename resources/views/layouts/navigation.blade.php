<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name','Marketing Control') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Marketing Control</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mcNav"
                aria-controls="mcNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mcNav">
                <ul class="navbar-nav me-auto">

                    {{-- Dashboard --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">Dashboard</a>
                    </li>

                    {{-- Sekolah (Pipeline) --}}
                    <li class="nav-item">
                        @if(Route::has('master.index'))
                            <a class="nav-link {{ request()->routeIs('master.*') && !request('stage') ? 'active' : '' }}"
                                href="{{ route('master.index') }}">Sekolah (Pipeline)</a>
                        @else
                            <span class="nav-link disabled">Sekolah (Pipeline)
                                <span class="badge text-bg-warning ms-1">Belum Ada</span>
                            </span>
                        @endif
                    </li>

                    {{-- Quick filter: Prospek (2) --}}
                    <li class="nav-item">
                        @if(Route::has('master.index'))
                            <a class="nav-link {{ request()->routeIs('master.*') && (string)request('stage')==='2' ? 'active' : '' }}"
                                href="{{ route('master.index', ['stage'=>\App\Models\MasterSekolah::ST_PROSPEK]) }}">
                                Prospek
                            </a>
                        @else
                            <span class="nav-link disabled">Prospek <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Quick filter: Negosiasi (3) --}}
                    <li class="nav-item">
                        @if(Route::has('master.index'))
                            <a class="nav-link {{ request()->routeIs('master.*') && (string)request('stage')==='3' ? 'active' : '' }}"
                                href="{{ route('master.index', ['stage'=>\App\Models\MasterSekolah::ST_NEGOSIASI]) }}">
                                Negosiasi
                            </a>
                        @else
                            <span class="nav-link disabled">Negosiasi <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Quick filter: MOU (4) --}}
                    <li class="nav-item">
                        @if(Route::has('master.index'))
                            <a class="nav-link {{ request()->routeIs('master.*') && (string)request('stage')==='4' ? 'active' : '' }}"
                                href="{{ route('master.index', ['stage'=>\App\Models\MasterSekolah::ST_MOU]) }}">
                                MOU
                            </a>
                        @else
                            <span class="nav-link disabled">MOU <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Quick filter: Klien Aktif (5) --}}
                    <li class="nav-item">
                        @if(Route::has('master.index'))
                            <a class="nav-link {{ request()->routeIs('master.*') && (string)request('stage')==='5' ? 'active' : '' }}"
                                href="{{ route('master.index', ['stage'=>\App\Models\MasterSekolah::ST_KLIEN]) }}">
                                Klien Aktif
                            </a>
                        @else
                            <span class="nav-link disabled">Klien Aktif <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Aktivitas Global --}}
                    <li class="nav-item">
                        @if(Route::has('aktivitas.index'))
                            <a class="nav-link {{ request()->routeIs('aktivitas.*') ? 'active' : '' }}"
                                href="{{ route('aktivitas.index') }}">Aktivitas</a>
                        @else
                            <span class="nav-link disabled">Aktivitas <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Tagihan --}}
                    <li class="nav-item">
                        @if(Route::has('tagihan.index'))
                            <a class="nav-link {{ request()->routeIs('tagihan.*') ? 'active' : '' }}"
                                href="{{ route('tagihan.index') }}">Tagihan</a>
                        @else
                            <span class="nav-link disabled">Tagihan <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Modul/Produk --}}
                    <li class="nav-item">
                        @if(Route::has('modul.index'))
                            <a class="nav-link {{ request()->routeIs('modul.*') ? 'active' : '' }}"
                                href="{{ route('modul.index') }}">Modul/Produk</a>
                        @else
                            <span class="nav-link disabled">Modul/Produk <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Pengguna Modul --}}
                    <li class="nav-item">
                        @if(Route::has('penggunaan-modul.index'))
                            <a class="nav-link {{ request()->routeIs('penggunaan-modul.*') ? 'active' : '' }}"
                                href="{{ route('penggunaan-modul.index') }}">Pengguna Modul</a>
                        @else
                            <span class="nav-link disabled">Pengguna Modul <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- Logs --}}
                    <li class="nav-item">
                        @if(Route::has('logs.index'))
                            <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}"
                                href="{{ route('logs.index') }}">Logs</a>
                        @else
                            <span class="nav-link disabled">Logs <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                        @endif
                    </li>

                    {{-- User Control (Admin only) --}}
                    @auth
                        @if(method_exists(auth()->user(),'isAdmin') ? auth()->user()->isAdmin() : (strtolower(auth()->user()->role ?? '')==='admin'))
                            <li class="nav-item">
                                @if(Route::has('users.index'))
                                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                        href="{{ route('users.index') }}">User Control</a>
                                @else
                                    <span class="nav-link disabled">User Control <span class="badge text-bg-warning ms-1">Belum Ada</span></span>
                                @endif
                            </li>
                        @endif
                    @endauth

                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-3">

        {{-- Flash --}}
        @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        {{-- Breadcrumb sederhana --}}
        @php
            use App\Models\MasterSekolah as MS;
            $stageMap = [
                MS::ST_CALON=>'Calon', MS::ST_PROSPEK=>'Prospek', MS::ST_NEGOSIASI=>'Negosiasi',
                MS::ST_MOU=>'MOU', MS::ST_KLIEN=>'Klien',
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

                if ($routeName === 'master.aktivitas.index')       $crumbs[] = ['label'=>'Aktivitas Sekolah','url'=>null];
                elseif ($routeName === 'master.mou.form')          $crumbs[] = ['label'=>'MOU & TTD','url'=>null];
                elseif ($routeName === 'master.create')            $crumbs[] = ['label'=>'Tambah Sekolah','url'=>null];
                elseif ($routeName === 'master.edit')              $crumbs[] = ['label'=>'Ubah Sekolah','url'=>null];
            }
            elseif ($routeName && str_starts_with($routeName,'aktivitas.'))          $crumbs[] = ['label'=>'Aktivitas','url'=>null];
            elseif ($routeName && str_starts_with($routeName,'tagihan.'))            $crumbs[] = ['label'=>'Tagihan','url'=>null];
            elseif ($routeName && str_starts_with($routeName,'modul.'))              $crumbs[] = ['label'=>'Modul/Produk','url'=>null];
            elseif ($routeName && str_starts_with($routeName,'penggunaan-modul.'))   $crumbs[] = ['label'=>'Pengguna Modul','url'=>null];
            elseif ($routeName && str_starts_with($routeName,'logs.'))               $crumbs[] = ['label'=>'Logs','url'=>null];
            elseif ($routeName && str_starts_with($routeName,'users.'))              $crumbs[] = ['label'=>'User Control','url'=>null];
        @endphp

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
