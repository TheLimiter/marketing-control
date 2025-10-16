
@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    /* warna badge per jenis (kelas akan dipetakan ke badge-stage) */
    $badge = [
        'modul_progress' => 'info',
        'modul_done'     => 'success',
        'modul_reopen'   => 'warning',
        'modul_attach'   => 'secondary',
        'stage_change'   => 'dark',
        'kunjungan'      => 'primary',
        'meeting'        => 'secondary',
        'follow_up'      => 'secondary',
        'whatsapp'       => 'success',
        'email'          => 'secondary',
        'lainnya'        => 'light',
        'billing_payment'=> 'success',
        'billing_create' => 'secondary',
    ];

    $perJenis   = $items->getCollection()->groupBy('jenis')->map->count();
    $toggle     = request('dir', 'desc') === 'desc' ? 'asc' : 'desc';
    $showTrash  = isset($showTrash) && $showTrash;
@endphp

@section('content')
<style>
:root{
  --neutralblack-1:#131315;
  --neutralgrey-6:#878AA6;
  --neutralgrey-14:#EFF0F9;
  --neutralgrey-13:#E5E7F2;
  --neutralgrey-9:#C3C2D7;
  --neutralwhite:#fff;
}

/* ====== soft inputs (static) ====== */
.input-soft-static{
  display:block;width:100%;padding:.6rem .95rem;border-radius:999px;
  border:1px solid var(--neutralgrey-13);background:#fff;color:var(--bs-body-color);
  cursor:not-allowed
}

/* ====== table: sticky header + internal scroll ====== */
.table-wrap{
  max-height:60vh;             /* area scroll card */
  overflow:auto;
  border-top:1px solid var(--neutralgrey-13)
}
.table thead th{ position:sticky; top:0; z-index:2; }

/* kecilkan checkbox */
.chk{ width:18px;height:18px; cursor:pointer; }

/* badge file counter */
.badge-clip{
  display:inline-flex; align-items:center; gap:.35rem;
  border-radius:999px; padding:.15rem .45rem; font-weight:600; font-size:.75rem;
  background:#e9f2ff; color:#1d4ed8; border:1px solid #cfe0ff;
}

/* details dropdown feel */
.attachments details{ display:inline-block; }
.attachments summary{ list-style:none; cursor:pointer; }
.attachments summary::-webkit-details-marker{ display:none; }
.attachments ul{
  background:#fff; border:1px solid var(--neutralgrey-13); border-radius:12px; padding:.5rem .75rem;
  box-shadow:0 10px 24px rgba(17,24,39,.08); min-width:220px
}

/* empty state */
.table-empty{padding:2.25rem 1rem;color:#6b7280}

/* toolbar footer chips */
.toolbar-footer .chip{ text-decoration:none; }

/* tombol bulat kecil */
.btn-chip{
  --bs-btn-padding-y:.35rem;
  --bs-btn-padding-x:.5rem;
  --bs-btn-font-size:.85rem;
  border-radius:9999px;
}

/* badge-stage mengikuti gaya master blade */
.badge-stage{ border-radius:9999px; font-size:12px; padding:.3rem .55rem; border:1px solid transparent; font-weight:600; display:inline-block; }
.badge-stage.calon{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
.badge-stage.shb{ background:#ecfeff; color:#155e75; border-color:#a5f3fc; }
.badge-stage.slth{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.mou{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.tlmou{ background:#fefce8; color:#854d0e; border-color:#fde68a; }
.badge-stage.tolak{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.badge-stage.info{ background:#ecfeff; color:#0f766e; border-color:#a7f3d0; }
.badge-stage.success{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.warning{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
.badge-stage.secondary{ background:#f3f4f6; color:#111827; border-color:#e5e7eb; }
.badge-stage.dark{ background:#eef2ff; color:#0f172a; border-color:#d1d5db; }
.badge-stage.primary{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.light{ background:#ffffff; color:#374151; border-color:#f3f4f6; }

</style>

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <div class="h-page">Log Aktivitas</div>
    <div class="subtle">Aktivitas {{ $master->nama_sekolah }}</div>
  </div>

  <div class="d-flex gap-2">
    <a href="{{ route('master.index') }}" class="btn btn-ghost round">
      <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>

    @if(!$showTrash)
      <a href="{{ route('master.aktivitas.trash', $master->id) }}" class="btn btn-outline-danger round">
        <i class="bi bi-trash3 me-1"></i> Riwayat terhapus
      </a>
    @else
      <a href="{{ route('master.aktivitas.index', $master->id) }}" class="btn btn-ghost round">
        <i class="bi bi-arrow-left me-1"></i> Kembali
      </a>
    @endif

    <button class="btn btn-primary round" data-bs-toggle="offcanvas" data-bs-target="#ocNew">
      <i class="bi bi-plus-lg me-1"></i> Tambah Aktivitas
    </button>
  </div>
</div>

@if(empty($showTrash))
  {{-- Filter Toolbar --}}
  <form method="get" id="filterFormSchool" class="card card-toolbar mb-3">
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

      <div class="field" style="min-width:180px">
        <label>Jenis</label>
        <input type="text" name="jenis" value="{{ request('jenis') }}" class="input-soft" placeholder="Ketik jenis">
      </div>

      <div class="field" style="min-width:200px">
        <label>Oleh</label>
        <input type="text" name="oleh" value="{{ request('oleh') }}" class="input-soft" placeholder="Nama user" list="dl-oleh">
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
        <input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Hasil atau catatan">
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
  <div class="alert alert-warning py-2 px-3 small mb-3">
    Menampilkan <strong>Riwayat Terhapus</strong>. Anda dapat memulihkan atau menghapus permanen item di sini.
  </div>
@endif

<div class="card p-0">
  <div class="table-wrap table-responsive">
    <table class="table table-modern table-sm table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:26px">
            @if(empty($showTrash))
              <input type="checkbox" class="chk" id="chkAll" title="Pilih semua">
            @endif
          </th>
          <th style="width:140px">
            <a href="{{ request()->fullUrlWithQuery(['sort'=>'tanggal','dir'=>$toggle]) }}" class="text-decoration-none">Tanggal</a>
          </th>
          <th style="width:140px">
            <a href="{{ request()->fullUrlWithQuery(['sort'=>'jenis','dir'=>$toggle]) }}" class="text-decoration-none">Jenis</a>
          </th>
          <th>Hasil</th>
          <th>Catatan</th>
          <th style="width:180px">
            <a href="{{ request()->fullUrlWithQuery(['sort'=>'creator_name','dir'=>$toggle]) }}" class="text-decoration-none">Oleh</a>
          </th>
          <th style="width:160px" class="text-end">Aksi</th>
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

            <td class="small">
              <div class="fw-medium">{{ optional($it->tanggal)->format('d M Y') ?: '-' }}</div>
              <div class="text-muted">{{ optional($it->created_at)->diffForHumans() }}</div>
              @if(($showTrash ?? false) && $it->deleted_at)
                <div class="text-danger">Deleted {{ $it->deleted_at->diffForHumans() }}</div>
              @endif
            </td>

            <td>
              {{-- gunakan badge-stage agar warna konsisten dengan master blade --}}
              <span class="badge-stage {{ $badge[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span>
            </td>

            <td class="fw-medium">
              {{ $it->hasil }}

              @php
                $fc = ($it->files->count() ?? 0) + ($it->paymentFiles->count() ?? 0);
              @endphp

              @if($fc)
                <span class="attachments ms-2">
                  <details>
                    <summary class="badge-clip">
                      <i class="bi bi-paperclip"></i> {{ $fc }}
                    </summary>
                    <ul class="list-unstyled small mb-0 mt-2">
                      {{-- Lampiran aktivitas biasa --}}
                      @foreach($it->files as $f)
                        <li class="d-flex align-items-center gap-2 mb-1">
                          <a href="{{ route('aktivitas.file.download', $f->id) }}">{{ $f->original_name }}</a>
                          <span class="text-muted">({{ number_format(($f->size ?? 0)/1024,1) }} KB)</span>
                          @if(Str::startsWith((string) $f->mime, 'image/'))
                            <button type="button" class="btn btn-sm btn-ghost round add-btn"
                              onclick="previewImage(@json(route('aktivitas.file.preview', $f->id)), @json($f->original_name))" title="Pratinjau">
                              <i class="bi bi-eye"></i>
                            </button>
                          @endif
                        </li>
                      @endforeach

                      {{-- Bukti pembayaran --}}
                      @foreach($it->paymentFiles as $bf)
                        <li class="d-flex align-items-center gap-2 mb-1">
                          <a href="{{ asset('storage/'.$bf->path) }}" target="_blank">{{ $bf->original_name }}</a>
                          <span class="text-muted">({{ number_format(($bf->size ?? 0)/1024,1) }} KB)</span>
                          @if(Str::startsWith((string) $bf->mime, 'image/'))
                            <a class="btn btn-sm btn-ghost round" target="_blank" href="{{ asset('storage/'.$bf->path) }}" title="Buka">
                              <i class="bi bi-eye"></i>
                            </a>
                          @endif
                        </li>
                      @endforeach
                    </ul>
                  </details>
                </span>
              @endif
            </td>

            <td class="text-muted small">{!! $it->catatan ? nl2br(e(Str::limit($it->catatan,180))) : '-' !!}</td>
            <td class="small">{{ optional($it->creator)->name ?? '-' }}</td>

            <td class="text-end text-nowrap">
              @if($showTrash)
                <form method="post" action="{{ route('master.aktivitas.restore', [$master->id, $it->id]) }}" class="d-inline">
                  @csrf @method('PATCH')
                  <button class="btn btn-sm btn-success round">
                    <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                  </button>
                </form>

                @if(auth()->user()?->hasRole('admin'))
                  <form method="post"
                        action="{{ route('master.aktivitas.force', [$master->id, $it->id]) }}"
                        class="d-inline"
                        onsubmit="return confirm('Hapus permanen item ini? Tindakan tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger round">
                      <i class="bi bi-x-circle"></i> Hapus permanen
                    </button>
                  </form>
                @endif
              @else
                <form action="{{ route('master.aktivitas.destroy', [$master->id, $it->id]) }}" method="post" onsubmit="return confirm('Hapus aktivitas ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger round btn-chip">
                    <i class="bi bi-trash me-1"></i> Hapus
                  </button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center table-empty">Belum ada aktivitas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Bulk action & pagination --}}
  @if(empty($showTrash))
    <form id="bulkForm" method="post" action="{{ route('master.aktivitas.bulk', $master->id) }}" class="d-flex flex-wrap gap-2 align-items-center p-3 border-top">
      @csrf
      <select name="action" class="select-soft" style="width:auto">
        <option value="" selected>Aksi massal</option>
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
        <div class="form-text">jpg, png, webp, pdf, doc/x, xls/x, ppt/x maks 5MB/file</div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary round">Simpan</button>
        <button type="button" class="btn btn-outline-secondary round" data-bs-dismiss="offcanvas">Batal</button>
      </div>
    </form>
  </div>
</div>

{{-- Image Preview Modal --}}
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
  // Check all
  const chkAll = document.getElementById('chkAll');
  if (chkAll) {
    chkAll.addEventListener('change', function(){
      document.querySelectorAll('input.rowchk').forEach(el => el.checked = chkAll.checked);
    });
  }

  // Preview gambar
  function previewImage(url, title){
    const img = document.getElementById('imgPreviewEl');
    const ttl = document.getElementById('imgPreviewTitle');
    ttl.textContent = title || 'Pratinjau Gambar';
    img.src = '';
    const m = new bootstrap.Modal(document.getElementById('imgPreviewModal'));
    m.show();
    img.onerror = () => { img.alt = 'Gambar gagal dimuat.'; };
    img.src = url;
  }
</script>
@endsection
