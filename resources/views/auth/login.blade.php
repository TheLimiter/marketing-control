@extends('layouts.guest')
@section('title', 'Masuk')

{{-- Place custom styles in a separate file or a style block --}}
@section('styles')
<style>
    .auth-card {
        max-width: 900px;
        border-radius: 1.5rem;
    }
    .auth-left {
        background: linear-gradient(135deg, var(--bs-primary-bg-subtle, #e0f7fa), var(--bs-info-bg-subtle, #fce4ec));
    }
    [data-theme="dark"] .auth-left {
        background: linear-gradient(135deg, var(--bs-primary-text-emphasis, #333d4e), var(--bs-info-text-emphasis, #3a3249));
        color: #ddd;
    }
    .brand-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        border-radius: 50%;
        background-color: var(--bs-primary-bg-subtle, #e0f7fa);
        font-size: 2.25rem;
        color: var(--bs-primary, #0d6efd);
    }
    .h-page {
        font-size: 1.75rem;
        font-weight: 600;
    }
    .subtle {
        color: var(--bs-secondary-color, #6c757d);
    }
    .input-soft {
        border-radius: 0.75rem;
    }
    .btn.round {
        border-radius: 50rem;
    }
    /* Ensures the row stays centered on small screens */
    .row.g-0 {
        justify-content: center;
    }
</style>
@endsection

@section('content')
<div class="card auth-card overflow-hidden shadow-lg">
    <div class="row g-0">
        {{-- Left side: branding/teaser --}}
        <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 auth-left">
            <div>
                <div class="brand-badge mb-3"><i class="bi bi-kanban"></i></div>
                <div class="h4 fw-bold mb-1">{{ config('app.name', 'Marketing Control') }}</div>
                <div class="subtle">Pipeline • Modul • Tagihan</div>
            </div>

            <div class="small text-muted">
                <div class="fw-semibold mb-1">Selamat datang 👋</div>
                <ul class="mb-0 ps-3">
                    <li>Pemeliharaan sekolah, kelola modul, dan tagihan.</li>
                    <li>Segala aktivitas terpantau dan tercatat rapi.</li>
                    <li>Aplikasi masih belum sempurna dan fitur masih belum maksimal.</li>
                </ul>
            </div>

            <div class="text-muted small">&copy; {{ date('Y') }} — IDS Rumah Pendidikan Indonesia</div>
        </div>

        {{-- Right side: form --}}
        <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
            <div class="mb-4 text-center">
                <div class="h-page mb-0">Masuk</div>
                <div class="subtle">Masuk ke akun Anda</div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger small elev-1">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="d-grid gap-3 form--soft">
                @csrf
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control input-soft" value="{{ old('email') }}" required autofocus>
                </div>

                <div>
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Password</span>
                        <button class="btn btn-link p-0 small text-decoration-none" type="button" id="togglePwd">
                            <i class="bi bi-eye"></i> tampilkan
                        </button>
                    </label>
                    <input type="password" name="password" id="password" class="form-control input-soft" required>
                    <div id="capsNote" class="form-text text-danger d-none mt-1">
                        <i class="bi bi-exclamation-triangle-fill"></i> Caps Lock aktif
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                    <div class="small text-muted text-center">
                        <p class="mb-0">Tips: Hubungi Admin</p>
                        <p class="mb-0">seputar akun dan bantuan</p>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg round mt-3">Masuk</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // show/hide password and caps lock note
    (function(){
        const btn = document.getElementById('togglePwd');
        const pwd = document.getElementById('password');
        const caps = document.getElementById('capsNote');

        if (btn && pwd) {
            btn.addEventListener('click', () => {
                const show = pwd.type === 'password';
                pwd.type = show ? 'text' : 'password';
                btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i> sembunyikan' : '<i class="bi bi-eye"></i> tampilkan';
            });
        }

        if (pwd && caps) {
            pwd.addEventListener('keyup', (e) => {
                const on = e.getModifierState && e.getModifierState('CapsLock');
                caps.classList.toggle('d-none', !on);
            });
        }
    })();
</script>
@endpush
@endsection
