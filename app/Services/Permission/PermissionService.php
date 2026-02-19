<?php

namespace App\Services\Permission;

use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * Servicio de lógica de negocio para la consulta de permisos del sistema.
 *
 * Solo expone un método de lectura (select) ya que los permisos son datos de
 * configuración definidos en seeders; no se crean ni eliminan desde la API.
 * El método retorna permisos agrupados por 'group' para poblar el modal de
 * asignación de permisos a roles en el panel de administración.
 */
class PermissionService
{
    /**
     * Retorna todos los permisos del guard 'web' agrupados por categoría.
     *
     * Soporta búsqueda opcional por display_name, name o group (parámetro 'q').
     * La estructura agrupada facilita la renderización del modal de permisos
     * sin que el frontend tenga que agrupar manualmente.
     *
     * @return array  data: objeto con claves = grupo, valor = array de permisos.
     */
    public function select()
    {
        // Selecciona solo campos necesarios; guard_name se incluye como contexto
        // aunque ya se filtra por 'web' (útil si el frontend lo necesita mostrar).
        $query = Permission::query()
            ->select(['id', 'name', 'display_name', 'group', 'guard_name'])
            // Solo permisos del guard 'web'; excluye guards de API u otros contextos.
            ->where('guard_name', 'web')
            // Doble orderBy: primero agrupa visualmente por categoría, luego alfabético dentro del grupo.
            ->orderBy('group')
            ->orderBy('display_name');

        // Filtro de búsqueda opcional: permite buscar en nombre técnico, nombre legible o grupo.
        if (request()->filled('q')) {
            $q = request('q');

            // Closure para encapsular el OR en un grupo de condiciones (evita AND/OR conflictos).
            $query->where(function ($sub) use ($q) {
                $sub->where('display_name', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('group', 'like', "%{$q}%");
            });
        }

        $items = $query->get();

        // groupBy('group'): genera un objeto con claves = nombre del grupo (ej: "Fichas", "Aprendices").
        // map + values(): descarta las claves numéricas del subarray para que el frontend reciba arrays limpios.
        $grouped = $items->groupBy('group')->map(function (Collection $perms) {
            return $perms->values();
        });

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Permisos obtenidos con éxito',
            'data' => $grouped,
        ];
    }
}
