<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar log aktivitas (audit trail).
     *
     * @param \Illuminate\Http\Request $r
     * @return \Illuminate\View\View
     */
    public function index(Request $r)
    {
        // Eager-load relasi user dan sekolah
        $q = ActivityLog::query()->with(['user', 'sekolah']);

        // Filter string
        if ($r->filled('action')) {
            $q->where('action', 'like', '%' . $r->input('action') . '%');
        }

        if ($r->filled('entity_type')) {
            $q->where('entity_type', $r->input('entity_type'));
        }

        if ($r->filled('entity_id')) {
            $q->where('entity_id', (int) $r->input('entity_id'));
        }

        if ($r->filled('user_id')) {
            $q->where('user_id', (int) $r->input('user_id'));
        }

        if ($r->filled('school')) {
            $q->where('master_sekolah_id', (int) $r->input('school'));
        }

        // Filter berdasarkan rentang tanggal
        if ($r->filled('from')) {
            $q->whereDate('created_at', '>=', $r->date('from'));
        }

        if ($r->filled('to')) {
            $q->whereDate('created_at', '<=', $r->date('to'));
        }

        // Ambil data dengan pagination, 25 item per halaman
        $logs = $q->latest()->paginate(25)->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
