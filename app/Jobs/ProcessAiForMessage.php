<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiForMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $messageId) {}

    public function handle(AiService $ai): void
    {
        $message = Message::find($this->messageId);
        if (! $message) {
            return;
        }
        $ai->handleInbound($message);
    }
}
