<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResourceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $action,
        public string $subjectType, 
        public int|string $subjectId, 
        public int $actorUserId, 
        public ?string $subjectLabel = null
    ) {}
}