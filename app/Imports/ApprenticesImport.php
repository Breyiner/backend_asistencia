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

class ApprenticesImport implements 
    ToCollection, 
    WithHeadingRow, 
    WithValidation, 
    WithChunkReading, 
    SkipsOnFailure,
    SkipsEmptyRows
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
        if (isset($row['fecha_nacimiento']) && is_numeric($row['fecha_nacimiento'])) {
            $row['fecha_nacimiento'] = Date::excelToDateTimeObject($row['fecha_nacimiento'])->format('Y-m-d');
        }
        
        if (empty($row['telefono'])) {
            $row['telefono'] = null;
        }

        return $row; 
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', new AlphaSpaces()],
            'apellidos' => ['required', 'string', new AlphaSpaces()],
            
            'telefono' => ['nullable', 'numeric', 'digits:10'],

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
            'apellidos.required' => 'El :attribute es obligatorio.',
            'telefono.numeric' => 'El :attribute debe ser numérico.',
            'telefono.digits' => 'El :attribute debe tener 10 dígitos.',
            'document_type.exists' => 'Tipo de documento no existe',
            'numero_documento.unique' => 'Este documento ya está registrado',
            'email.unique' => 'Este correo ya está registrado',
            'numero_ficha.exists' => 'La ficha no existe',
        ];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            
            DB::transaction(function () use ($row) {
                if (!isset($this->documentTypes[$row['tipo_documento']]) || !isset($this->fichas[$row['numero_ficha']])) {
                    return;
                }

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
                    'telephone_number' => $row['telefono'] ? (string) $row['telefono'] : null,
                    'birth_date' => $row['fecha_nacimiento'],
                ]);

                $apprenticeRoleId = Role::idByCode('APRENDIZ');
                $apprentice->roles()->syncWithoutDetaching([$apprenticeRoleId]);
            });
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}