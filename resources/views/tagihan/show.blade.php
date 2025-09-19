@extends('layouts.app')

@php
 use Carbon\Carbon;

 $isOverdue  = $tagihan->jatuh_tempo && $tagihan->sisa > 0 && Carbon::parse($tagihan->jatuh_tempo)->isPast();
 $isDueToday = $tagihan->jatuh_tempo && $tagihan->sisa > 0 && Carbon::parse($tagihan->jatuh_tempo)->isToday();

 $statusBadge = [
  'draft'    => 'secondary',
  'sebagian' => 'warning',
  'lunas'    => 'success',
 ][$tagihan->status] ?? 'secondary';

 $agingBadge  = $isOverdue ? 'danger' : ($isDueToday ? 'warning' : 'secondary');
 $agingLabel  = $isOverdue
  ? (Carbon::parse($tagihan->jatuh_tempo)->diffInDays(Carbon::today()) . ' hari lewat')
  : ($isDueToday ? 'Jatuh tempo hari ini' : 'Current');
@endphp

@section('content')

 {{-- Header --}}
 <div class="d-flex justify-content-between align-items-center mb-4">
  <div>
   <div class="text-muted small mb-1">Detail</div>
   <h5 class="mb-2">Detail Tagihan</h5>
   <div class="d-flex flex-wrap gap-2">
    <span class="badge bg-{{ $statusBadge }}">{{ strtoupper($tagihan->status) }}</span>
    @if($tagihan->jatuh_tempo)
      <span class="badge bg-{{ $agingBadge }}">{{ $agingLabel }}</span>
    @endif
   </div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('tagihan.edit',$tagihan) }}" class="btn btn-outline-primary btn-sm round">Edit</a>
    <a href="{{ route('tagihan.index') }}" class="btn btn-ghost btn-sm round">Kembali</a>
  </div>
 </div>

 {{-- Flash --}}
 @if(session('ok'))   <div class="alert alert-success">{{ session('ok') }}</div> @endif
 @if(session('err'))  <div class="alert alert-danger">{{ session('err') }}</div> @endif
 @if($errors->any())
  <div class="alert alert-danger"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
 @endif

 <div class="row g-3">
  {{-- Kiri: detail ringkas --}}
  <div class="col-lg-8">
   <div class="card">
    <div class="card-body pb-2">
     <dl class="row mb-0 dl-spaced">
      <dt class="col-sm-4">Sekolah</dt>
      <dd class="col-sm-8">
       <div class="fw-medium">{{ $tagihan->sekolah->nama_sekolah ?? '-' }}</div>
       @if(optional($tagihan->sekolah)->id)
         <div class="mt-1">
          <a href="{{ route('master.aktivitas.index', $tagihan->master_sekolah_id) }}"
             class="link-soft small">
           Lihat aktivitas sekolah <i class="bi bi-arrow-up-right ms-1"></i>
          </a>
         </div>
       @endif
      </dd>

      <dt class="col-sm-4">Nomor Tagihan</dt>
      <dd class="col-sm-8">{{ $tagihan->nomor ?? '-' }}</dd>

      <dt class="col-sm-4">Tanggal Tagihan</dt>
      <dd class="col-sm-8">
        {{ $tagihan->tanggal_tagihan ? date_id($tagihan->tanggal_tagihan) : '-' }}
      </dd>

      <dt class="col-sm-4">Jatuh Tempo</dt>
      <dd class="col-sm-8">
        @if($tagihan->jatuh_tempo)
          <span class="badge bg-{{ $agingBadge }}">
            {{ date_id($tagihan->jatuh_tempo, 'd/m/Y') }}
          </span>
          <span class="text-muted small ms-1">
            ({{ \Carbon\Carbon::parse($tagihan->jatuh_tempo)->diffForHumans() }})
          </span>
        @else
          -
        @endif
      </dd>

      <dt class="col-sm-4">Total</dt>
      <dd class="col-sm-8 fw-semibold">{{ rupiah($tagihan->total) }}</dd>

      <dt class="col-sm-4">Terbayar</dt>
      <dd class="col-sm-8">{{ rupiah($tagihan->terbayar) }}</dd>

      <dt class="col-sm-4">Sisa</dt>
      <dd class="col-sm-8 {{ $tagihan->sisa > 0 ? 'fw-semibold' : '' }}">{{ rupiah($tagihan->sisa) }}</dd>

      <dt class="col-sm-4">Status</dt>
      <dd class="col-sm-8">
        <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($tagihan->status) }}</span>
      </dd>

      <dt class="col-sm-4">Catatan</dt>
      <dd class="col-sm-8 lh-base">{{ $tagihan->catatan ?: '-' }}</dd>
     </dl>
    </div>
   </div>
  </div>

  {{-- Kanan: aksi cepat --}}
  <div class="col-lg-4">

   {{-- Pembayaran --}}
   <div class="card mb-3">
    <div class="card-body">
     <div class="h6 mb-3">Catat Pembayaran</div>

     <form action="{{ route('tagihan.bayar.simpan', $tagihan) }}"
           method="post"
           enctype="multipart/form-data"
           class="vstack gap-2">
       @csrf

       <div class="row g-2">
         <div class="col-6">
           <input type="number" name="nominal" class="form-control input-soft"
                  min="1" step="1" placeholder="Nominal" required>
           <div class="form-text">Sisa saat ini: {{ rupiah($tagihan->sisa) }}</div>
         </div>
         <div class="col-6">
           <input type="date" name="tanggal_bayar" class="form-control input-soft"
                  value="{{ now()->format('Y-m-d') }}">
         </div>
       </div>

       <input type="text" name="metode" class="form-control input-soft" placeholder="Metode (Transfer/Tunai)">
       <input type="text" name="catatan" class="form-control input-soft" placeholder="Catatan (opsional)">

       <div>
         <input type="file" name="bukti" class="form-control"
                accept="image/*,application/pdf">
         <div class="form-text">Upload bukti transaksi (gambar/PDF), opsional.</div>
       </div>

       <div class="d-flex gap-2 mt-1">
         <button class="btn btn-success round">Simpan</button>
         <a href="{{ route('tagihan.index') }}" class="btn btn-outline-secondary round">Batal</a>
       </div>
     </form>

     @if($tagihan->paymentFiles->count())
       <hr>
       <div class="small fw-semibold mb-2">Bukti Pembayaran</div>
       <ul class="list-unstyled m-0 small">
         @foreach($tagihan->paymentFiles as $bf)
           <li class="mb-1 d-flex align-items-center gap-2">
             <a href="{{ route('billing.file.download', $bf) }}">
               {{ $bf->original_name }}
             </a>
             <span class="text-muted">({{ number_format($bf->size/1024,1) }} KB)</span>

             {{-- tombol preview (gambar/pdf) --}}
             @if(Str::startsWith((string)$bf->mime, 'image/') || $bf->mime === 'application/pdf')
               <a class="btn btn-sm btn-outline-secondary"
                  href="{{ route('billing.file.preview', $bf) }}" target="_blank">Preview</a>
             @endif
           </li>
         @endforeach
       </ul>
     @endif
    </div>
   </div>

   {{-- Notifikasi sederhana --}}
   <div class="card">
    <div class="card-body">
     <div class="h6 mb-3">Notifikasi</div>
     <form action="{{ route('notifikasi.store') }}" method="post" class="vstack gap-2">
      @csrf
      <input type="hidden" name="tagihan_id" value="{{ $tagihan->id }}">
      <select name="saluran" class="form-select select-soft" style="max-width:200px">
        <option value="Email">Email</option>
        <option value="WA">WA</option>
        <option value="SMS">SMS</option>
      </select>
      <textarea name="isi_pesan" class="form-control input-soft" rows="2"
                placeholder="Isi pesan (opsional)"></textarea>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary round">Buat Notifikasi</button>
        <a href="{{ route('tagihan.wa', $tagihan->id) }}" target="_blank" class="btn btn-success round">
          Kirim WA
        </a>
      </div>
     </form>
     <hr>
     <ul class="list-unstyled m-0 small">
       @forelse($tagihan->notifikasi as $n)
         <li class="mb-2">
           [{{ optional($n->created_at)->format('d/m/Y H:i') }}]
           <strong>{{ $n->saluran }}</strong> - {{ $n->status }}
         </li>
       @empty
         <li class="text-muted">Belum ada notifikasi</li>
       @endforelse
     </ul>
    </div>
   </div>
  </div>
 </div>

 {{-- Scoped tweaks --}}
 <style>
  .dl-spaced > dt,
  .dl-spaced > dd { margin-bottom: .65rem; line-height: 1.4; }
  .dl-spaced > dt { color: var(--neu-6, #878AA6); font-weight: 500; }
  .link-soft { color: var(--brand-600, #2563eb); text-decoration: none; }
  .link-soft:hover { text-decoration: underline; }
 </style>
@endsection
