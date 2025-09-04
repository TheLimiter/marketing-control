@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $badge = [
        'modul_progress' => 'info',
        'modul_done' => 'success',
        'modul_reopen' => 'warning',
        'modul_attach' => 'secondary',
        'stage_change' => 'dark',
        'kunjungan' => 'primary',
        'meeting' => 'secondary',
        'follow_up' => 'secondary',
        'whatsapp' => 'success',
        'email' => 'secondary',
        'lainnya' => 'light',
    ];
    $toggle = request('dir', 'desc') === 'desc' ? 'asc' : 'desc';
@endphp

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Semua Aktivitas</div>
            <div class="subtle">Riwayat aktivitas dari seluruh sekolah</div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="get" id="filterForm" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="d-flex gap-2 align-items-end">
                <div class="field" style="min-width:140px">
                    <label>Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="input-soft">
                </div>
                <div class="field" style="min-width:140px">
                    <label>Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="input-soft">
                </div>
            </div>

               <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari Sekolah</label>
                <input name="school" value="{{ request('school') }}" class="input-soft" placeholder="Nama sekolah">
            </div>

            <div class="field" style="min-width:180px">
                <label>Jenis</label>
                <input type="text" name="jenis" value="{{ request('jenis') }}" class="input-soft" placeholder="Ketik jenis…">
            </div>

            <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari (hasil/catatan)</label>
                <input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Kata kunci">
            </div>

             <div class="field" style="min-width:200px">
                    <label>Oleh</label>
                    <input type="text" name="oleh" value="{{ request('oleh') }}" class="input-soft" placeholder="Nama user…" list="dl-oleh">
                    @isset($creatorOptions)
                        <datalist id="dl-oleh">
                            @foreach($creatorOptions as $n)
                                <option value="{{ $n }}"></option>
                            @endforeach
                        </datalist>
                    @endisset
                </div>

            <div class="field" style="min-width:120px">
                <label>Per halaman</label>
                <select name="per" class="select-soft" onchange="this.form.submit()">
                    @foreach([15,25,50,100] as $n)
                        <option value="{{ $n }}" @selected(request('per',25)==$n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Terapkan
                </button>
                <a href="{{ route('aktivitas.index') }}" class="btn btn-ghost round">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>
        <div class="toolbar-footer d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">Hari ini</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(7)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">7 hari</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(30)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">30 hari</a>
            <a href="{{ route('aktivitas.export', request()->all()) }}" class="btn btn-sm btn-outline-success round ms-auto">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
        </div>
    </form>

    {{-- Tabel Utama --}}
    <div class="card p-0">
        <form method="post" action="{{ route('aktivitas.bulk') }}">
            @csrf

            <div class="table-responsive">
                <table class="table table-modern table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:26px">
                                <input type="checkbox" id="chkAll" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)">
                            </th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'tanggal','dir'=>$toggle]) }}" class="text-decoration-none">Tanggal</a>
                            </th>
                            <th>Sekolah</th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'jenis','dir'=>$toggle]) }}" class="text-decoration-none">Jenis</a>
                            </th>
                            <th>Hasil</th>
                            <th>Catatan</th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'creator_name','dir'=>$toggle]) }}" class="text-decoration-none">Oleh</a>
                            </th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $it)
                            @php $k = strtolower($it->jenis ?? 'lainnya'); @endphp
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $it->id }}" class="rowchk"></td>
                                <td>
                                    <div class="fw-medium">{{ optional($it->tanggal)->format('d M Y') ?: '—' }}</div>
                                    <div class="text-muted small">{{ optional($it->created_at)->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('master.aktivitas.index', optional($it->master)->id) }}" class="text-decoration-none">
                                        {{ optional($it->master)->nama_sekolah ?? '—' }}
                                    </a>
                                </td>
                                <td><span class="badge rounded-pill text-bg-{{ $badge[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span></td>
                                <td class="fw-medium">
                                    {{ $it->hasil }}
                                    @php $fc = $it->files->count(); @endphp
                                    @if($fc)
                                        <details class="d-inline-block align-middle">
                                            <summary class="badge rounded-pill text-bg-secondary border-0" style="cursor:pointer; list-style:none; display:inline-block;">
                                                <i class="bi bi-paperclip me-1"></i> {{ $fc }}
                                            </summary>
                                            <ul class="list-unstyled small mb-0 mt-1">
                                                @foreach($it->files as $f)
                                                    <li class="d-flex align-items-center gap-2">
                                                        <a href="{{ route('aktivitas.file.download', $f->id) }}">{{ $f->original_name }}</a>
                                                        <span class="text-muted">({{ number_format($f->size/1024,1) }} KB)</span>
                                                        @if(Str::startsWith($f->mime, 'image/'))
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewImage(@json(route('aktivitas.file.preview', $f->id)), @json($f->original_name))">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td class="text-muted small">{!! $it->catatan ? nl2br(e(Str::limit($it->catatan,200))) : '—' !!}</td>
                                <td class="small">{{ optional($it->creator)->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if(optional($it->master)->id)
                                        <a class="btn btn-sm btn-outline-secondary round" href="{{ route('master.aktivitas.index', $it->master->id) }}">
                                            Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- toolbar bulk --}}
            <div class="d-flex flex-wrap gap-2 align-items-center p-3 border-top">
                <select name="action" class="select-soft" style="width:auto">
                    <option value="" selected>Aksi massal…</option>
                    <option value="delete">Hapus terpilih</option>
                    <option value="export">Export CSV terpilih</option>
                </select>
                <button class="btn btn-primary round" onclick="return confirm('Lanjutkan aksi massal?')">Jalankan</button>
                <span class="text-muted small ms-auto">Terlihat {{ $items->count() }} dari total {{ $items->total() }}</span>
            </div>

        </form>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endsection
