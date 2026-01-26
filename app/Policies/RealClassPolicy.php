<?php

namespace App\Policies;

use App\Models\ScheduleSession;
use App\Models\User;

class RealClassPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return null;
    }

    public function create(User $user, ScheduleSession $scheduleSession, int $instructorId) 
    {
        $fichaTerm = $scheduleSession->schedule?->fichaTerm;
        $ficha = $fichaTerm?->ficha;

        if (!$fichaTerm || !$ficha) return false;

        if (!(bool) $fichaTerm->is_current) return false;

        if ((int) $instructorId !== (int) $scheduleSession->instructor_id) return false;

        if ((int) $user->id === (int) $scheduleSession->instructor_id) return true;
        if ((int) $user->id === (int) $ficha->gestor_id) return true;             

        return false;
    }
}