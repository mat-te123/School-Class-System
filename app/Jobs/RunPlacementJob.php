<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PenjurusanPlacementService;

class RunPlacementJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $periodeId;

    /**
     * Create a new job instance.
     */
    public function __construct($periodeId)
    {
        $this->periodeId = $periodeId;
    }

    /**
     * Execute the job.
     */
    public function handle(PenjurusanPlacementService $service): void
    {
        $service->calculatePlacement($this->periodeId);
    }
}
