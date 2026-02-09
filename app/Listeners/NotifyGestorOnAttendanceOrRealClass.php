<?php

namespace App\Listeners;

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

class NotifyGestorOnAttendanceOrRealClass implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ResourceChanged $event): void
    {
        if (!in_array($event->subjectType, [Attendance::class, RealClass::class], true)) {
            return;
        }

        $allowed = ($event->subjectType === Attendance::class && $event->action === 'updated')
            || ($event->subjectType === RealClass::class && $event->action === 'created');

        if (!$allowed) {
            return;
        }

        $typeKey = match (true) {
            $event->subjectType === Attendance::class && $event->action === 'updated' => 'attendance_recorded',
            $event->subjectType === RealClass::class  && $event->action === 'created' => 'real_class_created',
        };

        $notificationType = NotificationType::where('key', $typeKey)->first();
        if (!$notificationType) {
            return;
        }

        $apprenticeId = null;

        if ($event->subjectType === Attendance::class) {
            $apprenticeId = Attendance::whereKey($event->subjectId)->value('apprentice_id');
        } else {
            $apprenticeId = Attendance::where('real_class_id', $event->subjectId)->value('apprentice_id');
        }

        if (!$apprenticeId) {
            return;
        }

        $apprentice = Apprentice::with(['user.profile'])->find($apprenticeId);
        $apprenticeName = $apprentice?->user?->profile
            ? trim($apprentice->user->profile->first_name . ' ' . $apprentice->user->profile->last_name)
            : 'El aprendiz';

        $fichaId = Apprentice::whereKey($apprenticeId)->value('ficha_id');
        if (!$fichaId) {
            return;
        }

        $ficha = Ficha::select(['id', 'ficha_number', 'gestor_id'])->find($fichaId);
        if (!$ficha?->gestor_id) {
            return;
        }

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
            $gestorId => ['read_at' => null, 'role_code' => "GESTOR_FICHAS",],
        ]);
    }
}