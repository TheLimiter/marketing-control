{{-- Offcanvas Detail Sekolah --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="schoolDetail" aria-labelledby="schoolDetailLabel" style="--bs-offcanvas-width:480px">
  <div class="offcanvas-header border-bottom">
    <div>
      <div class="eyebrow mb-1">Detail Sekolah</div>
      <h5 class="offcanvas-title h-page mb-0" id="schoolDetailLabel" data-f="nama">-</h5>
      <div class="subtle mt-1">
        <span class="chip" data-f="jenjang"></span>
        <span class="chip stage-chip" data-f="stage"></span>
        <span class="chip" data-f="mou"></span>
        <span class="chip" data-f="ttd"></span>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body">
    {{-- Alamat --}}
    <div class="card p-3 mb-3">
      <div class="d-flex align-items-start gap-2">
        <i class="bi bi-geo-alt text-muted mt-1"></i>
        <div class="flex-grow-1">
          <div class="h-section mb-1">Alamat</div>
          <div class="small" data-f="alamat"></div>
        </div>
        <button class="btn btn-sm btn-ghost round copy-btn" data-copy="alamat" title="Salin alamat">
          <i class="bi bi-clipboard"></i>
        </button>
      </div>
    </div>

    {{-- Kontak --}}
    <div class="card p-3 mb-3">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-person-lines-fill text-muted mt-1"></i>
            <div>
              <div class="h-section mb-1">Narahubung</div>
              <div class="small" data-f="narahubung"></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-telephone text-muted mt-1"></i>
            <div>
              <div class="h-section mb-1">No. HP</div>
              <div class="small" data-f="nohp"></div>
              <div class="d-flex gap-2 mt-1">
                <a class="btn btn-sm btn-outline-secondary round action-call" target="_blank">
                  <i class="bi bi-telephone-outbound me-1"></i> Telepon
                </a>
                <a class="btn btn-sm btn-outline-success round action-wa" target="_blank">
                  <i class="bi bi-whatsapp me-1"></i> WhatsApp
                </a>
                <button class="btn btn-sm btn-ghost round copy-btn" data-copy="nohp" title="Salin nomor">
                  <i class="bi bi-clipboard"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Info Lainnya --}}
    <div class="card p-3 mb-3">
      <div class="row small g-3">
        <div class="col-6">
          <div class="text-muted">Jumlah Siswa</div>
          <div class="fw-semibold" data-f="siswa"></div>
        </div>
        <div class="col-6">
          <div class="text-muted">Sumber</div>
          <div class="fw-semibold" data-f="sumber"></div>
        </div>
        <div class="col-12">
          <div class="text-muted">Tindak Lanjut</div>
          <div class="fw-semibold" data-f="tindak"></div>
        </div>
        <div class="col-12">
          <div class="text-muted">Catatan</div>
          <div class="fw-semibold" data-f="catatan"></div>
        </div>
      </div>
    </div>

    {{-- Meta & Quick Actions --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
      <div class="text-muted small">
        Dibuat: <span data-f="created"></span>Update: <span data-f="updated"></span>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="#" class="btn btn-sm btn-ghost round quick-aktivitas">
          <i class="bi bi-clock-history me-1"></i> Aktivitas
        </a>
        <a href="#" class="btn btn-sm btn-ghost round quick-progress">
          <i class="bi bi-graph-up-arrow me-1"></i> Progress
        </a>
        <a href="#" class="btn btn-sm btn-primary round quick-edit">
          <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="#" class="btn btn-sm btn-success quick-batch">
          + Tambah Modul (Batch)
        </a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const off = document.getElementById('schoolDetail');
  if (!off) return;

  const stageClass = (txt='') => {
    const k = (txt || '').toString().trim().toLowerCase();
    if (k.includes('calon')) return 'badge-stage calon';
    if (k.includes('prospek')) return 'badge-stage prospek';
    if (k.includes('negosiasi')) return 'badge-stage negosiasi';
    if (k.includes('mou')) return 'badge-stage mou';
    if (k.includes('klien')) return 'badge-stage klien';
    return 'chip';
  };

  const setText = (key, v) => {
    const el = off.querySelector(`[data-f="${key}"]`);
    if (el) el.textContent = (v && v !== '') ? v : '-';
  };

  const buildTel = (raw='') => {
    const num = (raw || '').replace(/[^0-9+]/g, '');
    return num ? `tel:${num}` : '#';
  };
  const buildWa = (raw='') => {
    let num = (raw || '').replace(/[^0-9]/g, '');
    if (!num) return '#';
    if (num.startsWith('0')) num = '62' + num.slice(1);
    if (!num.startsWith('62')) num = '62' + num;
    return `https://wa.me/${num}`;
  };

  const copyText = (txt='') => {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(txt).catch(() => {});
  };

  off.addEventListener('show.bs.offcanvas', (ev) => {
    const b = ev.relatedTarget;
    if (!b || !b.dataset) return;

    setText('nama', b.dataset.nama);
    setText('jenjang', b.dataset.jenjang);
    setText('stage', b.dataset.stage);
    setText('alamat', b.dataset.alamat);
    setText('narahubung', b.dataset.narahubung);
    setText('nohp', b.dataset.nohp);
    setText('siswa', b.dataset.siswa);
    setText('sumber', b.dataset.sumber);
    setText('mou', b.dataset.mou);
    setText('ttd', b.dataset.ttd);
    setText('tindak', b.dataset.tindak);
    setText('catatan', b.dataset.catatan);
    setText('created', b.dataset.created);
    setText('updated', b.dataset.updated);

    const stageEl = off.querySelector('[data-f="stage"]');
    if (stageEl) {
      stageEl.className = 'chip stage-chip';
      stageEl.className = stageClass(b.dataset.stage);
      stageEl.textContent = b.dataset.stage || '';
    }

    const aEdit   = off.querySelector('.quick-edit');
    const aAkt    = off.querySelector('.quick-aktivitas');
    const aProg   = off.querySelector('.quick-progress');
    const aBatch  = off.querySelector('.quick-batch');
    if (aEdit)  aEdit.href  = b.dataset.edit      || '#';
    if (aAkt)   aAkt.href   = b.dataset.aktivitas || '#';
    if (aProg)  aProg.href  = b.dataset.progress  || '#';
    if (aBatch) aBatch.href = b.dataset.batch     || '#';

    const call = off.querySelector('.action-call');
    const wa   = off.querySelector('.action-wa');
    if (call) call.href = buildTel(b.dataset.nohp || '');
    if (wa)   wa.href   = buildWa(b.dataset.nohp || '');

    off.querySelectorAll('.copy-btn').forEach(btn => {
      btn.onclick = () => {
        const key = btn.getAttribute('data-copy');
        const el  = off.querySelector(`[data-f="${key}"]`);
        if (el) copyText(el.textContent.trim());
      };
    });
  });
});
</script>
@endpush

@push('styles')
<style>
#schoolDetail .chip{padding:.25rem .6rem;border-radius:999px;border:1px solid var(--neutral-grey-13);background:var(--neutral-grey-14);font-weight:600}
#schoolDetail .card{border-radius:14px}
#schoolDetail .h-section{font-weight:700;font-size:.95rem}
#schoolDetail .stage-chip.badge-stage{border:1px solid var(--neutral-grey-13)}
</style>
@endpush
