<?php

namespace App\Jobs;

use App\Models\RequestItem;

class ProcessRequestItemJob extends Job
{
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     * @var int
     */
    //public $timeout = 2000;

    protected $requestItem;

    /**
     * Create a new job instance.
     *
     * @param RequestItem $requestItem
     * @return void
     */
    public function __construct(RequestItem $requestItem) {
        $this->allOnQueue('request-item');
        $this->requestItem = $requestItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->requestItem->process();
    }

}
