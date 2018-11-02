<?php

namespace App\Jobs;

use App\Models\TTSItem;

class TTSJob extends Job
{
    protected $ttsItem;

    /**
     * Create a new job instance.
     *
     * @param TTSItem $ttsItem
     * @return void
     */
    public function __construct(TTSItem $ttsItem) {
        $this->ttsItem = $ttsItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->ttsItem->generateAudio();
    }
}
