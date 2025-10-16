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
<form action="{{ route('master.update', $master->id) }}" method="post">
    @csrf
    @method('PUT')

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h-page">Edit Sekolah</div>
            <div class="subtle">{{ $master->nama_sekolah }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('master.index') }}" class="btn btn-ghost round">Batal</a>
            <button type="submit" class="btn btn-primary round">
                <i class="bi bi-floppy me-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>

    {{-- Error summary --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Content --}}
    <div class="card">
        <div class="card-body p-4 form--soft">

            <h6 class="mb-3">Identitas & Kontak</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                    <input type="text" name="nama_sekolah" class="form-control input-soft" autofocus placeholder="Masukkan nama sekolah" value="{{ old('nama_sekolah', $master->nama_sekolah ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenjang</label>
                    <select name="jenjang" class="form-select select-soft">
                        <option value="">Pilih Jenjang</option>
                        <option value="SD" @selected(old('jenjang', $master->jenjang ?? '') == 'SD')>SD/MI</option>
                        <option value="SMP" @selected(old('jenjang', $master->jenjang ?? '') == 'SMP')>SMP/MTs</option>
                        <option value="SMA" @selected(old('jenjang', $master->jenjang ?? '') == 'SMA')>SMA/MA/SMK</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" rows="2" class="form-control input-soft" placeholder="Nama jalan, kecamatan, kota/kabupaten">{{ old('alamat', $master->alamat ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Narahubung</label>
                    <input type="text" name="narahubung" class="form-control input-soft" placeholder="Nama, Jabatan" value="{{ old('narahubung', $master->narahubung ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control input-soft" inputmode="tel" placeholder="08xxxxxxx" value="{{ old('no_hp', $master->no_hp ?? '') }}">
                </div>
                 <div class="col-md-6">
                    <label class="form-label">Jumlah Siswa</label>
                    <input type="number" name="jumlah_siswa" class="form-control input-soft" min="0" inputmode="numeric" placeholder="Contoh: 650" value="{{ old('jumlah_siswa', $master->jumlah_siswa ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sumber</label>
                    <input type="text" name="sumber" class="form-control input-soft" placeholder="Kerjasama / event / kontak / dsb." value="{{ old('sumber', $master->sumber ?? '') }}">
                </div>
            </div>

            <hr class="my-4">

            <h6 class="mb-3">Catatan & Tindak Lanjut</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Tindak Lanjut</label>
                    <textarea name="tindak_lanjut" rows="3" class="form-control input-soft" placeholder="Rencana follow-up berikutnya">{{ old('tindak_lanjut', $master->tindak_lanjut ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="5" class="form-control input-soft" placeholder="Ringkasan call/visit, komitmen, atau hal khusus">{{ old('catatan', $master->catatan ?? '') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
             <div class="text-muted small">
                Terakhir diperbarui: {{ optional($master->updated_at)->diffForHumans() }}
             </div>
             <div class="d-flex gap-2">
                <a href="{{ route('master.index') }}" class="btn btn-ghost round">Batal</a>
                <button type="submit" class="btn btn-primary round">Simpan Perubahan</button>
             </div>
        </div>
    </div>
</form>
@endsection

