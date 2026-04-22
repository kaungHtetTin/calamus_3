<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmToTopic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public string $topic;

    public string $title;

    public string $body;

    public array $data;

    public ?string $image;

    public function __construct(string $topic, string $title, string $body, array $data = [], ?string $image = null)
    {
        $this->topic = trim($topic);
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->image = $image !== null && trim((string) $image) !== '' ? $image : null;
    }

    public function handle(FcmService $fcm): void
    {
        if ($this->topic === '') {
            return;
        }

        $fcm->sendTopic(
            topic: $this->topic,
            title: $this->title,
            body: $this->body,
            data: $this->data,
            image: $this->image
        );
    }
}

