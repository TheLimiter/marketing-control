@extends('layouts.app')

@php
    use Illuminate\Support\Arr;

    $calon      = (int) Arr::get($counts,'calon',0);
    $prospek    = (int) Arr::get($counts,'prospek',0);
    $nego       = (int) Arr::get($counts,'negosiasi',0);
    $mou        = (int) Arr::get($counts,'mou',0);
    $klien      = (int) Arr::get($counts,'klien',0);
    $tanpaMou   = (int) ($klienTanpaMou ?? 0);

    $totalPipe  = max($calon+$prospek+$nego+$mou+$klien, 1);
    $p = fn($n)=> (int) round($n / $totalPipe * 100);

    // --- Legend: mapping warna & deskripsi jenis aktivitas ---
    // Jika di tempat lain kamu sudah punya $badge global, bagian ini boleh dihapus dan pakai variabel itu.
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
    ];

    $jenisDesc = [
        'modul_progress' => 'Progres penggunaan modul',
        'modul_done'     => 'Modul selesai digunakan',
        'modul_reopen'   => 'Modul dibuka ulang / diaktifkan kembali',
        'modul_attach'   => 'Lampiran/berkas terkait modul',
        'stage_change'   => 'Perubahan/kenaikan stage/tahap sekolah (indikator pipeline)',
        'kunjungan'      => 'Kunjungan langsung ke sekolah',
        'meeting'        => 'Pertemuan tatap muka atau online',
        'follow_up'      => 'Tindak lanjut dari komunikasi sebelumnya',
        'whatsapp'       => 'Komunikasi melalui WhatsApp',
        'email'          => 'Komunikasi melalui email',
        'lainnya'        => 'Aktivitas lain di luar kategori di atas',
    ];
@endphp

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="h-hero">Dashboard</div>
            <div class="subtle">{{ now()->translatedFormat('l, d F Y') }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('master.create') }}" class="btn btn-primary round">
                <i class="bi bi-plus-lg me-1"></i> Tambah Sekolah
            </a>
            <a href="{{ route('tagihan.create') }}" class="btn btn-outline-primary round">
                <i class="bi bi-receipt me-1"></i> Buat Tagihan
            </a>
            <form method="post" action="{{ route('theme.toggle') }}">
                @csrf
                <button class="btn btn-ghost">
                    <i class="bi bi-circle-half me-1"></i> Tema
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('ok'))      <div class="alert alert-success elev-1">{{ session('ok') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger elev-1">{{ session('error') }}</div> @endif

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="eyebrow">Calon</div>
                    <div class="h4 mb-0">{{ number_format($calon) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="eyebrow">Prospek</div>
                    <div class="h4 mb-0">{{ number_format($prospek) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="eyebrow">Negosiasi</div>
                    <div class="h4 mb-0">{{ number_format($nego) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="eyebrow">MOU</div>
                    <div class="h4 mb-0">{{ number_format($mou) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="eyebrow">Klien</div>
                    <div class="h4 mb-0">{{ number_format($klien) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-danger">
                <div class="card-body py-3">
                    <div class="eyebrow text-danger">Tanpa MOU</div>
                    <div class="h4 mb-0 text-danger">{{ number_format($tanpaMou) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pipeline mini (prosentase per tahap) --}}
    <div class="card card-toolbar mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="h-section mb-0">
                <i class="bi bi-funnel-fill text-muted"></i>
                <span>Pipeline</span>
            </div>
            <a href="{{ route('master.index') }}" class="link-action">
                Kelola Pipeline
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="hr-soft my-3"></div>
        <div class="progress" style="height:14px">
            <div class="progress-bar bg-secondary" style="width: {{ $p($calon) }}%" title="Calon {{ $calon }}"></div>
            <div class="progress-bar bg-primary"   style="width: {{ $p($prospek) }}%" title="Prospek {{ $prospek }}"></div>
            <div class="progress-bar bg-warning"   style="width: {{ $p($nego) }}%" title="Negosiasi {{ $nego }}"></div>
            <div class="progress-bar bg-info"      style="width: {{ $p($mou) }}%" title="MOU {{ $mou }}"></div>
            <div class="progress-bar bg-success"   style="width: {{ $p($klien) }}%" title="Klien {{ $klien }}"></div>
        </div>
        <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
            <span>Calon {{ $p($calon) }}%</span>
            <span>Prospek {{ $p($prospek) }}%</span>
            <span>Negosiasi {{ $p($nego) }}%</span>
            <span>MOU {{ $p($mou) }}%</span>
            <span>Klien {{ $p($klien) }}%</span>
        </div>
    </div>

    {{-- Weekly quick stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="eyebrow">Prospek Masuk (minggu ini)</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h4 mb-0">{{ number_format($prospekThisWeek ?? 0) }}</div>
                        <span class="chip">baru</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="eyebrow">MOU Diperbarui (minggu ini)</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h4 mb-0">{{ number_format($mouUpdatedThisWeek ?? 0) }}</div>
                        <span class="chip">update</span>
                    </div>
                </div>
            </div>
        </div>
        {{-- ruang kosong untuk widget keuangan/progress kalau nanti ada --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="eyebrow">Catatan</div>
                    <div class="subtle">Selamat bekerja! Cek pipeline & tagihan yang mendekati jatuh tempo</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent activities --}}
    <div class="card card-toolbar p-0">
      <div class="card-header border-0 bg-transparent p-3 d-flex justify-content-between align-items-center">
        <div class="h-section mb-0">
          <i class="bi bi-clock-history text-muted"></i>
          <span>Aktivitas Terbaru</span>
        </div>

        <a href="{{ route('aktivitas.index') }}" class="link-action">
          Semua Aktivitas
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <x-activity-feed :items="$recent" :show-school="true" :show-catatan="true" :compact="true"/>
    </div>

    {{-- Legend / Keterangan Jenis Aktivitas --}}
    <div class="card mt-4">
        <div class="card-body">
            <div class="h-section mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-muted"></i>
                <span>Keterangan Jenis Aktivitas</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px">Jenis</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="small text-muted">
                        @foreach($badge as $jenis => $color)
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-{{ $color }}">{{ $jenis }}</span>
                                </td>
                                <td>{{ $jenisDesc[$jenis] ?? 'Tidak ada deskripsi' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small mt-2 text-muted">
                <span class="fw-semibold">Contoh cepat:</span>
                <span class="badge bg-secondary">modul_assign</span> = Pemberian/penggunaan modul terhadap sekolah,
                <span class="badge bg-dark">stage_change</span> = Perubahan/naik stage/tahap sekolah.
            </div>
        </div>
    </div>
@endsection
