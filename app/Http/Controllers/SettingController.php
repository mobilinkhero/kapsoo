<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\KapsoService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.index', compact('settings'));
    }

    public function test(KapsoService $kapso)
    {
        return response()->json($kapso->testConnection());
    }

    public function update(Request $request, KapsoService $kapso)
    {
        $data = $request->validate([
            'kapso_base_url' => 'nullable|url',
            'kapso_api_key' => 'nullable|string',
            'kapso_phone_number_id' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'openai_model' => 'nullable|string|max:100',
            'openai_embedding_model' => 'nullable|string|max:100',
            'ai_enabled' => 'nullable',
            'ai_system_prompt' => 'nullable|string',
            'ai_handoff_confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        // Ensure ai_enabled is persisted even when checkbox unchecked
        if (! $request->has('ai_enabled')) {
            Setting::updateOrCreate(['key' => 'ai_enabled'], ['value' => '0']);
        } else {
            Setting::updateOrCreate(['key' => 'ai_enabled'], ['value' => '1']);
        }

        if ($request->has('test_mode')) {
            $result = $kapso->testConnection();

            return redirect()->route('settings.index')->with('test_result', $result);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
