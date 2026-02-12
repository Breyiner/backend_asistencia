<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\ResourceChanged;
use App\Models\Apprentice;
use App\Models\Attendance;
use App\Models\Ficha;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\RealClass;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyGestorOnAttendanceOrRealClass
{
    public function handle(ResourceChanged $event): void
    {
        // Solo aplica para Attendance y RealClass
        if (!in_array($event->subjectType, [Attendance::class, RealClass::class], true)) {
            return;
        }

        // IMPORTANTE: tus acciones son 'crear' / 'actualizar' / 'eliminar'
        $allowed =
            ($event->subjectType === Attendance::class && $event->action === 'updated') ||
            ($event->subjectType === RealClass::class && $event->action === 'created');

        if (!$allowed) {
            return;
        }

        $typeKey = match (true) {
            $event->subjectType === Attendance::class && $event->action === 'updated' => 'resource_updated',
            $event->subjectType === RealClass::class  && $event->action === 'created'      => 'resource_created',
        };

        $notificationType = NotificationType::where('key', $typeKey)->first();
        if (!$notificationType) {
            return;
        }

        // Determinar ficha_id y (opcional) apprenticeName
        $fichaId = null;
        $apprenticeName = null;

        if ($event->subjectType === Attendance::class) {
            $attendance = Attendance::select(['id', 'apprentice_id'])
                ->whereKey($event->subjectId)
                ->first();

            if (!$attendance?->apprentice_id) {
                return;
            }

            // CORRECCIÓN: Apprentice = users, NO existe user_id, carga profile directo
            $apprentice = Apprentice::with(['profile'])
                ->select(['id', 'ficha_id'])  // Solo campos que existen en users
                ->find($attendance->apprentice_id);

            if (!$apprentice?->ficha_id) {
                return;
            }

            $fichaId = $apprentice->ficha_id;

            $apprenticeName = $apprentice?->profile
                ? trim($apprentice->profile->first_name . ' ' . $apprentice->profile->last_name)
                : 'El aprendiz';
        }

        if ($event->subjectType === RealClass::class) {
            $realClass = RealClass::with([
                'scheduleSession.schedule.fichaTerm'
            ])->select(['id'])->find($event->subjectId);

            $fichaId = $realClass?->scheduleSession?->schedule?->fichaTerm?->ficha_id;

            if (!$fichaId) {
                return;
            }
        }

        $ficha = Ficha::select(['id', 'ficha_number', 'gestor_id'])->find($fichaId);
        if (!$ficha?->gestor_id) {
            return;
        }

        // Valida que el gestor tenga el rol esperado (si tu sistema lo usa así)
        $gestorId = User::role('Gestor de Fichas')
            ->whereKey($ficha->gestor_id)
            ->value('id');

        if (!$gestorId) {
            return;
        }

        $title = $event->subjectType === Attendance::class
            ? 'Inasistencia registrada'
            : 'Clase real creada';

        $content = $event->subjectType === Attendance::class
            ? "Se registró una inasistencia de {$apprenticeName} para la ficha {$ficha->ficha_number}."
            : "Se registró una clase real para la ficha {$ficha->ficha_number}.";

        $notification = Notification::create([
            'notification_type_id' => $notificationType->id,
            'title' => $title,
            'content' => $content,
            'role_code' => 'GESTOR_FICHAS',
            'modelable_type' => $event->subjectType,
            'modelable_id' => $event->subjectId,
        ]);

        $notification->users()->syncWithoutDetaching([
            $gestorId => ['read_at' => null, 'role_code' => 'GESTOR_FICHAS'],
        ]);

        // Broadcast SOLO al gestor
        broadcast(new NotificationCreated($notification, [$gestorId], 'GESTOR_FICHAS'));
    }
}
