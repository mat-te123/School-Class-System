<?php

namespace App\Jobs;

use App\Services\PenjurusanPlacementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunPlacementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
