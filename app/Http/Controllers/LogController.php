<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('viewAny', ActivityLog::class);

        $q = ActivityLog::with('user')
            ->when($r->filled('entity'), fn($x)=>$x->where('entity_type',$r->entity))
            ->when($r->filled('action'), fn($x)=>$x->where('action',$r->action))
            ->when($r->filled('uid'),    fn($x)=>$x->where('user_id',$r->uid))
            ->orderByDesc('id');

        $logs = $q->paginate(20)->withQueryString();
        return view('logs.index', compact('logs'));
    }

    // user biasa: hanya log miliknya
    public function mine(Request $r)
    {
        $logs = ActivityLog::with('user')
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
