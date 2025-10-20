<?php

namespace App\Http\Controllers;

use App\Models\Tesoreria\CajaDiaria\ConceptoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConceptoPagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gestionar_conceptos_pago');
    }
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $conceptos = ConceptoPago::search($search)
            ->orderBy('nombre')
            ->paginate(10);

        return response()->json($conceptos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|unique:tes_cd_conceptos_pago,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $concepto = new ConceptoPago($request->all());
            $concepto->created_by = Auth::id();
            $concepto->save();

            return response()->json($concepto, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el concepto de pago'], 500);
        }
    }

    public function show($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        return response()->json($concepto);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|unique:tes_cd_conceptos_pago,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $concepto = ConceptoPago::findOrFail($id);
            $concepto->fill($request->all());
            $concepto->updated_by = Auth::id();
            $concepto->save();

            return response()->json($concepto);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el concepto de pago'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $concepto = ConceptoPago::findOrFail($id);

            // Verificar si hay pagos asociados
            if ($concepto->pagos()->exists()) {
                return response()->json(['message' => 'No se puede eliminar el concepto porque tiene pagos asociados'], 422);
            }

            $concepto->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el concepto de pago'], 500);
        }
    }
}
