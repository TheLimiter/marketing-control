<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $r)
    {
        $q = ActivityLog::with(['user','sekolah']);

        if ($r->filled('action')) {
            $q->where('action', $r->string('action'));
        }
        if ($r->filled('entity_type')) {
            $q->where('entity_type', $r->string('entity_type'));
        }
        if ($r->filled('school')) { // butuh kolom master_sekolah_id (Step A)
            $q->where('master_sekolah_id', $r->integer('school'));
        }
        if ($r->filled('date_from')) {
            $q->whereDate('created_at', '>=', $r->date('date_from'));
        }
        if ($r->filled('date_to')) {
            $q->whereDate('created_at', '<=', $r->date('date_to'));
        }

        $logs = $q->latest()->paginate(20)->withQueryString();

        $topActions = ActivityLog::selectRaw('action, count(*) as total')
            ->groupBy('action')->orderByDesc('total')->limit(10)->get();

        return view('activity.index', compact('logs','topActions'));
    }
}
