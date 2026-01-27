<?php

namespace App\Policies;

use App\Models\ScheduleSession;
use App\Models\User;

class RealClassPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        $actingPerms = request()?->attributes?->get('acting_role_permissions', []);
        $actingCode  = request()?->attributes?->get('acting_role_code');

        if (!in_array('real_classes.create', $actingPerms, true)) {
            return false;
        }

        if ($actingCode === 'ADMIN') {
            return true;
        }

        return null;
    }

    public function create(User $user, ScheduleSession $scheduleSession, ?int $instructorId = null): bool
    {
        $actingPerms = request()?->attributes?->get('acting_role_permissions', []);
        $actingCode  = request()?->attributes?->get('acting_role_code');

        if (!in_array('real_classes.create', $actingPerms, true)) {
            return false;
        }

        $fichaTerm = $scheduleSession->schedule?->fichaTerm;
        $ficha     = $fichaTerm?->ficha;

        if (!$fichaTerm || !$ficha) return false;
        if (!(bool) $fichaTerm->is_current) return false;

        if ($actingCode === 'INSTRUCTOR') {
            if ($instructorId === null) return false;
            if ((int) $instructorId !== (int) $scheduleSession->instructor_id) return false;

            return (int) $user->id === (int) $scheduleSession->instructor_id;
        }

        if ($actingCode === 'GESTOR_FICHAS') {
            return (int) $user->id === (int) $ficha->gestor_id;
        }
 
        return false;
    }
}