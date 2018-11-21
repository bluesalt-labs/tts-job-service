<?php

namespace App\Jobs;

use App\Models\RequestItem;
use App\Models\AudioItemPart;

class GenerateAudioItemPartJob extends Job
{
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     * @var int
     */
    //public $timeout = 2000;

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
        // todo
    }
}