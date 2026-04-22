<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmToTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public array $tokens;

    public string $title;

    public string $body;

    public array $data;

    public ?string $image;

    public function __construct(array $tokens, string $title, string $body, array $data = [], ?string $image = null)
    {
        $this->tokens = array_values(array_filter(array_map(static function ($value) {
            $t = trim((string) $value);
            return $t !== '' ? $t : null;
        }, $tokens)));
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->image = $image !== null && trim((string) $image) !== '' ? $image : null;
    }

    public function handle(FcmService $fcm): void
    {
        foreach ($this->tokens as $token) {
            $fcm->sendPush(
                token: (string) $token,
                title: $this->title,
                body: $this->body,
                data: $this->data,
                image: $this->image
            );
        }
    }
}
