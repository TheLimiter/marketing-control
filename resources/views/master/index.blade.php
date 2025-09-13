@extends('layouts.app')

@php
    // mapping stage -> label
    $stageOptions = [
        \App\Models\MasterSekolah::ST_CALON     => 'Calon',
        \App\Models\MasterSekolah::ST_PROSPEK   => 'Prospek',
        \App\Models\MasterSekolah::ST_NEGOSIASI => 'Negosiasi',
        \App\Models\MasterSekolah::ST_MOU       => 'MOU',
        \App\Models\MasterSekolah::ST_KLIEN     => 'Klien',
    ];

    $st = (string) request('stage');
@endphp

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Master Sekolah</div>
            <div class="subtle">Kelola pipeline & status kemajuan</div>
        </div>
        <a href="{{ route('master.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah Sekolah
        </a>
    </div>

    {{-- Toolbar (search + select stage + per) --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari nama sekolah</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="Ketik nama sekolah...">
            </div>

            <div class="field" style="min-width:200px">
                <label>Stage</label>
                <select name="stage" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string)request('stage')===(string)$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:140px">
                <label>Per halaman</label>
                <select name="per_page" class="select-soft" onchange="this.form.submit()">
                    @foreach([15,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected((int)request('per_page',15)===$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button class="btn btn-primary round">Terapkan</button>
                @if(request()->filled('q') || request()->filled('stage') || request()->filled('per_page'))
                    <a href="{{ route('master.index') }}" class="btn btn-ghost round">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Tabel utama --}}
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-compact table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Sekolah</th>
                        <th>Kontak</th>
                        <th style="min-width:220px;">Stage</th>
                        <th class="text-center" style="width:90px;">MOU</th>
                        <th class="text-center" style="width:80px;">TTD</th>
                        <th style="min-width:170px;">Progress</th>
                        <th style="width:120px;">Update</th>
                        <th style="min-width:260px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $x) {{-- $rows = paginator MasterSekolah --}}
                        @php
                            $sid      = $x->id;
                            $mouAda   = !is_null($x->mou_path);
                            $progress = (int)($x->modul_percent ?? 0);
                        @endphp
                        <tr>
                            {{-- Nama & alamat --}}
                            <td class="fw-medium">
                                <a href="{{ route('master.edit',$sid) }}" class="text-decoration-none">
                                    {{ $x->nama_sekolah ?? '-' }}
                                </a>
                                <div class="small text-muted">{{ $x->alamat ?: '&mdash;' }}</div>
                            </td>

                            {{-- Kontak --}}
                            <td>
                                <div class="small">{{ $x->narahubung ?: '&mdash;' }}</div>
                                <div class="small text-muted">{{ $x->no_hp ?: '&mdash;' }}</div>
                            </td>

                            {{-- Stage + controls kecil --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    {{-- badge stage --}}
                                    <span class="badge badge-stage {{ $x->stage == \App\Models\MasterSekolah::ST_CALON ? 'secondary' : strtolower($stageOptions[$x->stage] ?? '') }}">
                                        {{ $stageOptions[$x->stage] ?? '-' }}
                                    </span>

                                    {{-- Dropdown ubah stage --}}
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-ghost round" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah Stage">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach($stageOptions as $val => $label)
                                                @if($val !== $x->stage)
                                                    <li>
                                                        <form action="{{ route('master.stage.update', $sid) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="to" value="{{ $val }}">
                                                            <button type="submit" class="dropdown-item">-> {{ $label }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </td>

                            {{-- MOU (status + tombol form) --}}
                            <td class="text-center">
                                {!! $mouAda ? '<span class="badge badge-stage mou">Ada</span>' : '<span class="badge badge-stage secondary">&mdash;</span>' !!}
                                <div class="mt-1">
                                    <a href="{{ route('master.mou.form', $sid) }}" class="btn btn-sm btn-ghost round" title="Input / Perbarui MOU">
                                        <i class="bi bi-file-earmark-plus"></i>
                                    </a>
                                </div>
                            </td>

                            {{-- TTD --}}
                            <td class="text-center">
                                {!! $x->ttd_status ? '<span class="badge badge-stage klien">OK</span>' : '<span class="badge badge-stage secondary">&mdash;</span>' !!}
                            </td>

                            {{-- Progress modul --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">{{ $progress }}%</small>
                                    <div class="progress flex-grow-1" style="height:8px">
                                        <div class="progress-bar bg-info" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Update time --}}
                            <td class="small text-muted text-nowrap">
                                {{ optional($x->updated_at)->diffForHumans() ?? '—' }}
                            </td>

                            {{-- Aksi --}}
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('master.aktivitas.index', $sid) }}" class="btn btn-sm btn-outline-secondary round" title="Aktivitas">
                                        <i class="bi bi-clock-history me-1"></i> Aktivitas
                                        @if(isset($x->aktivitas_count) && $x->aktivitas_count > 0)
                                            <span class="badge rounded-pill text-bg-info ms-1">{{ $x->aktivitas_count }}</span>
                                        @endif
                                    </a>

                                    <a href="{{ route('progress.show', $sid) }}" class="btn btn-sm btn-outline-secondary round" title="Progress">
                                        <i class="bi bi-graph-up-arrow me-1"></i> Progress
                                    </a>

                                    <a href="{{ route('master.edit',$sid) }}" class="btn btn-sm btn-outline-primary round" title="Edit">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>

                                    {{-- Detail (offcanvas) --}}
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary round"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#schoolDetail"
                                        title="Detail sekolah"
                                        data-nama="{{ $x->nama_sekolah }}"
                                        data-jenjang="{{ $x->jenjang ?? '—' }}"
                                        data-alamat="{{ $x->alamat ?? '—' }}"
                                        data-narahubung="{{ $x->narahubung ?? '—' }}"
                                        data-nohp="{{ $x->no_hp ?? '—' }}"
                                        data-sumber="{{ $x->sumber ?? '—' }}"
                                        data-siswa="{{ $x->jumlah_siswa ?? '—' }}"
                                        data-mou="{{ $mouAda ? 'Ada' : '—' }}"
                                        data-ttd="{{ $x->ttd_status ? 'OK' : '—' }}"
                                        data-stage="{{ $stageOptions[$x->stage] ?? '-' }}"
                                        data-tindak="{{ $x->tindak_lanjut ?? '—' }}"
                                        data-catatan="{{ $x->catatan ? \Illuminate\Support\Str::limit($x->catatan, 200) : '—' }}"
                                        data-created="{{ optional($x->created_at)->format('d/m/Y H:i') }}"
                                        data-updated="{{ optional($x->updated_at)->diffForHumans() }}"
                                        data-edit="{{ route('master.edit',$sid) }}"
                                        data-aktivitas="{{ route('master.aktivitas.index',$sid) }}"
                                        data-progress="{{ route('progress.show',$sid) }}"
                                        data-batch="{{ route('penggunaan-modul.batch-form', ['school' => $sid]) }}"
                                    >
                                        <i class="bi bi-info-circle me-1"></i> Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-3">
            {{ $rows->appends(request()->query())->links() }}
        </div>
    </div>

    @include('master.partials.offcanvas')
@endsection
