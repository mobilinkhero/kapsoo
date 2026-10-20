<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CronController extends Controller
{
    /**
     * Endpoint hit by external cron services (e.g. cron-job.org) every minute.
     * Protected by a secret token stored in the `cron_token` setting.
     */
    public function run(Request $request)
    {
        $token = (string) $request->query('token', '');
        $expected = $this->token();

        if ($expected === '' || ! hash_equals($expected, $token)) {
            return response('Forbidden', 403);
        }

        // Prevent overlapping runs if the previous invocation is still processing
        $lock = Cache::lock('cron-run', 120);
        if (! $lock->get()) {
            return response('Already running', 429);
        }

        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();
        } finally {
            $lock->release();
        }

        return response($output ?: 'OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * The secret token used to authorize external cron calls.
     * Auto-generated on first use and persisted in the settings table.
     */
    public static function token(): string
    {
        $value = Setting::where('key', 'cron_token')->value('value');
        if (empty($value)) {
            $value = Str::random(40);
            Setting::updateOrCreate(['key' => 'cron_token'], ['value' => $value]);
        }

        return (string) $value;
    }

    /**
     * The full URL to paste into cron-job.org.
     */
    public static function url(): string
    {
        return url('/cron/run?token='.self::token());
    }
}