<?php

namespace App\Events;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Notification $notification,
        public array $userIds,
        public string $roleCode,
    ) {}

    public function broadcastOn(): array
    {
        // Un canal por usuario admin
        return collect($this->userIds)
            ->map(fn ($id) => new PrivateChannel("users.{$id}"))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        $humanized = Carbon::parse($this->notification->created_at)
            ->diffForHumans();

        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'type' => $this->notification->notification_type_id,
            'role_code' => $this->roleCode,
            'read_at' => null,
            'created_at' => $this->notification->created_at->toISOString(),
            'created_at_human' => $humanized,
        ];
    }
}