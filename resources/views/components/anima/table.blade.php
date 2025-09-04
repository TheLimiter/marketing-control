@props([
  'title' => null,           // judul panel kiri: mis. "RPL X-1" / nama sekolah
  'subtitle' => null,        // kanan: "40 siswa" / "9 modul"
  'rows' => [],              // array: ['checked'=>bool,'nama'=>str,'nis'=>str|int,'ket'=>str,'toggle'=>url]
  'scroll' => false,         // true = batasi tinggi dg scrollbar
])

@once
  @push('styles')
    @vite('resources/css/anima-table.css')
  @endpush
@endonce


<div class="anima-scope">
  {{-- Panel header --}}
  <div class="anima-panel">
    <div class="check">
      {{-- master checkbox optional; tinggal aktifkan kalau perlu --}}
      {{-- <input type="checkbox" class="anima-checkbox"> --}}
    </div>
    <div class="title anima-nowrap">{{ $title }}</div>
    @if($subtitle)
      <div class="dropdown"><span>{{ $subtitle }}</span></div>
    @endif
  </div>

  {{-- Tabel --}}
  <div class="anima-table">
    <div class="anima-head">
      <div class="anima-col col-check"></div>
      <div class="anima-col col-name"><div class="label">Nama</div></div>
      <div class="anima-col col-nis"><div class="label">NIS</div></div>
      <div class="anima-col col-ket"><div class="label">Keterangan</div></div>
    </div>

    <div class="anima-body {{ $scroll ? 'scroll' : '' }}">
      @forelse($rows as $r)
        <div class="anima-row">
          <div class="anima-col col-check">
            @if(!empty($r['toggle']))
              <form action="{{ $r['toggle'] }}" method="post">
                @csrf
                <input type="checkbox" class="anima-checkbox" {{ !empty($r['checked']) ? 'checked' : '' }} onchange="this.form.submit()">
              </form>
            @else
              <input type="checkbox" class="anima-checkbox" disabled {{ !empty($r['checked']) ? 'checked' : '' }}>
            @endif
          </div>
          <div class="anima-col col-name"><div class="text anima-nowrap">{{ $r['nama'] ?? '' }}</div></div>
          <div class="anima-col col-nis"><div class="text anima-nowrap">{{ $r['nis'] ?? '' }}</div></div>
          <div class="anima-col col-ket">
            <div class="text {{ ($r['ket'] ?? '') === 'Tambah keterangan' ? 'muted' : '' }}">
              {{ $r['ket'] ?? '' }}
            </div>
          </div>
        </div>
      @empty
        <div class="anima-row">
          <div class="anima-col col-check"></div>
          <div class="anima-col col-name"><div class="text muted">Belum ada data</div></div>
          <div class="anima-col col-nis"></div>
          <div class="anima-col col-ket"></div>
        </div>
      @endforelse
    </div>
  </div>
</div>
