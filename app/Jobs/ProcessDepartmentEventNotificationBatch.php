<?php

namespace App\Jobs;

use App\Services\DepartmentEventNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDepartmentEventNotificationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $batchId) {}

    public function handle(DepartmentEventNotificationService $service): void
    {
        $service->processPending(10, $this->batchId);
    }
}
