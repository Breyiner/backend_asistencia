<?php

namespace App\Console\Commands;

use App\Events\NotificationCreated;
use App\Models\Apprentice;
use App\Models\Ficha;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Console\Command;

class NotifyGestorAdultApprentices extends Command
{
    protected $signature = 'apprentices:notify-adults';
    protected $description = 'Notifica al gestor cuando un aprendiz cumple 18 años (diario).';

    public function handle(): int
    {
        $adultDate = now()->toDateString();
        $birthDate = now()->subYears(18)->toDateString();

        $notificationType = NotificationType::where('key', 'apprentice_became_adult')->first();
        if (!$notificationType) {
            return self::SUCCESS;
        }

        $apprentices = Apprentice::query()
            ->select(['id', 'ficha_id'])
            ->with([
                'profile:id,user_id,first_name,last_name,birth_date'
            ])
            ->whereHas('profile', function ($q) use ($birthDate) {
                $q->whereDate('birth_date', $birthDate);
            })
            ->get();

        foreach ($apprentices as $apprentice) {
            if (!$apprentice->ficha_id) continue;

            $alreadyNotified = Notification::query()
                ->where('notification_type_id', $notificationType->id)
                ->where('modelable_type', Apprentice::class)
                ->where('modelable_id', $apprentice->id)
                ->whereDate('created_at', $adultDate)
                ->exists();

            if ($alreadyNotified) continue;

            $ficha = Ficha::select(['id', 'ficha_number', 'gestor_id'])->find($apprentice->ficha_id);
            if (!$ficha?->gestor_id) continue;

            $gestorId = User::role('Gestor de Fichas')
                ->whereKey($ficha->gestor_id)
                ->value('id');

            if (!$gestorId) continue;

            $apprenticeName = $apprentice->profile
                ? trim($apprentice->profile->first_name . ' ' . $apprentice->profile->last_name)
                : 'El aprendiz';

            $notification = Notification::create([
                'notification_type_id' => $notificationType->id,
                'title' => 'Aprendiz cumple mayoría de edad',
                'content' => "El aprendiz {$apprenticeName} cumplió 18 años (ficha {$ficha->ficha_number}).",
                'role_code' => 'GESTOR_FICHAS',
                'modelable_type' => Apprentice::class,
                'modelable_id' => $apprentice->id,
            ]);

            $notification->users()->syncWithoutDetaching([
                $gestorId => ['read_at' => null, 'role_code' => 'GESTOR_FICHAS'],
            ]);

            broadcast(new NotificationCreated($notification, [$gestorId], 'GESTOR_FICHAS'));
        }

        return self::SUCCESS;
    }
}