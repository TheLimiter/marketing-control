@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    use App\Models\MasterSekolah as MS;

    // mapping stage → label & warna
    $stageOptions = [
        MS::ST_CALON      => 'Calon',
        MS::ST_PROSPEK    => 'Prospek',
        MS::ST_NEGOSIASI  => 'Negosiasi',
        MS::ST_MOU        => 'MOU',
        MS::ST_KLIEN      => 'Klien',
    ];
    // Ini tidak lagi diperlukan karena badge-stage sudah punya class sendiri
    // $stageColor = [
    //     MS::ST_CALON      => 'secondary',
    //     MS::ST_PROSPEK    => 'info',
    //     MS::ST_NEGOSIASI  => 'warning',
    //     MS::ST_MOU        => 'primary',
    //     MS::ST_KLIEN      => 'success',
    // ];
    $st = (string) request('stage');
@endphp

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Master Sekolah</div>
            <div class="subtle">Kelola pipeline & status kemajuan</div>
        </div>
        <a href="{{ route('master.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah Sekolah
        </a>
    </div>

    {{-- Toolbar (search + select stage + per) --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari nama sekolah</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="Ketik nama sekolah…">
            </div>

            <div class="field" style="min-width:200px">
                <label>Stage</label>
                <select name="stage" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string)request('stage')===(string)$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:140px">
                <label>Per halaman</label>
                <select name="per_page" class="select-soft" onchange="this.form.submit()">
                    @foreach([15,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected((int)request('per_page',15)===$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button class="btn btn-primary round">Terapkan</button>
                @if(request()->filled('q') || request()->filled('stage') || request()->filled('per_page'))
                    <a href="{{ route('master.index') }}" class="btn btn-ghost round">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Tabel utama --}}
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-compact table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Sekolah</th>
                        <th>Kontak</th>
                        <th style="min-width:220px;">Stage</th>
                        <th class="text-center" style="width:90px;">MOU</th>
                        <th class="text-center" style="width:80px;">TTD</th>
                        <th style="min-width:170px;">Progress</th>
                        <th style="width:120px;">Update</th>
                        <th style="min-width:260px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $x) {{-- $rows = paginator MasterSekolah --}}
                        @php
                            $sid      = $x->id;
                            $mouAda   = !is_null($x->mou_path);
                            $progress = (int)($x->modul_percent ?? 0);
                        @endphp
                        <tr>
                            {{-- Nama & alamat --}}
                            <td class="fw-medium">
                                <a href="{{ route('master.edit',$sid) }}" class="text-decoration-none">
                                    {{ $x->nama_sekolah ?? '-' }}
                                </a>
                                <div class="small text-muted">{{ $x->alamat ?: '—' }}</div>
                            </td>

                            {{-- Kontak --}}
                            <td>
                                <div class="small">{{ $x->narahubung ?: '—' }}</div>
                                <div class="small text-muted">{{ $x->no_hp ?: '—' }}</div>
                            </td>

                            {{-- Stage + controls kecil --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    {{-- PERBAIKAN: Menetapkan kelas 'secondary' secara eksplisit untuk badge Calon --}}
                                    <span class="badge badge-stage {{ $x->stage == MS::ST_CALON ? 'secondary' : strtolower($stageOptions[$x->stage]) }}">
                                        {{ $stageOptions[$x->stage] ?? '-' }}
                                    </span>

                                    {{-- Dropdown ubah stage --}}
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-ghost round" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah Stage">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach($stageOptions as $val => $label)
                                                @if($val !== $x->stage)
                                                    <li>
                                                        <form action="{{ route('master.stage.update', $sid) }}" method="POST">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="to" value="{{ $val }}">
                                                            <button type="submit" class="dropdown-item">→ {{ $label }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </td>

                            {{-- MOU (status + tombol form) --}}
                            <td class="text-center">
                                {!! $mouAda ? '<span class="badge badge-stage mou">Ada</span>' : '<span class="badge badge-stage secondary">—</span>' !!}
                                <div class="mt-1">
                                    <a href="{{ route('master.mou.form', $sid) }}" class="btn btn-sm btn-ghost round" title="Input / Perbarui MOU">
                                        <i class="bi bi-file-earmark-plus"></i>
                                    </a>
                                </div>
                            </td>

                            {{-- TTD --}}
                            <td class="text-center">
                                {!! $x->ttd_status ? '<span class="badge badge-stage klien">OK</span>' : '<span class="badge badge-stage secondary">—</span>' !!}
                            </td>

                            {{-- Progress modul --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">{{ $progress }}%</small>
                                    <div class="progress flex-grow-1" style="height:8px">
                                        <div class="progress-bar bg-info" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Update time --}}
                            <td class="small text-muted text-nowrap">
                                {{ optional($x->updated_at)->diffForHumans() ?? '—' }}
                            </td>

                            {{-- Aksi --}}
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('master.aktivitas.index', $sid) }}" class="btn btn-sm btn-outline-secondary round" title="Aktivitas">
                                        <i class="bi bi-clock-history me-1"></i> Aktivitas
                                        @if(isset($x->aktivitas_count) && $x->aktivitas_count > 0)
                                            <span class="badge rounded-pill text-bg-info ms-1">{{ $x->aktivitas_count }}</span>
                                        @endif
                                    </a>

                                    <a href="{{ route('progress.show', $sid) }}" class="btn btn-sm btn-outline-secondary round" title="Progress">
                                        <i class="bi bi-graph-up-arrow me-1"></i> Progress
                                    </a>

                                    <a href="{{ route('master.edit',$sid) }}" class="btn btn-sm btn-outline-primary round" title="Edit">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>

                                    {{-- Detail (offcanvas) --}}
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary round"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#schoolDetail"
                                            title="Detail sekolah"
                                            data-nama="{{ $x->nama_sekolah }}"
                                            data-jenjang="{{ $x->jenjang ?? '—' }}"
                                            data-alamat="{{ $x->alamat ?? '—' }}"
                                            data-narahubung="{{ $x->narahubung ?? '—' }}"
                                            data-nohp="{{ $x->no_hp ?? '—' }}"
                                            data-sumber="{{ $x->sumber ?? '—' }}"
                                            data-siswa="{{ $x->jumlah_siswa ?? '—' }}"
                                            data-mou="{{ $mouAda ? 'Ada' : '—' }}"
                                            data-ttd="{{ $x->ttd_status ? 'OK' : '—' }}"
                                            data-stage="{{ $stageOptions[$x->stage] ?? '-' }}"
                                            data-tindak="{{ $x->tindak_lanjut ?? '—' }}"
                                            data-catatan="{{ $x->catatan ? Str::limit($x->catatan, 200) : '—' }}"
                                            data-created="{{ optional($x->created_at)->format('d/m/Y H:i') }}"
                                            data-updated="{{ optional($x->updated_at)->diffForHumans() }}">
                                        <i class="bi bi-info-circle me-1"></i> Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-3">
            {{ $rows->appends(request()->query())->links() }}
        </div>
    </div>
@endsection

{{-- Offcanvas Detail Sekolah (improved) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="schoolDetail" aria-labelledby="schoolDetailLabel" style="--bs-offcanvas-width:480px">
  <div class="offcanvas-header border-bottom">
    <div>
      <div class="eyebrow mb-1">Detail Sekolah</div>
      <h5 class="offcanvas-title h-page mb-0" id="schoolDetailLabel" data-f="nama">-</h5>
      <div class="subtle mt-1">
        <span class="chip" data-f="jenjang">—</span>
        <span class="chip stage-chip" data-f="stage">—</span>
        <span class="chip" data-f="mou">—</span>
        <span class="chip" data-f="ttd">—</span>
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
          <div class="small" data-f="alamat">—</div>
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
              <div class="small" data-f="narahubung">—</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-telephone text-muted mt-1"></i>
            <div>
              <div class="h-section mb-1">No. HP</div>
              <div class="small" data-f="nohp">—</div>
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
          <div class="fw-semibold" data-f="siswa">—</div>
        </div>
        <div class="col-6">
          <div class="text-muted">Sumber</div>
          <div class="fw-semibold" data-f="sumber">—</div>
        </div>
        <div class="col-12">
          <div class="text-muted">Tindak Lanjut</div>
          <div class="fw-semibold" data-f="tindak">—</div>
        </div>
        <div class="col-12">
          <div class="text-muted">Catatan</div>
          <div class="fw-semibold" data-f="catatan">—</div>
        </div>
      </div>
    </div>

    {{-- Meta & Quick Actions --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
      <div class="text-muted small">
        Dibuat: <span data-f="created">—</span> • Update: <span data-f="updated">—</span>
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
      </div>
    </div>
  </div>
</div>

{{-- Script pengisi offcanvas (improved) --}}
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
    return 'chip'; // fallback
  };

  const setText = (key, v) => {
    const el = off.querySelector(`[data-f="${key}"]`);
    if (!el) return;
    el.textContent = v && v !== '' ? v : '—';
  };

  const buildTel = (raw='') => {
    const num = (raw || '').replace(/[^0-9+]/g, '');
    return num ? `tel:${num}` : '#';
  };

  const buildWa = (raw='') => {
    // normalisasi ke 62 jika diawali 0
    let num = (raw || '').replace(/[^0-9]/g, '');
    if (!num) return '#';
    if (num.startsWith('0')) num = '62' + num.slice(1);
    if (!num.startsWith('62')) num = '62' + num;
    return `https://wa.me/${num}`;
  };

  // copy helper
  const copyText = (txt='') => {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(txt).then(() => {
      // kecilkan notifikasi: gunakan BS toast kalau ada, kalau tidak, title jadi “Tersalin”
    });
  };

  off.addEventListener('show.bs.offcanvas', (ev) => {
    const b = ev.relatedTarget;
    if (!b?.dataset) return;

    // set teks
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

    // stage pill look
    const stageEl = off.querySelector('[data-f="stage"]');
    if (stageEl) {
      stageEl.className = 'chip stage-chip'; // reset
      stageEl.className = stageClass(b.dataset.stage);
      stageEl.textContent = b.dataset.stage || '—';
    }

    // actions (edit/aktivitas/progress) – ambil URL dari data-* pada tombol pemicu
    const aEdit = off.querySelector('.quick-edit');
    const aAkt  = off.querySelector('.quick-aktivitas');
    const aProg = off.querySelector('.quick-progress');
    if (aEdit) aEdit.href = b.dataset.edit || '#';
    if (aAkt)  aAkt.href  = b.dataset.aktivitas || '#';
    if (aProg) aProg.href = b.dataset.progress || '#';

    // call/wa links
    const call = off.querySelector('.action-call');
    const wa   = off.querySelector('.action-wa');
    if (call) call.href = buildTel(b.dataset.nohp || '');
    if (wa)   wa.href   = buildWa(b.dataset.nohp || '');

    // wire copy buttons
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

{{-- Sedikit styling khusus offcanvas (opsional, aman dipasang di sini) --}}
<style>
#schoolDetail .chip{padding:.25rem .6rem;border-radius:999px;border:1px solid var(--neutral-grey-13);background:var(--neutral-grey-14);font-weight:600}
#schoolDetail .card{border-radius:14px}
#schoolDetail .h-section{font-weight:700;font-size:.95rem}
#schoolDetail .stage-chip.badge-stage{border:1px solid var(--neutral-grey-13)} /* sinkron feel */
</style>
