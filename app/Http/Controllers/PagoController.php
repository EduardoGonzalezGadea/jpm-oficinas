<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Tesoreria\CajaDiaria\ConceptoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver_pagos')->only(['index', 'show']);
        $this->middleware('permission:crear_pagos')->only(['store']);
        $this->middleware('permission:editar_pagos')->only(['update']);
        $this->middleware('permission:eliminar_pagos')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $pagos = Pago::with('concepto')
            ->search($search)
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return response()->json($pagos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'medio_pago' => 'required|string',
            'descripcion' => 'nullable|string',
            'numero_comprobante' => 'nullable|string',
            'concepto_id' => 'required|exists:tes_cd_conceptos_pago,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $pago = new Pago($request->all());
            $pago->created_by = Auth::id();
            $pago->save();

            DB::commit();
            return response()->json($pago->load('concepto'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el pago'], 500);
        }
    }

    public function show($id)
    {
        $pago = Pago::with('concepto')->findOrFail($id);
        return response()->json($pago);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'medio_pago' => 'required|string',
            'descripcion' => 'nullable|string',
            'numero_comprobante' => 'nullable|string',
            'concepto_id' => 'required|exists:tes_cd_conceptos_pago,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $pago = Pago::findOrFail($id);
            $pago->fill($request->all());
            $pago->updated_by = Auth::id();
            $pago->save();

            DB::commit();
            return response()->json($pago->load('concepto'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el pago'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pago = Pago::findOrFail($id);
            $pago->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el pago'], 500);
        }
    }

    public function getConceptos()
    {
        $conceptos = ConceptoPago::activos()->orderBy('nombre')->get();
        return response()->json($conceptos);
    }
}
