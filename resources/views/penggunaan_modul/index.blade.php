@extends('layouts.app')

@section('content')
    {{-- HIGHLIGHT KOLOM: lokal saja --}}
    <style>
      .cell-warning, .cell-danger { position: relative; }
      .cell-warning { background: rgba(245,158,11,.10) !important; box-shadow: inset 4px 0 0 0 #f59e0b; }
      .cell-danger  { background: rgba(239,68,68,.12) !important; box-shadow: inset 4px 0 0 0 #ef4444; }
    </style>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Penggunaan Modul</div>
            <div class="subtle">Daftar assignment modul per sekolah</div>
        </div>
        <a href="{{ route('penggunaan-modul.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field" style="min-width:210px">
                <label>Modul</label>
                <select name="modul_id" class="select-soft">
                    <option value="">Semua</option>
                    @foreach($modulOptions as $m)
                        <option value="{{ $m->id }}" {{ (string)request('modul_id')===(string)$m->id?'selected':'' }}>{{ $m->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Lisensi</label>
                <select name="lisensi" class="select-soft">
                    <option value="">Semua</option>
                    <option value="trial"    {{ request('lisensi')==='trial'?'selected':'' }}>Uji Coba</option>
                    <option value="official" {{ request('lisensi')==='official'?'selected':'' }}>Official</option>
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Status</label>
                <select name="status" class="select-soft">
                    <option value="">Semua</option>
                    @foreach(['active'=>'Active','paused'=>'Paused','ended'=>'Ended'] as $k=>$v)
                        <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Stage Sekolah</label>
                <select name="stage" class="select-soft">
                    <option value="">Semua</option>
                    @foreach($stageOptions as $k=>$v)
                        <option value="{{ $k }}" {{ (string)request('stage')===(string)$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari Sekolah</label>
                <input class="input-soft" name="q" value="{{ request('q') }}" placeholder="Nama sekolah">
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="{{ route('penggunaan-modul.index') }}" class="btn btn-ghost round">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-compact table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">No</th>
                        <th style="width:20%;">Sekolah</th>
                        <th style="width:15%;">Pengguna</th>
                        <th style="width:15%;">Modul</th>
                        <th style="width:10%;">Periode</th>
                        <th style="width:10%;">Terakhir</th>
                        <th style="width:10%;">Lisensi / Status</th>
                        <th style="width:15%;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $i => $r)
                    @php
                        $rowNo = $items->firstItem() + $i;

                        // status pill
                        $status = $r->computed_status ?? $r->status ?? '-';
                        $statusClass = $status==='ended' ? 'danger'
                                     : ($status==='paused' ? 'warning'
                                     : ($status==='done' ? 'success'
                                     : ($status==='on_progress' ? 'info'
                                     : ($status==='reopen' ? 'warning' : 'secondary'))));

                        // indikator umur (kuning = >7 hari dari mulai; merah = lewat akhir, non-ended)
                        $today = now()->startOfDay();
                        $isOverdue = $r->akhir_tanggal && $r->akhir_tanggal->lt($today) && ($r->status !== 'ended');
                        $isAging   = !$isOverdue && $r->mulai_tanggal && $r->mulai_tanggal->lte($today->copy()->subDays(7));
                        $ageCellClass = $isOverdue ? 'cell-danger' : ($isAging ? 'cell-warning' : '');
                    @endphp
                    <tr>
                        <td>{{ $rowNo }}</td>

                        <td class="fw-medium">
                            <a href="{{ route('master.edit', $r->master_sekolah_id) }}" class="text-decoration-none">
                                {{ $r->master->nama_sekolah ?? '-' }}
                            </a>
                            <div class="small text-muted">{{ $r->master->alamat ?? '' }}</div>
                        </td>

                        <td>
                            {{ $r->pengguna_nama ?: '-' }}
                            <div class="small text-muted">{{ $r->pengguna_kontak ?: '-' }}</div>
                        </td>

                        <td>{{ $r->modul->nama ?? '-' }}</td>

                        {{-- Periode (highlight cell) --}}
                        <td class="text-nowrap small text-muted {{ $ageCellClass }}">
                            {{ $r->mulai_tanggal ? \Illuminate\Support\Carbon::parse($r->mulai_tanggal)->format('d/m/Y') : '' }}
                            {{ $r->akhir_tanggal ? \Illuminate\Support\Carbon::parse($r->akhir_tanggal)->format('d/m/Y') : '' }}
                        </td>

                        {{-- Terakhir digunakan (ikut di-highlight agar konsisten) --}}
                        <td class="text-nowrap small text-muted {{ $ageCellClass }}">
                            {{ $r->last_used_at ? \Illuminate\Support\Carbon::parse($r->last_used_at)->diffForHumans() : '-' }}
                        </td>

                        <td>
                            <span class="badge rounded-pill bg-{{ $r->is_official ? 'success' : 'secondary' }}">
                                {{ $r->lisensi_label ?? ($r->is_official ? 'Official' : 'Trial') }}
                            </span>
                            <br>
                            <span class="badge rounded-pill bg-{{ $statusClass }}">{{ ucfirst($status) }}</span>
                        </td>

                        <td class="text-end text-nowrap">
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <form method="post" action="{{ route('penggunaan-modul.use',$r->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-ghost round" title="Catat penggunaan sekarang">
                                        <i class="bi bi-clock me-1"></i> Gunakan
                                    </button>
                                </form>

                                <div class="btn-group btn-group-sm">
                                    @can('update', $r)
                                        @if($r->status === 'attached' || $r->status === 'reopen')
                                            <form method="post" action="{{ route('penggunaan-modul.start',$r->id) }}">
                                                @csrf
                                                <button class="btn btn-outline-primary" title="Mulai Progress">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($r->status === 'on_progress')
                                            <form method="post" action="{{ route('penggunaan-modul.done',$r->id) }}">
                                                @csrf
                                                <button class="btn btn-outline-success" title="Tandai Selesai">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($r->status === 'done')
                                            <form method="post" action="{{ route('penggunaan-modul.reopen',$r->id) }}">
                                                @csrf
                                                <button class="btn btn-outline-warning" title="Buka Kembali">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    @can('delete', $r)
                                        <form method="post" action="{{ route('penggunaan-modul.destroy',$r->id) }}"
                                              onsubmit="return confirm('Hapus penggunaan modul ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada penggunaan modul.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $items->withQueryString()->links() }}
    </div>
@endsection
