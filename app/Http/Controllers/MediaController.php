<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MediaController extends Controller
{
    public function download(Request $request)
    {
        $url = $request->query('url');
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid URL');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            abort(400, 'Invalid scheme');
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->get($url);

        if ($response->failed()) {
            abort(404, 'Media not found');
        }

        $contentType = $response->header('Content-Type') ?: 'application/octet-stream';
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'file');
        if ($filename === '' || $filename === '/') {
            $filename = 'file';
        }

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($response->body()),
        ]);
    }
}
