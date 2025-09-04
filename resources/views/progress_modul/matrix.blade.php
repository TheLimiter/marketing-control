@extends('layouts.app')

@php
    // Nilai aman dari controller/request
    $search   = $search ?? request('q','');
    $status   = request('status');
    $moduleId = request('module_id');
@endphp

@section('content')
    {{-- Header Halaman --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Progress Modul</div>
            <div class="subtle">Matriks checklist 1–9 per sekolah</div>
        </div>
        <a href="{{ route('progress.export') }}" class="btn btn-ghost round">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari Sekolah</label>
                <input type="text" name="q" value="{{ $search }}" class="input-soft" placeholder="Ketik nama sekolah…">
            </div>

            <div class="field" style="min-width:200px">
                <label>Modul</label>
                <select name="module_id" class="select-soft">
                    <option value="">Semua modul</option>
                    @foreach($modules as $m)
                        <option value="{{ $m->id }}" {{ (string)$moduleId===(string)$m->id ? 'selected' : '' }}>
                            {{ $m->kode ?? $m->nama ?? ('Modul #'.$m->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:200px">
                <label>Status</label>
                <select name="status" class="select-soft">
                    <option value="">Semua status</option>
                    <option value="done" {{ $status==='done' ? 'selected' : '' }}>Sudah ceklis</option>
                    <option value="todo" {{ $status==='todo' ? 'selected' : '' }}>Belum ceklis</option>
                </select>
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Terapkan
                </button>
                @if(request()->hasAny(['q','module_id','status']) && (request('q')||request('module_id')||request('status')))
                    <a href="{{ route('progress.matrix') }}" class="btn btn-ghost round">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Matriks Utama --}}
    <div class="card p-0">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div class="h-section">
                <i class="bi bi-grid-3x3-gap"></i><span>Matriks Progress</span>
            </div>
            <div class="text-muted small">Checklist 1–9 per sekolah</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                {{-- Komponen matriks milikmu --}}
                <x-anima.matrix :modules="$modules" :schools="$schools" :grid="$grid" />
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Gunakan filter di atas untuk fokus ke modul/status tertentu.</small>
            <a href="{{ route('progress.export') }}" class="btn btn-sm btn-ghost round">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
        </div>
    </div>
@endsection
