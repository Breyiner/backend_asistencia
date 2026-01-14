<?php

namespace App\Listeners;

use App\Events\ResourceChanged;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\RealClass;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminsOnCrud implements ShouldQueue
{
    use InteractsWithQueue;

    protected array $excludedSubjects = [
        Attendance::class,
        RealClass::class,
    ];

    public function handle(ResourceChanged $event): void
    {
        if (in_array($event->subjectType, $this->excludedSubjects, true)) {
            return;
        }

        $adminIds = User::role('Administrador')->pluck('id')->all();

        if (empty($adminIds)) {
            return;
        }

        $typeKey = match ($event->action) {
            'crear' => 'crud_create',
            'actualizar' => 'crud_update',
            'eliminar' => 'crud_delete',
            default => 'crud_update',
        };

        $notificationTypeId = NotificationType::where('key', $typeKey)->value('id');

        if (!$notificationTypeId) {
            return;
        }

        $label = $event->subjectLabel ?? class_basename($event->subjectType);

        $title = "Se realizó una acción sobre {$label}";
        $content = "Se realizó la acción '{$event->action}' sobre {$label} (ID: {$event->subjectId}).";

        $notification = Notification::create([
            'notification_type_id' => $notificationTypeId,
            'title' => $title,
            'content' => $content,
            'modelable_type' => $event->subjectType,
            'modelable_id' => $event->subjectId,
        ]);

        $payload = [];
        foreach ($adminIds as $id) {
            $payload[$id] = ['read_at' => null];
        }

        $notification->users()->syncWithoutDetaching($payload);
    }
}