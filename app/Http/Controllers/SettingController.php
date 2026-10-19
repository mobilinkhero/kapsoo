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

    public function update(Request $request)
    {
        $data = $request->validate([
            'kapso_base_url' => 'nullable|url',
            'kapso_api_key' => 'nullable|string',
            'kapso_phone_number_id' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}