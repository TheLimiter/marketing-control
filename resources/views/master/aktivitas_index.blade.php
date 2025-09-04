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
    $perJenis = $items->getCollection()->groupBy('jenis')->map->count();
    $toggle = request('dir', 'desc') === 'desc' ? 'asc' : 'desc' ;
    $showTrash = isset($showTrash) && $showTrash;
@endphp

@section('content')
    <style>
        /* Gaya untuk input statis agar seragam dengan input-soft */
        .input-soft-static {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--bs-body-color);
            background-color: var(--bs-body-bg);
            background-clip: padding-box;
            border: var(--bs-border-width) solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            cursor: not-allowed;
        }
    </style>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Log Aktivitas</div>
            <div class="subtle">Aktivitas • {{ $master->nama_sekolah }}</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('master.index') }}" class="btn btn-ghost round">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @if(empty($showTrash))
                <a href="{{ route('master.aktivitas.trash', $master->id) }}" class="btn btn-outline-secondary round">
                    <i class="bi bi-trash me-1"></i> Riwayat Terhapus
                </a>
                <button class="btn btn-primary round" data-bs-toggle="offcanvas" data-bs-target="#ocNew">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Aktivitas
                </button>
            @else
                <a href="{{ route('master.aktivitas.index', $master->id) }}" class="btn btn-outline-secondary round">
                    <i class="bi bi-box-arrow-left me-1"></i> Kembali ke Log
                </a>
            @endif
        </div>
    </div>

    @if(empty($showTrash))
        {{-- Filter Toolbar --}}
        <form method="get" id="filterFormSchool" class="card card-toolbar mb-4">
            <div class="toolbar">
                <div class="field" style="min-width:140px">
                    <label>Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="input-soft">
                </div>
                <div class="field" style="min-width:140px">
                    <label>Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="input-soft">
                </div>
                <div class="field" style="min-width:180px">
                    <label>Jenis</label>
                    <input type="text" name="jenis" value="{{ request('jenis') }}" class="input-soft" placeholder="Ketik jenis…">
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

                <div class="field flex-grow-1" style="min-width:260px">
                    <label>Cari</label>
                    <input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Hasil atau catatan…">
                </div>
                <div class="field" style="min-width:120px">
                    <label>Per halaman</label>
                    <select name="per" class="select-soft" onchange="this.form.submit()">
                        @foreach([15,25,50,100] as $n)
                            <option value="{{ $n }}" @selected(request('per',25)===$n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary round">
                        <i class="bi bi-filter me-1"></i> Terapkan
                    </button>
                    <a href="{{ route('master.aktivitas.index', [$master->id]) }}" class="btn btn-ghost round">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </div>

            {{-- Chip ringkasan --}}
            @if($perJenis->isNotEmpty())
                <div class="toolbar-footer d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
                    @foreach($perJenis as $k=>$n)
                        <a class="chip {{ strtolower($k) === strtolower(request('jenis')) ? 'bg-primary text-white border-primary' : '' }}"
                           href="{{ route('master.aktivitas.index', [$master->id, 'jenis'=>$k] + request()->except('page','jenis')) }}">
                            {{ Str::headline($k) }} <span class="opacity-75">({{ $n }})</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    @else
        <div class="alert alert-warning py-2 px-3 small mb-4">
            Menampilkan <strong>Riwayat Terhapus</strong>. Anda dapat memulihkan atau menghapus permanen item di sini.
        </div>
    @endif

    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:26px">
                            @if(empty($showTrash))
                                <input type="checkbox" class="chk" id="chkAll" title="Pilih semua">
                            @endif
                        </th>
                        <th style="width:120px">
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'tanggal','dir'=>$toggle]) }}" class="text-decoration-none">Tanggal</a>
                        </th>
                        <th style="width:120px">
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'jenis','dir'=>$toggle]) }}" class="text-decoration-none">Jenis</a>
                        </th>
                        <th>Hasil</th>
                        <th>Catatan</th>
                        <th style="width:160px">
                            {{-- NEW: sort by creator name --}}
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'creator_name','dir'=>$toggle]) }}" class="text-decoration-none">Oleh</a>
                        </th>
                        <th style="width:140px" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $it)
                        @php $k = strtolower($it->jenis ?? 'lainnya'); @endphp
                        <tr>
                            <td>
                                @if(empty($showTrash))
                                    <input type="checkbox" name="ids[]" value="{{ $it->id }}" class="chk rowchk" form="bulkForm">
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium">{{ optional($it->tanggal)->format('d M Y') ?: '—' }}</div>
                                <div class="text-muted small">{{ optional($it->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="badge rounded-pill text-bg-{{ $badge[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span>
                            </td>
                            <td class="fw-medium">
                                {{ $it->hasil }}
                                @php $fc = $it->files->count(); @endphp
                                @if($fc)
                                    <details class="d-inline-block align-middle">
                                        <summary class="badge rounded-pill text-bg-secondary border-0"
                                                 style="cursor:pointer; list-style:none; display:inline-block;">
                                            <i class="bi bi-paperclip me-1"></i> {{ $fc }}
                                        </summary>
                                        <ul class="list-unstyled small mb-0 mt-1">
                                            @foreach($it->files as $f)
                                                <li class="d-flex align-items-center gap-2">
                                                    <a href="{{ route('aktivitas.file.download', $f->id) }}">{{ $f->original_name }}</a>
                                                    <span class="text-muted">({{ number_format($f->size/1024,1) }} KB)</span>
                                                    @if(Str::startsWith($f->mime, 'image/'))
                                                        <button type="button" class="btn btn-sm btn-outline-secondary add-btn" onclick="previewImage(@json(route('aktivitas.file.preview', $f->id)), @json($f->original_name))">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td class="text-muted small">{!! $it->catatan ? nl2br(e(Str::limit($it->catatan,180))) : '—' !!}</td>
                            <td class="small">{{ optional($it->creator)->name ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if(!empty($showTrash))
                                    <form class="d-inline" method="post" action="{{ route('master.aktivitas.restore', [$master->id, $it->id]) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-success round" onclick="return confirm('Pulihkan aktivitas ini?')">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                        </button>
                                    </form>
                                    <form class="d-inline" method="post" action="{{ route('master.aktivitas.force', [$master->id, $it->id]) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger round" onclick="return confirm('Hapus permanen? Tindakan ini tidak bisa dibatalkan.')">
                                            <i class="bi bi-x-circle me-1"></i> Hapus
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('master.aktivitas.destroy', [$master->id, $it->id]) }}" method="post" onsubmit="return confirm('Hapus aktivitas ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger round">
                                            <i class="bi bi-trash me-1"></i> Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bulk action & pagination --}}
        @if(empty($showTrash))
            <form id="bulkForm" method="post" action="{{ route('master.aktivitas.bulk', $master->id) }}" class="d-flex flex-wrap gap-2 align-items-center p-3 border-top">
                @csrf
                <select name="action" class="select-soft" style="width:auto">
                    <option value="" selected>Aksi massal…</option>
                    <option value="delete">Hapus terpilih</option>
                    <option value="export">Export CSV terpilih</option>
                </select>
                <button class="btn btn-primary round" onclick="return confirm('Lanjutkan aksi massal?')">Jalankan</button>
                <span class="text-muted small ms-auto">Terlihat {{ $items->count() }} dari total {{ $items->total() }}</span>
            </form>
        @endif
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $items->withQueryString()->links() }}
    </div>

    {{-- Offcanvas: Tambah Aktivitas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="ocNew" aria-labelledby="ocNewLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="ocNewLabel">Tambah Aktivitas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            {{-- flash message / errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-medium mb-1">Gagal menyimpan:</div>
                    <ul class="mb-0 small">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
                </div>
            @endif

            <form method="post" action="{{ route('master.aktivitas.store', $master->id) }}" class="vstack gap-3" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label>Tanggal</label>
                    <div class="input-soft-static">{{ now()->format('d M Y H:i') }}</div>
                    <div class="form-text">Tanggal dan waktu otomatis diambil dari server saat ini.</div>
                </div>

                <div class="field">
                    <label for="jenis_new">Jenis</label>
                    <input id="jenis_new" type="text" name="jenis" class="input-soft" value="{{ old('jenis') }}" maxlength="100" placeholder="mis: kunjungan / follow_up" required>
                </div>

                <div class="field">
                    <label for="hasil">Hasil (judul singkat)</label>
                    <input id="hasil" type="text" name="hasil" class="input-soft" value="{{ old('hasil') }}" maxlength="150" required>
                </div>

                <div class="field">
                    <label for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="4" class="form-control" placeholder="Detail, next step, dsb.">{{ old('catatan') }}</textarea>
                </div>

                <div class="field">
                    <label for="files">Lampiran (opsional)</label>
                    <input id="files" type="file" name="files[]" class="form-control" multiple>
                    <div class="form-text">jpg, png, webp, pdf, doc/x, xls/x, ppt/x • maks 5MB/file</div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary round">Simpan</button>
                    <button type="button" class="btn btn-outline-secondary round" data-bs-dismiss="offcanvas">Batal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Image Preview Modal (reusable) --}}
    <div class="modal fade" id="imgPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="imgPreviewTitle">Pratinjau Gambar</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center" style="min-height:40vh">
                    <img id="imgPreviewEl" src="" alt="" class="rounded border" style="max-width:100%; max-height:80vh; object-fit:contain;">
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script>
        // Check all (hanya di halaman ini)
        const chkAll = document.getElementById('chkAll');
        if (chkAll) {
            chkAll.addEventListener('change', function() {
                document.querySelectorAll('input.rowchk').forEach(el => el.checked = chkAll.checked);
            });
        }

        function previewImage(url, title) {
            const img = document.getElementById('imgPreviewEl');
            const ttl = document.getElementById('imgPreviewTitle');
            ttl.textContent = title || 'Pratinjau Gambar';
            img.src = ''; // reset agar force reload
            const m = new bootstrap.Modal(document.getElementById('imgPreviewModal'));
            m.show();
            img.onerror = () => { img.alt = 'Gambar gagal dimuat.'; };
            img.src = url; // gunakan route preview
        }
    </script>
@endsection
