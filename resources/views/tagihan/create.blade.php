@extends('layouts.app')

@push('styles')
<style>
/* Style tambahan untuk form--soft dan input-soft jika belum ada di app.css */
.form--soft .form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    margin-bottom: 0.5rem;
}
.form--soft .input-soft,
.form--soft .select-soft {
    background-color: #f9fafb;
    border-color: #e5e7eb;
    border-radius: 0.375rem; /* DIUBAH: Mengurangi lengkungan sudut */
    height: 42px;
}
.form--soft .input-soft:focus,
.form--soft .select-soft:focus {
    background-color: #fff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
}
.form--soft .form-text {
    font-size: 0.8rem;
}
.card-footer {
    background-color: #f9fafb;
}

/* FIX: Style untuk input-group addon agar serasi */
.form--soft .input-group .input-group-text {
    background-color: #f9fafb;
    border-color: #e5e7eb;
    border-right: 0;
    /* DIUBAH: Menyesuaikan lengkungan sudut */
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}
.form--soft .input-group .form-control {
    /* DIUBAH: Menyesuaikan lengkungan sudut input di dalam grup */
    border-radius: 0 0.375rem 0.375rem 0;
}
.form--soft .input-group .form-control:focus {
    z-index: 3;
}
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('tagihan.store') }}">
    @csrf

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h-page">Buat Tagihan</div>
            <div class="subtle">Isi detail tagihan untuk sekolah</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-ghost round">Batal</a>
            <button type="submit" class="btn btn-primary round"><i class="bi bi-floppy me-1"></i> Simpan Tagihan</button>
        </div>
    </div>

    {{-- Error summary --}}
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Form Content --}}
    <div class="card">
        <div class="card-body p-4 form--soft">
            <h6 class="mb-3">Informasi Utama</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                    <select name="master_sekolah_id" class="form-select select-soft" required>
                        <option value="">Pilih sekolah</option>
                        @php $selected = old('master_sekolah_id', $prefillId ?? null); @endphp
                        @foreach($sekolah as $s)
                            <option value="{{ $s->id }}" {{ (string)$selected===(string)$s->id ? 'selected' : '' }}>
                                {{ $s->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Tagihan</label>
                    <input name="nomor" class="form-control input-soft" value="{{ old('nomor') }}" placeholder="Kosongkan untuk auto">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Tagihan</label>
                    <input type="date" name="tanggal_tagihan" class="form-control input-soft" value="{{ old('tanggal_tagihan', now()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="date" name="jatuh_tempo" class="form-control input-soft" value="{{ old('jatuh_tempo') }}" required>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="mb-3">Detail Biaya</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Total <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input class="form-control input-soft" id="total_display" type="text" placeholder="Masukkan total biaya" required value="{{ old('total') ? number_format(old('total'), 0, ',', '.') : '' }}">
                        <input type="hidden" name="total" id="total_hidden" value="{{ old('total') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Terbayar (opsional)</label>
                     <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input class="form-control input-soft" id="terbayar_display" type="text" placeholder="Masukkan jumlah terbayar" value="{{ old('terbayar') ? number_format(old('terbayar'), 0, ',', '.') : '' }}">
                        <input type="hidden" name="terbayar" id="terbayar_hidden" value="{{ old('terbayar') }}">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="mb-3">Metadata & Catatan</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control input-soft" rows="2">{{ old('catatan') }}</textarea>
                </div>
                 <div class="col-12">
                    <label class="form-label d-flex align-items-center">Modul yang dicakup <span class="small text-muted ms-2">(opsional)</span></label>
                    @php
                        $oldMods = collect(old('modul_ids', []))->map('intval')->all();
                        $pre     = isset($assigned) ? $assigned->all() : [];
                    @endphp
                    <div class="border rounded p-3" style="max-height:260px; overflow:auto;">
                        @forelse($allModul as $m)
                            @php
                                $checked = !empty($oldMods) ? in_array($m->id, $oldMods) : in_array($m->id, $pre);
                            @endphp
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="modul_ids[]" value="{{ $m->id }}" {{ $checked ? 'checked' : '' }}>
                                <label class="form-check-label">{{ $m->nama }}</label>
                                <input type="text" name="pivot_ket[{{ $m->id }}]" class="form-control form-control-sm mt-1 input-soft" placeholder="Keterangan (opsional)" value="{{ old('pivot_ket.'.$m->id) }}">
                            </div>
                        @empty
                            <div class="small text-muted">Belum ada modul aktif.</div>
                        @endforelse
                    </div>
                </div>
            </div>
             <hr class="my-4">

            <h6 class="mb-3">Opsi Tambahan</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status Awal</label>
                    <select name="status" class="form-select select-soft">
                        @foreach(['draft','sebagian','lunas'] as $st)
                            <option value="{{ $st }}" @selected(old('status','draft')===$st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="buat_lagi" name="buat_lagi" value="1" @checked(old('buat_lagi', request('buat_lagi')))>
                        <label class="form-check-label" for="buat_lagi">Buat tagihan lagi untuk sekolah ini setelah disimpan</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
             <div class="d-flex gap-2">
                <a href="{{ route('tagihan.index') }}" class="btn btn-ghost round">Batal</a>
                <button type="submit" class="btn btn-primary round">Simpan Tagihan</button>
             </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    function setupCurrencyInput(displayId, hiddenId) {
        const displayInput = document.getElementById(displayId);
        const hiddenInput = document.getElementById(hiddenId);

        if (!displayInput || !hiddenInput) return;

        // Saat pertama kali fokus, jika nilainya 0, kosongkan
        displayInput.addEventListener('focus', function(e) {
            if (parseInt(hiddenInput.value, 10) === 0) {
                e.target.value = '';
                hiddenInput.value = '';
            }
        });

        displayInput.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/[^0-9]/g, '');
            hiddenInput.value = rawValue;

            if (rawValue) {
                let formattedValue = parseInt(rawValue, 10).toLocaleString('id-ID');
                e.target.value = formattedValue;
            } else {
                e.target.value = '';
            }
        });
    }

    setupCurrencyInput('total_display', 'total_hidden');
    setupCurrencyInput('terbayar_display', 'terbayar_hidden');
});
</script>
@endpush

