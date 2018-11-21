<?php

namespace App\Jobs;

use App\Models\AudioItem;

class FinalizeAudioItemJob extends Job
{
    public $tries = 10;

    /**
     * The number of seconds the job can run before timing out.
     * @var int
     */
    public $timeout = 300;

    protected $audioItem;

    /**
     * Create a new job instance.
     *
     * @param AudioItem $audioItem
     * @return void
     */
    public function __construct(AudioItem $audioItem) {
        $this->allOnQueue('finalize-audio-item');
        $this->audioItem = $audioItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->audioItem->process();
    }
}