@extends('layouts.app')

@section('content')
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <div class="h-page">Detail Modul</div>
      <div class="subtle">Informasi & penggunaan modul</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('modul.edit', $modul->id) }}" class="btn btn-outline-primary round">
        <i class="bi bi-pencil me-1"></i> Edit
      </a>
      <a href="{{ route('modul.index') }}" class="btn btn-ghost round">Kembali</a>
    </div>
  </div>

  {{-- Ringkasan Modul --}}
  <div class="row g-3 mb-3">
    {{-- Kiri: Info Modul --}}
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Info Modul</div>

          <div class="row g-2">
            <div class="col-md-6">
              <div class="text-muted small">Kode</div>
              <div class="fw-semibold">{{ $modul->kode ?? '-' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Nama</div>
              <div class="fw-semibold">{{ $modul->nama ?? '-' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Kategori</div>
              <div class="fw-semibold">{{ $modul->kategori ?? '-' }}</div>
            </div>

            <div class="col-md-3">
              <div class="text-muted small">Versi</div>
              <div class="fw-semibold">{{ $modul->versi ?? '-' }}</div>
            </div>

            <div class="col-md-3">
              <div class="text-muted small">Status</div>
              <span class="badge {{ ($modul->aktif ?? $modul->active ?? false) ? 'badge-stage klien' : 'badge-stage secondary' }}">
                {{ ($modul->aktif ?? $modul->active ?? false) ? 'Aktif' : 'Tidak' }}
              </span>
            </div>

            {{-- Harga (default) --}}
            @php
              $harga = $modul->harga_default
                       ?? $modul->harga_per_siswa
                       ?? $modul->harga
                       ?? $modul->biaya
                       ?? $modul->price
                       ?? $modul->tarif
                       ?? null;
            @endphp
            <div class="col-md-6">
              <div class="text-muted small">Harga (default)</div>
              <div class="fw-semibold">
                {{ is_null($harga) ? '—' : 'Rp '.number_format((float)$harga, 0, ',', '.') }}
              </div>
            </div>
          </div> {{-- end .row g-2 --}}

          @php
            // fallback ke beberapa nama kolom yang mungkin kamu pakai
            $desc = $modul->deskripsi
                  ?? $modul->deskripsi_modul
                  ?? $modul->description
                  ?? $modul->keterangan
                  ?? null;
          @endphp

          <hr class="my-3">

          <div class="d-flex align-items-center mb-2">
            <i class="bi bi-info-circle me-2"></i>
            <div class="h6 mb-0">Deskripsi Modul</div>
          </div>

          @if($desc)
            {{-- tampilkan aman + pertahankan baris-baru --}}
            <div class="text-muted" style="white-space:pre-line">
              {{ $desc }}
            </div>
          @else
            <div class="alert alert-info small mb-0">
              Belum ada deskripsi modul. <a href="{{ route('modul.edit', $modul->id) }}">Tambahkan deskripsi</a>
              agar pengguna awam paham fungsi dan manfaat modul ini.
            </div>
          @endif
        </div> {{-- end .card-body --}}
      </div> {{-- end .card --}}
    </div> {{-- end .col-lg-8 --}}

    {{-- Kanan: Ringkasan Penggunaan --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Ringkasan Penggunaan</div>
          <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between">
              <span class="text-muted">Total Sekolah</span>
              <span class="fw-semibold">{{ $total }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Official</span>
              <span class="fw-semibold">{{ $official }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Trial</span>
              <span class="fw-semibold">{{ $trial }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Aktif</span>
              <span class="fw-semibold">{{ $aktif }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Terakhir diupdate</span>
              <span class="fw-semibold">{{ optional($terakhir)->diffForHumans() ?? '-' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div> {{-- end .col-lg-4 --}}
  </div> {{-- end .row --}}

  {{-- Toolbar filter penggunaan --}}
  <form method="GET" class="card card-toolbar mb-3">
    <div class="toolbar">
      <input type="hidden" name="_" value="1">
      <div class="field" style="min-width:260px">
        <label>Cari sekolah</label>
        <input type="text" name="q" class="input-soft" value="{{ request('q') }}" placeholder="Ketik nama sekolah">
      </div>
      <div class="field" style="min-width:180px">
        <label>Lisensi</label>
        <select name="lisensi" class="input-soft">
          <option value="">— Semua —</option>
          <option value="official" {{ request('lisensi')==='official'?'selected':'' }}>Official</option>
          <option value="trial" {{ request('lisensi')==='trial'?'selected':'' }}>Trial</option>
        </select>
      </div>
      <div class="field" style="min-width:180px">
        <label>Status</label>
        <select name="status" class="input-soft">
          <option value="">— Semua —</option>
          @foreach(['active','aktif','paused','progress','ended','selesai'] as $st)
            <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
      </div>
      <div class="ms-auto d-flex align-items-end">
        <button class="btn btn-primary round">
          <i class="bi bi-funnel me-1"></i> Terapkan
        </button>
      </div>
    </div>
  </form>

  {{-- Tabel penggunaan per sekolah --}}
  <div class="card p-0">
    <div class="table-responsive">
      <table class="table table-modern table-compact table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:36%">Sekolah</th>
            <th style="width:12%">Lisensi</th>
            <th style="width:14%">Mulai</th>
            <th style="width:14%">Akhir</th>
            <th style="width:14%">Status</th>
            <th class="text-end" style="width:10%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($usage as $u)
            <tr>
              <td>{{ $u->master->nama_sekolah ?? '-' }}</td>
              <td>
                <span class="badge {{ $u->is_official ? 'badge-stage klien' : 'badge-stage secondary' }}">
                  {{ $u->is_official ? 'Official' : 'Trial' }}
                </span>
              </td>
              <td>{{ $u->mulai_tanggal ?? '-' }}</td>
              <td>{{ $u->akhir_tanggal ?? '-' }}</td>
              <td>{{ ucfirst($u->status ?? '-') }}</td>
              <td class="text-end">
                @if(isset($u->master->id))
                  <a href="{{ route('master.aktivitas.index', $u->master->id) }}" class="btn btn-sm btn-outline-primary round">
                    Detail
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada penggunaan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3 p-3 text-center">
    {{ $usage->links() }}
  </div>
@endsection
