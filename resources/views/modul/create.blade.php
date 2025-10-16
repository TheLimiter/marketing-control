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
    border-radius: 0.5rem;
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
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('modul.store') }}">
    @csrf

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h-page">Tambah Modul</div>
            <div class="subtle">Buat modul baru yang akan tersedia untuk sekolah</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('modul.index') }}" class="btn btn-ghost round">Batal</a>
            <button type="submit" class="btn btn-primary round">
                <i class="bi bi-floppy me-1"></i> Simpan Modul
            </button>
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
            <h6 class="mb-3">Informasi Modul</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Modul <span class="text-danger">*</span></label>
                    <input class="form-control input-soft" name="nama" required placeholder="Nama lengkap modul" value="{{ old('nama') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kode</label>
                    <input class="form-control input-soft" name="kode" placeholder="mis. M-001" value="{{ old('kode') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <input class="form-control input-soft" name="kategori" placeholder="e.g., Akademik, Keuangan" value="{{ old('kategori') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Versi</label>
                    <input class="form-control input-soft" name="versi" placeholder="e.g., 1.0.0" value="{{ old('versi') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control input-soft" name="deskripsi" id="deskripsi" rows="3" placeholder="Jelaskan secara singkat tujuan dan cakupan dari modul ini">{{ old('deskripsi') }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="mb-3">Harga & Status</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Harga Default</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        {{-- Input yang terlihat oleh user --}}
                        <input class="form-control input-soft" id="harga_display" type="text" placeholder="Masukkan harga" value="{{ old('harga_default') ? number_format(old('harga_default'), 0, ',', '.') : '' }}">
                        {{-- Input tersembunyi untuk menyimpan nilai angka asli --}}
                        <input type="hidden" name="harga_default" id="harga_default" value="{{ old('harga_default') }}">
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                     <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="aktif" name="aktif" value="1" @checked(!old() || old('aktif', true))>
                        <label for="aktif" class="form-check-label">Jadikan Modul Aktif</label>
                        <div class="form-text mt-0">Modul yang tidak aktif tidak akan bisa dipilih saat membuat penggunaan baru.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
             <div class="d-flex gap-2">
                <a href="{{ route('modul.index') }}" class="btn btn-ghost round">Batal</a>
                <button type="submit" class="btn btn-primary round">Simpan Modul</button>
             </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hargaDisplay = document.getElementById('harga_display');
    const hargaDefault = document.getElementById('harga_default');

    if (hargaDisplay && hargaDefault) {
        hargaDisplay.addEventListener('input', function(e) {
            // 1. Ambil nilai, hapus semua kecuali angka
            let rawValue = e.target.value.replace(/[^0-9]/g, '');

            // 2. Simpan nilai mentah ke input hidden
            hargaDefault.value = rawValue;

            // 3. Format nilai dengan pemisah ribuan (ganti toLocaleString dengan format manual agar lebih konsisten)
            if (rawValue) {
                let formattedValue = parseInt(rawValue, 10).toLocaleString('id-ID');
                e.target.value = formattedValue;
            } else {
                e.target.value = ''; // Kosongkan jika tidak ada angka
            }
        });
    }
});
</script>
@endpush

