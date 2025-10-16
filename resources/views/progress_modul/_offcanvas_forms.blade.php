{{-- resources/views/progress_modul/_offcanvas_forms.blade.php --}}

{{-- Offcanvas: Tambah Aktivitas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="ocNewActivity" aria-labelledby="ocNewActivityLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title h-page" id="ocNewActivityLabel">Tambah Aktivitas Modul</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Container untuk pesan error --}}
        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <h6 class="alert-heading">Gagal Menyimpan</h6>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('progress.aktivitas.store', $master->id) }}" class="vstack gap-3" enctype="multipart/form-data">
            @csrf

            {{-- Field: Tanggal (Info) --}}
            <div class="field">
                <label class="form-label eyebrow">Tanggal Aktivitas</label>
                <div class="form-control-plaintext fw-semibold ps-1">{{ now()->format('d M Y, H:i') }}</div>
                <div class="form-text ps-1">Dicatat otomatis berdasarkan waktu server.</div>
            </div>

            <hr class="hr-soft">

            {{-- Field: Jenis Aktivitas --}}
            <div class="field">
                <label for="oc_jenis" class="form-label">Jenis</label>
                <input id="oc_jenis" type="text" name="jenis" class="form-control input-soft" value="{{ old('jenis', 'modul_progress') }}" required list="jenis_options">
                <datalist id="jenis_options">
                    <option value="modul_progress">
                    <option value="modul_done">
                    <option value="stage_change">
                </datalist>
            </div>

            {{-- Field: Hasil/Judul --}}
            <div class="field">
                <label for="oc_hasil" class="form-label">Hasil (Judul Singkat)</label>
                <input id="oc_hasil" type="text" name="hasil" class="form-control input-soft" value="{{ old('hasil') }}" placeholder="Contoh: Menyelesaikan Bab 1" required>
            </div>

            {{-- Field: Catatan --}}
            <div class="field">
                <label for="oc_catatan" class="form-label">Catatan</label>
                <textarea id="oc_catatan" name="catatan" rows="4" class="form-control">{{ old('catatan') }}</textarea>
            </div>

            {{-- Field: Terkait Modul (Jika ada) --}}
            @if(($items ?? collect())->isNotEmpty())
            <div class="field">
                <label for="oc_modul_id" class="form-label">Terkait Modul <span class="text-muted">(Opsional)</span></label>
                <select id="oc_modul_id" name="modul_id" class="form-select select-soft">
                    <option value="">— Tidak spesifik —</option>
                    @foreach($items as $it)
                        <option value="{{ $it->modul_id }}">{{ $it->modul->nama ?? ('Modul #'.$it->modul_id) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Field: Lampiran --}}
            <div class="field">
                <label for="oc_files" class="form-label">Lampiran <span class="text-muted">(Opsional)</span></label>
                <input id="oc_files" type="file" name="files[]" class="form-control" multiple>
                <div class="form-text">Bisa lebih dari satu file. Maks 5MB per file.</div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                <button type="submit" class="btn btn-primary round w-100"><i class="bi bi-send me-1"></i> Simpan Aktivitas</button>
                <button type="button" class="btn btn-ghost round" data-bs-dismiss="offcanvas">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas: Bulk Stage --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasStage" aria-labelledby="offcanvasStageLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title h-page" id="offcanvasStageLabel">Kelola Stage Modul</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <form action="{{ route('progress.stage.bulk', [$master->id]) }}" method="post" class="vstack gap-3">
            @csrf
            @method('PATCH')

            <div class="alert alert-info small py-2">
                Ubah stage untuk beberapa modul sekaligus. Aktivitas akan tercatat secara otomatis.
            </div>

            {{-- Field: Pilih Stage --}}
            <div class="field">
                <label for="oc_stage_modul" class="form-label">Ubah stage untuk modul terpilih menjadi:</label>
                <select id="oc_stage_modul" name="stage_modul" class="form-select select-soft" required>
                    @foreach($stageOptions as $val => $lab)
                        <option value="{{ $val }}">{{ $lab }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Field: Catatan --}}
            <div class="field">
                <label for="oc_stage_note" class="form-label">Catatan <span class="text-muted">(Opsional)</span></label>
                <textarea id="oc_stage_note" name="note" class="form-control" rows="2" placeholder="Contoh: Semua modul sesi 1 sudah mandiri"></textarea>
            </div>

            {{-- Field: Daftar Modul --}}
            <div>
                <label class="form-label">Pilih Modul:</label>
                <div class="table-responsive border rounded" style="max-height: 40vh;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px" class="text-center">
                                    <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.chk-bulk').forEach(c => c.checked = this.checked)" title="Pilih Semua">
                                </th>
                                <th>Modul</th>
                                <th>Stage Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $x)
                            <tr>
                                <td class="text-center">
                                    <input class="form-check-input chk-bulk" type="checkbox" name="modul_ids[]" value="{{ $x->id }}">
                                </td>
                                <td class="fw-semibold">{{ $x->modul->nama ?? ('Modul #'.$x->modul_id) }}</td>
                                <td><span class="badge-stage {{ $x->stage_badge_class ?? 'secondary' }}">{{ $x->stage_label ?? '—' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center small text-muted py-3">Tidak ada modul.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                <button type="submit" class="btn btn-primary round w-100"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                <button type="button" class="btn btn-ghost round" data-bs-dismiss="offcanvas">Tutup</button>
            </div>
        </form>
    </div>
</div>
