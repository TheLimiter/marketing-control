<table>
    <tr>
        <th>Klien</th>
        <th>Nomor</th>
        <th>Tanggal</th>
        <th>Jatuh Tempo</th>
        <th>Total</th>
        <th>Terbayar</th>
        <th>Sisa</th>
        <th>Status</th>
        <th>Catatan</th>
    </tr>
    @foreach($tagihan as $t)
    <tr>
        <td>{{ $t->klien->nama_sekolah ?? '-' }}</td>
        <td>{{ $t->nomor }}</td>
        <td>{{ $t->tanggal_tagihan }}</td>
        <td>{{ $t->jatuh_tempo }}</td>
        <td>{{ (int)$t->total }}</td>
        <td>{{ (int)$t->terbayar }}</td>
        <td>{{ max((int)$t->total - (int)$t->terbayar, 0) }}</td>
        <td>{{ $t->status }}</td>
        <td>{{ $t->catatan }}</td>
    </tr>
    @endforeach
</table>
