<?php

namespace App\Imports;

use App\Models\Apprentice;
use App\Models\DocumentType;
use App\Models\Ficha;
use App\Rules\AlphaSpaces;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ApprenticesImport implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure
{

    public $errors = [];
    private $fichas;
    private $documentTypes;

    public function __construct()
    {
        $this->fichas = Ficha::pluck('id', 'ficha_number');
        $this->documentTypes = DocumentType::pluck('id', 'acronym');
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'fila' => $failure->row(),
                'atributo' => $failure->attribute(),
                'errores' => $failure->errors(),
                'valores' => $failure->values()
            ];
        }
    }

    public function prepareForValidation($row, $index)
    {
        if (is_numeric($row['fecha_nacimiento'])) {
            $row['fecha_nacimiento'] = Date::excelToDateTimeObject($row['fecha_nacimiento'])->format('Y-m-d');
        }
        return $row; 
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', new AlphaSpaces()],
            'apellidos' => ['required', 'string', new AlphaSpaces()],
            'telefono' => ['required', 'numeric', 'digits:10'],

            'tipo_documento' => ['required', 'string', 'exists:document_types,acronym'],

            'numero_documento' => ['required', 'numeric', 'digits_between:6,20', 'unique:users,document_number'],

            'email' => ['required', 'email', 'unique:users,email'],

            'fecha_nacimiento' => ['required', 'date', 'before:today'],

            'numero_ficha' => ['required', 'numeric', 'exists:fichas,ficha_number'],
        ];
    }


    public function customValidationMessages()
    {
        return [
            'nombres.required' => 'El :attribute es obligatorio.',
            'nombres.string' => 'El :attribute debe ser texto.',
            'nombres.alpha_spaces' => 'El :attribute solo letras y espacios.',

            'apellidos.required' => 'El :attribute es obligatorio.',
            'apellidos.string' => 'El :attribute debe ser texto.',
            'apellidos.alpha_spaces' => 'El :attribute solo letras y espacios.',

            'telefono.required' => 'El :attribute es obligatorio.',
            'telefono.numeric' => 'El :attribute debe ser numérico.',
            'telefono.digits' => 'El :attribute debe tener exactamente :size dígitos.',

            'document_type.required' => 'El :attribute es obligatorio',
            'document_type.exists' => 'Tipo de documento (:input) no existe',

            'numero_documento.required' => 'El :attribute es obligatorio',
            'numero_documento.numeric' => 'El :attribute debe ser numérico',
            'numero_documento.digits_between' => 'El número de documento debe tener entre :min y :max dígitos',
            'numero_documento.unique' => 'Este :attribute ya está registrado',

            'email.required' => 'El :attribute es obligatorio',
            'email.email' => 'El :attribute debe tener formato válido',
            'email.unique' => 'Este :attribute ya está registrado',

            'fecha_nacimiento.required' => 'La :attribute es obligatoria',
            'fecha_nacimiento.date' => 'Formato de fecha inválido',
            'fecha_nacimiento.before' => 'La fecha no puede ser futura',

            'numero_ficha.required' => 'El :attribute es obligatorio',
            'numero_ficha.exists' => 'Ficha :input no existe',
        ];
    }

    public function customValidationAttributes()
    {
        return [
            'nombres' => 'nombre',
            'apellidos' => 'apellido',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'document_type' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'numero_ficha' => "número de ficha",
            'fecha_nacimiento' => 'fecha de nacimiento'
        ];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            DB::transaction(function () use ($row) {
                $apprentice = Apprentice::create([

                    'document_type_id' => $this->documentTypes[$row['tipo_documento']],
                    'document_number' => (string) $row['numero_documento'],
                    'email' => $row['email'],
                    'password' => null,
                    'status_id' => 1,
                    'ficha_id' => $this->fichas[$row['numero_ficha']]

                ]);

                $apprentice->profile()->create([

                    'first_name' => $row['nombres'],
                    'last_name' => $row['apellidos'],
                    'telephone_number' => (string) $row['telefono'],
                    'birth_date' => $row['fecha_nacimiento'],

                ]);

                $apprentice->roles()->attach(4);
            });
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
