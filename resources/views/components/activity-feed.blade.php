@props([
  'items' => collect(),          // collection AktivitasProspek
  'showSchool' => true,          // tampilkan kolom sekolah
  'showCatatan' => true,         // tampilkan ringkasan catatan
  'compact' => false,            // tabel rapat untuk dashboard
])

@php
  use Illuminate\Support\Str;
@endphp

<div class="table-responsive">
  <table class="table table-modern table-sm align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:160px" class="text-nowrap">Tanggal</th>
        @if($showSchool)<th>Sekolah</th>@endif
        <th style="width:140px">Jenis</th>
        <th style="width:260px">Hasil</th>
        @if($showCatatan)<th>Catatan</th>@endif
      </tr>
    </thead>
    <tbody>
      @forelse($items as $it)
        <tr>
          <td class="text-nowrap">
            {{ optional($it->tanggal)->format('d/m/Y H:i') ?? optional($it->created_at)->format('d/m/Y H:i') }}
          </td>

          @if($showSchool)
            <td class="text-truncate">
              @if($it->master_sekolah_id)
                <a href="{{ route('master.aktivitas.index', $it->master_sekolah_id) }}" class="text-decoration-none">
                  {{ optional($it->master)->nama_sekolah ?? 'â€”' }}
                </a>
              @else
                <span class="text-muted">â€”</span>
              @endif
            </td>
          @endif

          <td>
            <span class="badge badge-stage {{ $it->badge_class ?? 'text-bg-secondary' }}">
              {{ $it->display_jenis ?? Str::headline((string) $it->jenis) }}
            </span>
          </td>

          <td class="fw-medium">
            {{ $it->display_hasil ?? ($it->hasil ?? 'â€”') }}
          </td>

          @if($showCatatan)
            <td class="text-muted small">
              {{ Str::limit((string) $it->catatan, 160) ?: 'â€”' }}
            </td>
          @endif
        </tr>
      @empty
        <tr><td colspan="{{ 3 + (int)$showSchool + (int)$showCatatan }}" class="text-center text-muted py-4">
          Belum ada aktivitas.
        </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
