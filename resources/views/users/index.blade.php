@extends('layouts.app')

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout for User Management
   ========================================================= */
:root {
    --border-color: #e5e7eb;
    --header-bg: #f9fafb;
    --row-hover-bg: #f9fafb;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --radius: 8px;
}

.table-container {
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow-x: auto;
    background-color: #fff;
}

.static-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
}

.static-table th,
.static-table td {
    padding: 12px 15px;
    vertical-align: middle;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.static-table th {
    background-color: var(--header-bg);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--text-secondary);
    position: sticky;
    top: 0;
    z-index: 1;
}

.static-table tbody tr:last-child td {
    border-bottom: none;
}

.static-table tbody tr:hover {
    background-color: var(--row-hover-bg);
}

/* Penentuan Lebar Kolom */
.col-user     { width: 35%; }
.col-role     { width: 15%; }
.col-status   { width: 15%; }
.col-joined   { width: 20%; }
.col-aksi     { width: 15%; text-align: center; }

/* Avatar */
.avatar-circle{
    width:36px; height:36px; border-radius:9999px;
    display:inline-flex; align-items:center; justify-content:center;
    background:#eef2ff; color:#4338ca; font-weight:600;
    font-size: 0.85rem;
}

/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

/* Badge Role */
.badge-role {
    background-color: #eef2ff;
    color: #4338ca;
    padding: .3em .65em;
    font-weight: 600;
    border-radius: 999px;
    font-size: 0.8rem;
}


/* Empty state */
.empty-state-cell {
    text-align: center;
    padding: 40px;
}
</style>
@endpush

@section('content')
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <div class="h-page">Manajemen Pengguna</div>
      <div class="subtle">Kelola akun, peran, dan status akses</div>
    </div>
    @role('admin')
      <button class="btn btn-primary round" data-bs-toggle="modal" data-bs-target="#userCreateModal">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
      </button>
    @endrole
  </div>

  {{-- Flash Messages --}}
  @if(session('ok'))<div class="alert alert-success elev-1">{{ session('ok') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger elev-1">{{ session('error') }}</div>@endif

  {{-- Toolbar filter & cari --}}
  <form method="GET" action="{{ route('admin.users.index') }}" class="card card-toolbar mb-4">
    <div class="toolbar w-100">
      <div class="field flex-grow-1">
        <label>Cari nama / email</label>
        <input type="search" name="q" value="{{ request('q') }}" class="input-soft" placeholder="Ketik kata kunci">
      </div>
      <div class="field" style="min-width: 180px;">
        <label>Role</label>
        <select name="role" class="select-soft" onchange="this.form.submit()">
          <option value="">— Semua —</option>
          @foreach($roles as $role)
            <option value="{{ $role }}" @selected(request('role')===$role)>{{ ucfirst($role) }}</option>
          @endforeach
        </select>
      </div>
      <div class="field" style="min-width: 180px;">
        <label>Status</label>
        <select name="status" class="select-soft" onchange="this.form.submit()">
          <option value="">— Semua —</option>
          <option value="1" @selected(request('status')==='1')>Aktif</option>
          <option value="0" @selected(request('status')==='0')>Nonaktif</option>
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
    <div class="table-container">
        <table class="static-table">
            <thead>
                <tr>
                    <th class="col-user">Pengguna</th>
                    <th class="col-role">Role</th>
                    <th class="col-status">Status</th>
                    <th class="col-joined">Bergabung Sejak</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    @php
                        $roleNames = $u->roles->pluck('name');
                    @endphp
                    <tr>
                        <td class="col-user">
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
                                <div class="fw-semibold ellipsis-wrapper">{{ $u->name }}</div>
                                <div class="small-muted ellipsis-wrapper">{{ $u->email }}</div>
                              </div>
                            </div>
                        </td>
                        <td class="col-role">
                            @forelse($roleNames as $rn)
                                <span class="badge-role">{{ ucfirst($rn) }}</span>
                            @empty
                                <span class="small-muted">&mdash;</span>
                            @endforelse
                        </td>
                        <td class="col-status">
                            <span class="badge rounded-pill {{ $u->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $u->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="col-joined">
                            <div class="small-muted ellipsis-wrapper" title="{{ $u->created_at->format('d M Y, H:i') }}">
                                {{ $u->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td class="col-aksi">
                            @role('admin')
                                @if(auth()->id() !== $u->id)
                                    <div class="dropdown">
                                       <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">
                                           Aksi <i class="bi bi-chevron-down"></i>
                                       </button>
                                       <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#userRoleModal"
                                                    data-user-id="{{ $u->id }}"
                                                    data-user-name="{{ $u->name }}"
                                                    data-user-role="{{ $roleNames->first() }}"
                                                    data-form-action="{{ route('admin.users.update', $u) }}">
                                                    <i class="bi bi-shield-lock me-2"></i>Ubah Role
                                                </button>
                                            </li>
                                            <li>
                                               <form action="{{ route('admin.users.toggle-status', $u) }}" method="post" onsubmit="return confirm('Yakin ingin {{ $u->active ? 'menonaktifkan' : 'mengaktifkan' }} pengguna ini?')">
                                                   @csrf
                                                   <button type="submit" class="dropdown-item text-{{ $u->active ? 'danger' : 'success' }}">
                                                       <i class="bi bi-{{ $u->active ? 'person-dash' : 'person-check' }} me-2"></i>{{ $u->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                   </button>
                                               </form>
                                            </li>
                                       </ul>
                                   </div>
                                @else
                                    <span class="small-muted fst-italic">Ini Anda</span>
                                @endif
                            @endrole
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state-cell">
                            Belum ada pengguna.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
  </div>

  @role('admin')
  {{-- Modal: Tambah Pengguna --}}
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

  {{-- Modal: Ganti Role --}}
  <div class="modal fade" id="userRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="userRoleForm" method="post" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Role untuk <span id="userNameRole"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body form--soft">
                <div class="mb-2">
                    <label class="form-label">Pilih Role</label>
                    <select name="role" id="userRoleSelect" class="form-select select-soft">
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
  </div>
  @endrole

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script untuk modal tambah pengguna
    const genBtn = document.getElementById('genPwd');
    const newPwdInput = document.getElementById('newPassword');
    const toggleBtn = document.getElementById('togglePwdNew');

    genBtn?.addEventListener('click', () => {
        const s = Math.random().toString(36).slice(-6) + Math.random().toString(36).toUpperCase().slice(-6);
        if (newPwdInput) newPwdInput.value = s;
    });

    toggleBtn?.addEventListener('click', () => {
        if (!newPwdInput) return;
        const show = newPwdInput.type === 'password';
        newPwdInput.type = show ? 'text' : 'password';
        toggleBtn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    // Script untuk modal ganti role
    const roleModal = document.getElementById('userRoleModal');
    if (roleModal) {
        roleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userName = button.getAttribute('data-user-name');
            const userRole = button.getAttribute('data-user-role');
            const formAction = button.getAttribute('data-form-action');

            const modalTitle = roleModal.querySelector('#userNameRole');
            const roleForm = roleModal.querySelector('#userRoleForm');
            const roleSelect = roleModal.querySelector('#userRoleSelect');

            modalTitle.textContent = userName;
            roleForm.action = formAction;
            roleSelect.value = userRole;
        });
    }
});
</script>
@endpush

