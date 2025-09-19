@extends('layouts.app')

@php
  $hasFile = !empty($doc?->mou_path);
@endphp

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="text-muted small">Dokumen</div>
      <h5 class="mb-1">MOU & TTD {{ $master->nama_sekolah ?? $master->nama }}</h5>
      @if($hasFile)
        <div class="small">
          File saat ini:
          <a target="_blank" href="{{ asset('storage/'.$doc->mou_path) }}">Lihat</a>
        </div>
      @endif
    </div>
    <a href="{{ route('master.index') }}" class="btn btn-ghost btn-sm round">Kembali</a>
  </div>

  {{-- Flash --}}
  @if(session('ok'))   <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if($errors->any())  <div class="alert alert-danger"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif

  <form action="{{ route('master.mou.save', $master->id) }}" method="post" enctype="multipart/form-data" id="mouForm">
    @csrf

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card elev-2 p-3 h-100">
          <div class="h6 mb-3">Dokumen MOU</div>

          <div class="mb-3">
            <label class="form-label">Upload MOU (PDF/JPG/PNG)</label>
            <input type="file" name="mou" class="form-control input-soft"
                   accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text">Maks 5MB. Unggah ulang untuk mengganti file.</div>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="ttd_status" name="ttd_status" value="1"
                   {{ ($doc && $doc->ttd_status) ? 'checked' : '' }}>
            <label class="form-check-label" for="ttd_status">Sudah ditandatangani</label>
          </div>

          <div class="mb-1">
            <label class="form-label">Catatan</label>
            <textarea class="form-control input-soft" name="catatan" rows="3"
                      placeholder="Keterangan tambahan (opsional)">{{ old('catatan', $doc->mou_catatan ?? $doc->catatan ?? '') }}</textarea>
          </div>

        </div>
      </div>

      <div class="col-lg-5">
        <div class="card elev-2 p-3 h-100">
          <div class="h6 mb-3">Setelah Disimpan</div>

          <div class="mb-2">
            <label class="form-label d-block">Apakah Anda ingin langsung mengatur tagihan?</label>

            <div class="d-flex flex-column gap-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="next_action" id="na_stay" value="stay" checked>
                <label class="form-check-label" for="na_stay">Tidak, kembali ke daftar sekolah</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="next_action" id="na_billing" value="billing">
                <label class="form-check-label" for="na_billing">Ya, lanjut ke buat tagihan</label>
              </div>
            </div>

            <div class="form-text mt-1">
              Pilih "Ya" untuk dialihkan ke form <em>Buat Tagihan</em> klien ini setelah MOU disimpan.
            </div>
          </div>

          @if($hasFile)
            <hr>
            <div class="small">
              <div class="text-muted mb-1">Pratinjau cepat</div>
              <a target="_blank" class="btn btn-sm btn-outline-secondary round"
                 href="{{ asset('storage/'.$doc->mou_path) }}">
                <i class="bi bi-box-arrow-up-right me-1"></i> Buka File
              </a>
            </div>
          @endif

        </div>
      </div>
    </div>

    {{-- Sticky action bar --}}
    <div class="position-sticky bottom-0 mt-3" style="z-index:10;">
      <div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="text-muted small">
          @if(isset($doc?->updated_at))
            Terakhir diperbarui: {{ optional($doc->updated_at)->diffForHumans() }}
          @endif
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('master.index') }}" class="btn btn-outline-secondary">Batal</a>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </div>
  </form>
@endsection
