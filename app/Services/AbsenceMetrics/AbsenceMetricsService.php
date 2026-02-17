<?php

namespace App\Services\Attendance;

use Illuminate\Support\Facades\DB;

class AbsenceMetricsService
{
    public function byApprenticeId(int $apprenticeId)
    {
        $sql = "
    WITH daily AS (
      SELECT
        a.apprentice_id,
        rc.execution_date AS the_date,
        SUM(a.attendance_status_id = 2) AS absences_in_day
      FROM attendances a
      JOIN real_classes rc ON rc.id = a.real_class_id
      WHERE a.apprentice_id = :apprentice_id
      GROUP BY a.apprentice_id, rc.execution_date
    ),
    absent_days AS (
      SELECT apprentice_id, the_date
      FROM daily
      WHERE absences_in_day >= 1
    ),
    ranked AS (
      SELECT
        apprentice_id,
        the_date,
        ROW_NUMBER() OVER (PARTITION BY apprentice_id ORDER BY the_date) AS rn
      FROM absent_days
    ),
    streaks AS (
      SELECT
        apprentice_id,
        COUNT(*) AS streak_days
      FROM (
        SELECT
          apprentice_id,
          the_date,
          DATE_SUB(the_date, INTERVAL rn DAY) AS grp
        FROM ranked
      ) x
      GROUP BY apprentice_id, grp
    ),
    metrics AS (
      SELECT
        ad.apprentice_id,
        COUNT(*) AS total_absent_days,
        COALESCE(MAX(s.streak_days), 0) AS max_consecutive_absent_days
      FROM absent_days ad
      LEFT JOIN streaks s ON s.apprentice_id = ad.apprentice_id
      GROUP BY ad.apprentice_id
    )
    SELECT
      u.id AS apprentice_id,
      COALESCE(m.total_absent_days, 0) AS total_absent_days,
      COALESCE(m.max_consecutive_absent_days, 0) AS max_consecutive_absent_days,
      CASE
        WHEN COALESCE(m.max_consecutive_absent_days, 0) >= 3 OR COALESCE(m.total_absent_days, 0) >= 5 THEN 1
        ELSE 0
      END AS dropout_alert
    FROM users u
    LEFT JOIN metrics m ON m.apprentice_id = u.id
    WHERE u.id = :apprentice_id;
    ";

        $rows = DB::select($sql, ['apprentice_id' => $apprenticeId]);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Métricas de inasistencia del aprendiz obtenidas correctamente',
            'data' => $rows[0] ?? [
                'apprentice_id' => $apprenticeId,
                'total_absent_days' => 0,
                'max_consecutive_absent_days' => 0,
                'dropout_alert' => 0,
            ],
        ];
    }

    public function byFichaId(int $fichaId)
    {
        $sql = "
    WITH ficha_apprentices AS (
      SELECT u.id AS apprentice_id
      FROM users u
      WHERE u.ficha_id = :ficha_id
    ),
    daily AS (
      SELECT
        a.apprentice_id,
        rc.execution_date AS the_date,
        SUM(a.attendance_status_id = 2) AS absences_in_day
      FROM attendances a
      JOIN real_classes rc ON rc.id = a.real_class_id
      JOIN ficha_apprentices fa ON fa.apprentice_id = a.apprentice_id
      GROUP BY a.apprentice_id, rc.execution_date
    ),
    absent_days AS (
      SELECT apprentice_id, the_date
      FROM daily
      WHERE absences_in_day >= 1
    ),
    ranked AS (
      SELECT
        apprentice_id,
        the_date,
        ROW_NUMBER() OVER (PARTITION BY apprentice_id ORDER BY the_date) AS rn
      FROM absent_days
    ),
    streaks AS (
      SELECT
        apprentice_id,
        COUNT(*) AS streak_days
      FROM (
        SELECT
          apprentice_id,
          the_date,
          DATE_SUB(the_date, INTERVAL rn DAY) AS grp
        FROM ranked
      ) x
      GROUP BY apprentice_id, grp
    ),
    metrics AS (
      SELECT
        fa.apprentice_id,
        COALESCE((SELECT COUNT(*) FROM absent_days ad WHERE ad.apprentice_id = fa.apprentice_id), 0) AS total_absent_days,
        COALESCE((SELECT MAX(s.streak_days) FROM streaks s WHERE s.apprentice_id = fa.apprentice_id), 0) AS max_consecutive_absent_days
      FROM ficha_apprentices fa
    )
    SELECT
      fa.apprentice_id,
      m.total_absent_days,
      m.max_consecutive_absent_days,
      CASE
        WHEN m.max_consecutive_absent_days >= 3 OR m.total_absent_days >= 5 THEN 1
        ELSE 0
      END AS dropout_alert
    FROM ficha_apprentices fa
    JOIN metrics m ON m.apprentice_id = fa.apprentice_id
    ORDER BY fa.apprentice_id;
    ";

        $rows = DB::select($sql, ['ficha_id' => $fichaId]);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Métricas de inasistencia por ficha obtenidas correctamente',
            'data' => $rows,
        ];
    }
}
