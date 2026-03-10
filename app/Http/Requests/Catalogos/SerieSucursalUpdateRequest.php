<?php

namespace App\Http\Requests\Catalogos;

use App\Models\SerieSucursal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SerieSucursalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serieSucursal = $this->route('serie_sucursal');
        $id = is_object($serieSucursal) ? $serieSucursal->id : $serieSucursal;

        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'tipo_serie_id' => ['required', 'integer', 'exists:tipos_serie,id'],
            'serie' => ['required', 'string', 'max:20', Rule::unique('series_sucursal', 'serie')->ignore($id)],
            'folio_actual' => ['required', 'integer', 'min:0'],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $serieSucursal = $this->route('serie_sucursal');
            $id = is_object($serieSucursal) ? $serieSucursal->id : $serieSucursal;

            $exists = SerieSucursal::query()
                ->where('cliente_id', $this->cliente_id)
                ->where('tipo_serie_id', $this->tipo_serie_id)
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($exists) {
                $validator->errors()->add('tipo_serie_id', 'Ya existe una serie configurada para ese cliente y tipo de serie.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es requerido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'tipo_serie_id.required' => 'El tipo de serie es requerido.',
            'tipo_serie_id.exists' => 'El tipo de serie seleccionado no existe.',
            'serie.required' => 'La serie es requerida.',
            'serie.unique' => 'La serie ya existe.',
            'folio_actual.required' => 'El folio actual es requerido.',
            'activo.required' => 'El campo activo es requerido.',
        ];
    }
}