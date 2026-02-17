<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\ResourceChanged;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\RealClass;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminsOnCrud
{
    protected array $excludedSubjects = [
        Attendance::class,
        RealClass::class,
    ];

    public function handle(ResourceChanged $event): void
    {
        Log::info('NotifyAdminsOnCrud fired', [
            'action' => $event->action,
            'subjectType' => $event->subjectType,
            'subjectId' => $event->subjectId,
        ]);


        if (in_array($event->subjectType, $this->excludedSubjects, true)) {
            return;
        }

        $adminIds = User::role('Administrador')->pluck('id')->all();
        if (empty($adminIds)) {
            return;
        }

        $typeKey = match ($event->action) {
            'crear' => 'resource_created',
            'actualizar' => 'resource_updated',
            'eliminar' => 'resource_deleted',
            default => 'resource_updated',
        };

        $notificationTypeId = NotificationType::where('key', $typeKey)->value('id');
        if (!$notificationTypeId) {
            return;
        }

        $label = $event->subjectLabel ?? class_basename($event->subjectType);

        $actionText = match ($event->action) {
            'crear' => 'creado',
            'actualizar' => 'actualizado',
            'eliminar' => 'eliminado',
            default => 'modificado',
        };

        $notification = Notification::create([
            'notification_type_id' => $notificationTypeId,
            'title' => "Se ha {$actionText} un registro de {$label}",
            'content' => "Se ha {$actionText} el registro de {$label} con ID {$event->subjectId}.",
            'modelable_type' => $event->subjectType,
            'modelable_id' => $event->subjectId,
        ]);

        $payload = [];
        foreach ($adminIds as $id) {
            $payload[$id] = ['read_at' => null, 'role_code' => "ADMIN"];
        }
        $notification->users()->syncWithoutDetaching($payload);

        Log::info('Before broadcast NotificationCreated', [
            'notification_id' => $notification->id,
        ]);

        broadcast(new NotificationCreated($notification, $adminIds, "ADMIN"));

        Log::info('After broadcast NotificationCreated', [
            'notification_id' => $notification->id,
        ]);


        Log::info('Broadcast NotificationCreated', [
            'notification_id' => $notification->id,
            'admins' => $adminIds,
        ]);
    }
}
