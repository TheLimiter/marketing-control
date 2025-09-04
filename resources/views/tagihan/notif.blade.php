<h4 class="mb-3">Daftar Notifikasi – {{ $tagihan->klien->nama ?? '—' }}</h4>

<table class="table">
  <thead>
    <tr>
      <th>Sekolah</th>
      <th>Nomor</th>
      <th>Jatuh Tempo</th>
      <th>Total</th>
      <th>Terbayar</th>
      <th>Sisa</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($items as $t)
      <tr>
        <td>{{ $t->sekolah->nama_sekolah ?? '-' }}</td>
        <td>{{ $t->nomor }}</td>
        <td>{{ $t->jatuh_tempo }}</td>
        <td>Rp {{ number_format((int)$t->total,0,',','.') }}</td>
        <td>Rp {{ number_format((int)$t->terbayar,0,',','.') }}</td>
        <td>Rp {{ number_format((int)$t->sisa,0,',','.') }}</td>
        <td>{{ ucfirst($t->status ?? 'draft') }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="text-center">Tidak ada tagihan H-{{ $days }}</td></tr>
    @endforelse
  </tbody>
</table>
