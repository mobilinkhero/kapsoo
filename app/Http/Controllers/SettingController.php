<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\KapsoService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected KapsoService $kapso, protected OpenAIService $openai) {}

    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.index', compact('settings'));
    }

    public function test(Request $request)
    {
        $section = $request->get('section', 'kapso');
        if ($section === 'ai') {
            return response()->json($this->openai->testConnection());
        }

        return response()->json($this->kapso->testConnection());
    }

    public function update(Request $request)
    {
        $section = $request->get('section', 'all');

        if ($section === 'kapso' || $section === 'all') {
            $data = $request->validate([
                'kapso_base_url' => 'nullable|url',
                'kapso_api_key' => 'nullable|string',
                'kapso_phone_number_id' => 'nullable|string',
            ]);
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        if ($section === 'ai' || $section === 'all') {
            $data = $request->validate([
                'openai_api_key' => 'nullable|string',
                'openai_model' => 'nullable|string|max:100',
                'openai_embedding_model' => 'nullable|string|max:100',
                'ai_enabled' => 'nullable',
                'ai_system_prompt' => 'nullable|string',
                'ai_handoff_confidence' => 'nullable|numeric|min:0|max:1',
            ]);
            foreach ($data as $key => $value) {
                if ($key === 'ai_enabled') {
                    continue;
                }
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            if (! $request->has('ai_enabled')) {
                Setting::updateOrCreate(['key' => 'ai_enabled'], ['value' => '0']);
            } else {
                Setting::updateOrCreate(['key' => 'ai_enabled'], ['value' => '1']);
            }
        }

        if ($request->has('test_mode')) {
            $result = $section === 'ai'
                ? $this->openai->testConnection()
                : $this->kapso->testConnection();

            return redirect()->route('settings.index')->with('test_result', $result)->with('test_section', $section);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved.')->with('saved_section', $section);
    }
}
