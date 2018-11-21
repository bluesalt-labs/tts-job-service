<?php

namespace App\Jobs;

use App\Models\AudioItemPart;

class GenerateAudioItemPartJob extends Job
{
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     * @var int
     */
    public $timeout = 30;

    protected $audioItemPart;

    /**
     * Create a new job instance.
     *
     * @param AudioItemPart $audioItemPart
     * @return void
     */
    public function __construct(AudioItemPart $audioItemPart) {
        $this->allOnQueue('audio-item-part');
        $this->audioItemPart = $audioItemPart;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->audioItemPart->process();
    }
}