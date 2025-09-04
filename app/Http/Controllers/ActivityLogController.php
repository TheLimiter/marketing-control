<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $r)
    {
        $q = ActivityLog::query()->with('user');

        if ($r->filled('entity_type')) $q->where('entity_type', $r->entity_type);
        if ($r->filled('entity_id'))   $q->where('entity_id', $r->entity_id);
        if ($r->filled('action'))      $q->where('action','like','%'.$r->action.'%');
        if ($r->filled('user_id'))     $q->where('user_id', $r->user_id);

        $logs = $q->latest()->paginate(15)->withQueryString();
        return view('logs.index', compact('logs'));
    }
}
