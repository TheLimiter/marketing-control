@extends('layouts.app')

@push('styles')
<style>
  /* ==== Toolbar & Form Controls ==== */
  .card-toolbar .toolbar{display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end}
  .card-toolbar .field{min-width:220px}
  .card-toolbar .ms-auto{margin-left:auto!important}
  .input-soft,.select-soft,.card-toolbar .btn{height:42px}

  /* ==== Avatar ==== */
  .avatar-circle{
    width:36px;height:36px;border-radius:9999px;
    display:inline-flex;align-items:center;justify-content:center;
    background:#e9f0ff;color:#1d4ed8;font-weight:700;letter-spacing:.5px
  }

  /* ==== Table Look & Feel ==== */
  .table-modern thead th{background:#f8fafc;border-bottom:1px solid #e9eef5}
  .table-modern td,.table-modern th{vertical-align:middle}
  .table-modern.table-hover tbody tr:hover{background:#fafcff}
  .table-modern .badge.bg-secondary{background:#eef2ff!important;color:#3730a3!important}
  .btn.round{border-radius:9999px}
</style>
@endpush

@section('content')
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <div class="h-page">Manajemen Pengguna</div>
      <div class="subtle">Kelola akun, peran, dan status akses</div>
    </div>

    {{-- Tambah pengguna --}}
    @role('admin')
      <button class="btn btn-primary round" data-bs-toggle="modal" data-bs-target="#userCreateModal">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
      </button>
    @endrole
  </div>

  {{-- Flash --}}
  @if(session('ok'))
    <div class="alert alert-success elev-1">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger elev-1">{{ session('error') }}</div>
  @endif

  {{-- Toolbar filter & cari --}}
  <form method="GET" action="{{ route('admin.users.index') }}" class="card card-toolbar mb-3">
    <div class="toolbar w-100">
      <div class="field flex-grow-1">
        <label>Cari nama / email</label>
        <input type="search" name="q" value="{{ request('q') }}" class="input-soft" placeholder="Ketik kata kunci">
      </div>
      <div class="field min-180">
        <label>Role</label>
        <select name="role" class="select-soft">
          <option value="">— Semua —</option>
          @foreach($roles as $role)
            <option value="{{ $role }}" {{ request('role')===$role?'selected':'' }}>{{ ucfirst($role) }}</option>
          @endforeach
        </select>
      </div>
      <div class="field min-180">
        <label>Status</label>
        <select name="status" class="select-soft">
          <option value="">— Semua —</option>
          <option value="1" {{ request('status')==='1'?'selected':'' }}>Aktif</option>
          <option value="0" {{ request('status')==='0'?'selected':'' }}>Nonaktif</option>
        </select>
      </div>
      <div class="ms-auto d-flex align-items-end">
        <button class="btn btn-primary round">
          <i class="bi bi-funnel me-1"></i> Terapkan
        </button>
      </div>
    </div>
  </form>

  {{-- Tabel --}}
  <div class="card p-0">
    <div class="table-responsive">
      <table class="table table-modern table-hover align-middle mb-0">
        <colgroup>
          <col style="width:60px">
          <col>
          <col style="width:140px">
          <col style="width:280px">
          <col style="width:100px">
          <col style="width:160px">
        </colgroup>
        <thead>
          <tr>
            <th class="ps-3">No</th>
            <th>Pengguna</th>
            <th>Role Aktif</th>
            <th>Ganti Role</th>
            <th>Status</th>
            <th class="text-end pe-3">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($users as $u)
          @php
            $no = ($users->firstItem() ?? 1) + $loop->index;
            $roleNames = $u->roles->pluck('name');
          @endphp
          <tr>
            <td class="ps-3">{{ $no }}</td>

            {{-- Pengguna --}}
            <td>
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle">
                  {{ strtoupper(
                      collect(preg_split('/\s+/u', trim($u->name ?? '')))
                        ->filter()
                        ->map(fn($p)=>mb_substr($p,0,1))
                        ->take(2)
                        ->implode('')
                    ) ?: 'U' }}
                </div>
                <div>
                  <div class="fw-semibold text-dark">{{ $u->name }}</div>
                  <div class="text-secondary small">{{ $u->email }}</div>
                </div>
              </div>
            </td>

            {{-- Role aktif --}}
            <td>
              @forelse($roleNames as $rn)
                <span class="badge bg-secondary">{{ ucfirst($rn) }}</span>
              @empty
                <span class="badge text-bg-light text-dark">&mdash;</span>
              @endforelse
            </td>

            {{-- Form ganti role --}}
            <td>
              @role('admin')
              <form action="{{ route('admin.users.update', $u) }}" method="post" class="d-flex gap-2 align-items-center">
                @csrf @method('PUT')
                <select name="role" class="form-select select-soft" style="min-width:200px">
                  @foreach($roles as $role)
                    <option value="{{ $role }}" {{ $roleNames->contains($role) ? 'selected' : '' }}>
                      {{ ucfirst($role) }}
                    </option>
                  @endforeach
                </select>
                <button class="btn btn-outline-primary round">
                  <i class="bi bi-floppy me-1"></i> Simpan
                </button>
              </form>
              @else
                <span class="text-muted small">Perlu hak admin</span>
              @endrole
            </td>

            {{-- Status --}}
            <td>
              <span class="badge {{ $u->active ? 'bg-success' : 'bg-danger' }}">
                {{ $u->active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>

            {{-- Aksi --}}
            <td class="text-end">
              @role('admin')
                @if(auth()->id() !== $u->id)
                  <form action="{{ route('admin.users.toggle-status', $u) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Yakin ingin {{ $u->active ? 'menonaktifkan' : 'mengaktifkan' }} pengguna ini?')">
                    @csrf
                    <button class="btn btn-sm btn-{{ $u->active ? 'danger' : 'success' }} round">
                      <i class="bi bi-{{ $u->active ? 'person-dash' : 'person-check' }} me-1"></i>
                      {{ $u->active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                  </form>
                @else
                  <span class="text-muted small">Tidak bisa ubah diri sendiri</span>
                @endif
              @endrole
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <i class="bi bi-people me-2"></i> Belum ada pengguna
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $users->links() }}
  </div>

  {{-- Modal: Tambah Pengguna --}}
  @role('admin')
  <div class="modal fade" id="userCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('admin.users.store') }}" method="post" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body form--soft">
          <div class="mb-2">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control input-soft" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control input-soft" required>
          </div>
          <div class="mb-1">
            <label class="form-label d-flex justify-content-between">
              <span>Password</span>
              <button type="button" class="btn btn-link p-0 small" id="genPwd">Generate</button>
            </label>
            <div class="input-group">
              <input type="password" name="password" id="newPassword" class="form-control input-soft" required>
              <button type="button" class="btn btn-outline-secondary" id="togglePwdNew"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text">Peran awal akan otomatis <strong>Marketing</strong>.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

  @push('scripts')
  <script>
    (function(){
      const gen = document.getElementById('genPwd');
      const inp = document.getElementById('newPassword');
      const tog = document.getElementById('togglePwdNew');
      gen?.addEventListener('click', ()=>{
        const s = Math.random().toString(36).slice(-6) + Math.random().toString(36).toUpperCase().slice(-6);
        inp.value = s;
      });
      tog?.addEventListener('click', ()=>{
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        tog.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      });
    })();
  </script>
  @endpush
@endsection
