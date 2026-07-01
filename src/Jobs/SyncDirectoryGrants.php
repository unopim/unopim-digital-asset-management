<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Kept for backward compatibility with already-queued jobs; now a no-op. */
class SyncDirectoryGrants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $roleId,
        protected array $directoryIds,
    ) {}

    public function handle(): void {}
}
