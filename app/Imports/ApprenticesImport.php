<?php

namespace App\Imports;

use App\Models\Apprentice;
use App\Models\DocumentType;
use App\Models\Ficha;
use App\Models\Role;
use App\Rules\AlphaSpaces;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Importador de aprendices desde archivo Excel.
 *
 * Procesa un archivo .xlsx con datos de aprendices y los registra
 * en la base de datos junto con su perfil y rol correspondiente.
 *
 * Interfaces implementadas:
 * - ToCollection: recibe las filas como Collection de Laravel
 * - WithHeadingRow: usa la primera fila como nombres de columnas
 * - WithValidation: valida cada fila antes de procesarla
 * - WithChunkReading: lee el archivo en chunks para optimizar memoria
 * - SkipsOnFailure: continúa procesando si una fila falla validación
 * - SkipsEmptyRows: ignora filas completamente vacías
 *
 * Columnas esperadas en el Excel (fila 1 = headers):
 * - nombres: Nombres del aprendiz
 * - apellidos: Apellidos del aprendiz
 * - telefono: Teléfono (10 dígitos, opcional)
 * - tipo_documento: Acrónimo del tipo (CC, TI, CE, etc.)
 * - numero_documento: Número de documento (6-20 dígitos)
 * - email: Correo electrónico único
 * - fecha_nacimiento: Fecha de nacimiento (YYYY-MM-DD o serial de Excel)
 * - numero_ficha: Número de ficha existente en el sistema
 *
 * Uso en controlador:
 * $import = new ApprenticesImport();
 * Excel::import($import, $request->file('file'));
 * if (!empty($import->errors)) {
 *     return ResponseFormatter::error('Errores en importación', 422, $import->errors);
 * }
 */
class ApprenticesImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    SkipsOnFailure,
    SkipsEmptyRows
{
    /**
     * Acumula errores de validación de filas fallidas.
     *
     * Cada elemento contiene:
     * - fila: número de fila con error
     * - atributo: campo que falló
     * - errores: mensajes de error
     * - valores: valores de la fila
     *
     * @var array
     */
    public $errors = [];

    /**
     * Cache de fichas indexadas por número de ficha.
     * Estructura: ['2558971' => 3, '2558972' => 4, ...]
     * Evita consultas repetidas a la BD por cada fila.
     *
     * @var \Illuminate\Support\Collection
     */
    private $fichas;

    /**
     * Cache de tipos de documento indexados por acrónimo.
     * Estructura: ['CC' => 1, 'TI' => 2, 'CE' => 3, ...]
     * Evita consultas repetidas a la BD por cada fila.
     *
     * @var \Illuminate\Support\Collection
     */
    private $documentTypes;

    /**
     * Inicializa el importador precargando catálogos.
     *
     * Carga fichas y tipos de documento en memoria una sola vez
     * para evitar N+1 queries durante el procesamiento de filas.
     */
    public function __construct()
    {
        // Carga fichas: clave = ficha_number, valor = id
        $this->fichas = Ficha::pluck('id', 'ficha_number');

        // Carga tipos de documento: clave = acronym, valor = id
        $this->documentTypes = DocumentType::pluck('id', 'acronym');
    }

    /**
     * Maneja las filas que fallaron la validación.
     *
     * Se ejecuta por cada fila que no pasa las reglas de WithValidation.
     * Acumula los errores en $this->errors para retornarlos al controlador.
     *
     * @param Failure ...$failures Objetos con información del fallo
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'fila'      => $failure->row(),       // Número de fila en el Excel
                'atributo'  => $failure->attribute(), // Columna que falló
                'errores'   => $failure->errors(),    // Mensajes de error
                'valores'   => $failure->values(),    // Valores de la fila fallida
            ];
        }
    }

    /**
     * Prepara y transforma los datos de una fila antes de validarlos.
     *
     * Se ejecuta antes de WithValidation para normalizar datos.
     *
     * Transformaciones:
     * 1. Convierte serial de fecha de Excel a formato YYYY-MM-DD
     *    (Excel almacena fechas como números: 45678 → "2024-12-15")
     * 2. Convierte teléfono vacío a null para validación nullable
     *
     * @param array $row   Fila original del Excel
     * @param int   $index Número de fila (para referencia)
     * @return array Fila transformada lista para validar
     */
    public function prepareForValidation($row, $index)
    {
        // Convierte fecha serial de Excel a formato de fecha PHP
        // Excel guarda fechas como números (ej: 45678 = 15/12/2024)
        if (isset($row['fecha_nacimiento']) && is_numeric($row['fecha_nacimiento'])) {
            $row['fecha_nacimiento'] = Date::excelToDateTimeObject($row['fecha_nacimiento'])
                ->format('Y-m-d');
        }

        // Normaliza teléfono vacío a null para que la validación nullable funcione
        if (empty($row['telefono'])) {
            $row['telefono'] = null;
        }

        return $row;
    }

    /**
     * Define las reglas de validación para cada fila.
     *
     * Se ejecuta para cada fila del Excel antes de procesarla.
     *
     * @return array Reglas de validación de Laravel
     */
    public function rules(): array
    {
        return [
            // Nombres: obligatorio, texto, solo letras y espacios (regla personalizada)
            'nombres'          => ['required', 'string', new AlphaSpaces()],

            // Apellidos: obligatorio, texto, solo letras y espacios
            'apellidos'        => ['required', 'string', new AlphaSpaces()],

            // Teléfono: opcional, solo números, exactamente 10 dígitos
            'telefono'         => ['nullable', 'numeric', 'digits:10'],

            // Tipo de documento: obligatorio, debe existir en document_types por acrónimo
            'tipo_documento'   => ['required', 'string', 'exists:document_types,acronym'],

            // Número de documento: obligatorio, numérico, 6-20 dígitos, único en users
            'numero_documento' => ['required', 'numeric', 'digits_between:6,20', 'unique:users,document_number'],

            // Email: obligatorio, formato válido, único en users
            'email'            => ['required', 'email', 'unique:users,email'],

            // Fecha nacimiento: opcional, formato fecha, debe ser anterior a hoy
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],

            // Número de ficha: obligatorio, numérico, debe existir en fichas
            'numero_ficha'     => ['required', 'numeric', 'exists:fichas,ficha_number'],
        ];
    }

    /**
     * Define mensajes de error personalizados para las validaciones.
     *
     * @return array Mensajes personalizados por regla y campo
     */
    public function customValidationMessages()
    {
        return [
            'nombres.required'         => 'El :attribute es obligatorio.',
            'nombres.string'           => 'El :attribute debe ser texto.',
            'apellidos.required'       => 'El :attribute es obligatorio.',
            'telefono.numeric'         => 'El :attribute debe ser numérico.',
            'telefono.digits'          => 'El :attribute debe tener 10 dígitos.',
            'document_type.exists'     => 'Tipo de documento no existe',
            'numero_documento.unique'  => 'Este documento ya está registrado',
            'email.unique'             => 'Este correo ya está registrado',
            'numero_ficha.exists'      => 'La ficha no existe',
        ];
    }

    /**
     * Procesa la colección de filas válidas.
     *
     * Se ejecuta con las filas que pasaron la validación.
     * Cada fila crea: un Apprentice, su Profile y asigna el rol APRENDIZ.
     * Todo dentro de una transacción para garantizar integridad.
     *
     * @param Collection $rows Filas validadas del Excel
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // Envuelve la creación en transacción para atomicidad
            // Si falla cualquier paso, revierte toda la operación de esa fila
            DB::transaction(function () use ($row) {

                // Verifica nuevamente que los IDs existan en los catálogos
                // (protección extra por si los datos cambiaron durante el import)
                if (
                    !isset($this->documentTypes[$row['tipo_documento']]) ||
                    !isset($this->fichas[$row['numero_ficha']])
                ) {
                    return; // Salta esta fila silenciosamente
                }

                // Crea el registro principal del aprendiz
                $apprentice = Apprentice::create([
                    'document_type_id' => $this->documentTypes[$row['tipo_documento']], // ID del tipo de doc
                    'document_number'  => (string) $row['numero_documento'],            // Como string para preservar ceros
                    'email'            => $row['email'],
                    'password'         => null,  // Sin contraseña inicial (se establece al verificar email)
                    'status_id'        => 1,     // Estado activo por defecto
                    'ficha_id'         => $this->fichas[$row['numero_ficha']],          // ID de la ficha
                ]);

                // Crea el perfil del aprendiz (relación 1:1)
                $apprentice->profile()->create([
                    'first_name'       => $row['nombres'],
                    'last_name'        => $row['apellidos'],
                    'telephone_number' => $row['telefono'] ? (string) $row['telefono'] : null, // Null si vacío
                    'birth_date'       => $row['fecha_nacimiento'],
                ]);

                // Obtiene el ID del rol APRENDIZ
                $apprenticeRoleId = Role::idByCode('APRENDIZ');

                // Asigna el rol al aprendiz sin eliminar roles existentes
                $apprentice->roles()->syncWithoutDetaching([$apprenticeRoleId]);
            });
        }
    }

    /**
     * Define el tamaño del chunk para lectura del Excel.
     *
     * Lee 1000 filas a la vez para optimizar uso de memoria
     * en archivos grandes sin cargar todo en RAM.
     *
     * @return int Número de filas por chunk
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}