<?php

namespace App\Http\Controllers;

use App\Models\Conversation;

class PipelineController extends Controller
{
    public function index()
    {
        $stages = ['new', 'qualified', 'negotiating', 'won', 'lost'];

        $grouped = Conversation::with('contact', 'latestMessage')
            ->select('conversations.*')
            ->get()
            ->groupBy(fn ($c) => $c->lead_stage ?: 'new');

        $columns = [];
        foreach ($stages as $stage) {
            $columns[$stage] = $grouped->get($stage, collect());
        }

        $total = Conversation::count();
        $won = Conversation::where('lead_stage', 'won')->count();
        $conversionRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

        $counts = [];
        foreach ($stages as $stage) {
            $counts[$stage] = Conversation::where('lead_stage', $stage)->count();
        }

        return view('pipeline.index', compact('columns', 'stages', 'counts', 'total', 'won', 'conversionRate'));
    }
}
