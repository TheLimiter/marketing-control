@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    use App\Models\BillingPaymentFile;

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
        'billing_payment' => 'success',
        'billing_create'  => 'secondary',
    ];
    // Pastikan $toggle dihitung dari parameter 'sort' yang saat ini, bukan 'dir'
    $toggle = request('dir', 'desc') === 'desc' ? 'asc' : 'desc';
    $currentSort = request('sort', 'tanggal');
@endphp

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Semua Aktivitas</div>
            <div class="subtle">Riwayat aktivitas dari seluruh sekolah</div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="get" id="filterForm" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="d-flex gap-2 align-items-end">
                <div class="field" style="min-width:140px">
                    <label>Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="input-soft">
                </div>
                <div class="field" style="min-width:140px">
                    <label>Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="input-soft">
                </div>
            </div>

            <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari Sekolah</label>
                <input name="school" value="{{ request('school') }}" class="input-soft" placeholder="Nama sekolah">
            </div>

            <div class="field" style="min-width:180px">
                <label>Jenis</label>
                <input type="text" name="jenis" value="{{ request('jenis') }}" class="input-soft" placeholder="Ketik jenis">
            </div>

            <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari (hasil/catatan)</label>
                <input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Kata kunci">
            </div>

            <div class="field" style="min-width:200px">
                <label>Oleh</label>
                <input type="text" name="oleh" value="{{ request('oleh') }}" class="input-soft" placeholder="Nama user" list="dl-oleh">
                @isset($creatorOptions)
                    <datalist id="dl-oleh">
                        @foreach($creatorOptions as $n)
                            <option value="{{ $n }}"></option>
                        @endforeach
                    </datalist>
                @endisset
            </div>

            <div class="field" style="min-width:120px">
                <label>Per halaman</label>
                <select name="per" class="select-soft" onchange="this.form.submit()">
                    @foreach([15,25,50,100] as $n)
                        <option value="{{ $n }}" @selected(request('per',25)==$n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Terapkan
                </button>
                <a href="{{ route('aktivitas.index') }}" class="btn btn-ghost round">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>

        {{-- Footer toolbar: trash toggles --}}
        <div class="toolbar-footer d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">Hari ini</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(7)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">7 hari</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(30)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">30 hari</a>

            <a href="{{ route('aktivitas.export', request()->all()) }}" class="btn btn-sm btn-outline-success round ms-auto">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>

            @php $isTrash = request()->boolean('trashed'); @endphp
            @if($isTrash)
                <a href="{{ request()->fullUrlWithoutQuery(['trashed','with_trashed','page']) }}"
                   class="btn btn-ghost round">
                    <i class="bi bi-arrow-left me-1"></i> Keluar dari Sampah
                </a>
                <a href="{{ route('aktivitas.index', array_merge(request()->except(['trashed','page']), ['with_trashed'=>1])) }}"
                   class="btn btn-outline-secondary round">
                    Semua (+ terhapus)
                </a>
            @else
                <a href="{{ route('aktivitas.index', array_merge(request()->except(['with_trashed','page']), ['trashed'=>1])) }}"
                   class="btn btn-outline-danger round">
                    <i class="bi bi-trash3 me-1"></i> Lihat yang terhapus
                </a>
            @endif
        </div>
    </form>

    {{-- Tabel Utama --}}
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:26px">
                            <input type="checkbox" id="chkAll" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)">
                        </th>
                        <th>
                            {{-- Ubah link header Tanggal ke created_at --}}
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'created_at','dir'=>$toggle]) }}" class="text-decoration-none">Tanggal</a>
                        </th>
                        <th>Sekolah</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'jenis','dir'=>$toggle]) }}" class="text-decoration-none">Jenis</a>
                        </th>
                        <th>Hasil</th>
                        <th>Catatan</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'creator_name','dir'=>$toggle]) }}" class="text-decoration-none">Oleh</a>
                        </th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $it)
                        @php $k = strtolower($it->jenis ?? 'lainnya'); @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $it->id }}" class="rowchk" form="bulkForm">
                            </td>
                            <td>
                                {{-- Tampilkan tanggal dari created_at, bukan dari tanggal --}}
                                <div class="fw-medium">{{ optional($it->created_at)->format('d M Y') ?: '-' }}</div>
                                <div class="text-muted small">
                                    {{ optional($it->created_at)->diffForHumans() }}
                                    @if(method_exists($it,'trashed') && $it->trashed())
                                        <span class="badge text-bg-danger ms-1">Deleted {{ optional($it->deleted_at)->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('master.aktivitas.index', optional($it->master)->id) }}" class="text-decoration-none">
                                    {{ optional($it->master)->nama_sekolah ?? '-' }}
                                </a>
                            </td>
                            <td>
                                <span class="badge rounded-pill text-bg-{{ $badge[$k] ?? 'secondary' }}">
                                    {{ strtoupper($it->jenis ?? 'LAINNYA') }}
                                </span>
                            </td>
                            <td class="fw-medium">
                                {{ $it->hasil }}
                                @php
                                    $allFiles = collect()->concat($it->files)->concat($it->paymentFiles);
                                    $fc = $allFiles->count();
                                @endphp
                                @if($fc)
                                    <details class="d-inline-block align-middle">
                                        <summary class="badge rounded-pill text-bg-secondary border-0" style="cursor:pointer; list-style:none; display:inline-block;">
                                            <i class="bi bi-paperclip me-1"></i> {{ $fc }}
                                        </summary>
                                        <ul class="list-unstyled small mb-0 mt-1">
                                            @foreach($allFiles as $f)
                                                @php
                                                    $isBilling   = $f instanceof BillingPaymentFile;
                                                    $previewUrl  = $isBilling
                                                        ? route('billing.file.preview',  $f->id)
                                                        : route('aktivitas.file.preview', $f->id);
                                                    $downloadUrl = $isBilling
                                                        ? route('billing.file.download', $f->id)
                                                        : route('aktivitas.file.download', $f->id);
                                                @endphp
                                                <li class="d-flex align-items-center gap-2">
                                                    <a href="{{ $downloadUrl }}" class="text-truncate">{{ $f->original_name }}</a>
                                                    <span class="text-muted text-nowrap">({{ number_format(($f->size ?? 0)/1024,1) }} KB)</span>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                            onclick="previewFile(
                                                                @json($previewUrl),
                                                                @json($f->original_name),
                                                                @json($f->mime)
                                                            )">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td class="text-muted small">{!! $it->catatan ? nl2br(e(Str::limit($it->catatan,200))) : '-' !!}</td>
                            <td class="small">{{ optional($it->creator)->name ?? '-' }}</td>
                            <td class="text-end">
                                @if(optional($it->master)->id)
                                    @if(method_exists($it,'trashed') && $it->trashed())
                                        <form method="post"
                                              action="{{ route('master.aktivitas.restore', [$it->master->id, $it->id]) }}"
                                              class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-success round">
                                                <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                            </button>
                                        </form>

                                        @if(auth()->user()?->hasRole('admin'))
                                            <form method="post"
                                                  action="{{ route('master.aktivitas.force', [$it->master->id, $it->id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus permanen item ini? Tindakan tidak bisa dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger round">
                                                    <i class="bi bi-x-circle"></i> Hapus permanen
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <a class="btn btn-sm btn-outline-secondary round"
                                           href="{{ route('master.aktivitas.index', $it->master->id) }}">
                                            Detail
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- toolbar bulk --}}
        <form id="bulkForm" method="post" action="{{ route('aktivitas.bulk') }}"
              class="d-flex flex-wrap gap-2 align-items-center p-3 border-top">
            @csrf
            <select name="action" class="select-soft" style="width:auto">
                <option value="" selected>Aksi massal</option>
                <option value="delete">Hapus terpilih</option>
                <option value="export">Export CSV terpilih</option>
            </select>
            <button class="btn btn-primary round" onclick="return confirm('Lanjutkan aksi massal?')">Jalankan</button>
            <span class="text-muted small ms-auto">Terlihat {{ $items->count() }} dari total {{ $items->total() }}</span>
        </form>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $items->links() }}
    </div>

    {{-- Universal File Preview Modal (Modal untuk gambar & PDF) --}}
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="filePreviewTitle">Pratinjau File</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="filePreviewContent" class="d-flex justify-content-center align-items-center" style="min-height: 40vh;">
              </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
      const filePreviewTitle = document.getElementById('filePreviewTitle');
      const filePreviewContent = document.getElementById('filePreviewContent');

      function previewFile(url, title, mime) {
        filePreviewTitle.textContent = title;
        filePreviewContent.innerHTML = ''; // Bersihkan konten sebelumnya

        // Cek apakah mime type adalah image atau pdf
        if (mime && (mime.startsWith('image/') || mime === 'application/pdf')) {
            const embed = document.createElement('embed');
            embed.src = url;
            embed.type = mime;
            embed.style.width = '100%';
            embed.style.height = '70vh';
            filePreviewContent.appendChild(embed);
        } else {
            // Jika bukan image/pdf, berikan pesan atau tautan download
            const message = document.createElement('div');
            message.innerHTML = `
                <div class="text-center text-muted">
                    <p>Tipe file ini tidak didukung untuk pratinjau. Silakan unduh file untuk melihatnya.</p>
                    <a href="${url}" class="btn btn-primary mt-2">Unduh File</a>
                </div>
            `;
            filePreviewContent.appendChild(message);
        }

        filePreviewModal.show();
      }
    </script>
@endsection
