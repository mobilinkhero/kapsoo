<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\KapsoService;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::orderByDesc('created_at')->paginate(20);

        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|string|max:20',
            'category' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        Template::create($data);

        return redirect()->route('templates.index')->with('success', 'Template created.');
    }

    public function sync(KapsoService $kapso)
    {
        $items = $kapso->listTemplates();
        $count = 0;
        foreach ($items as $item) {
            Template::updateOrCreate(
                ['kapso_template_id' => $item['id'] ?? null],
                [
                    'name' => $item['name'] ?? 'Unnamed',
                    'language' => $item['language'] ?? 'en_US',
                    'category' => $item['category'] ?? null,
                    'status' => $item['status'] ?? 'unknown',
                    'components' => $item['components'] ?? null,
                ]
            );
            $count++;
        }

        return redirect()->route('templates.index')->with('success', "Synced {$count} templates.");
    }
}
