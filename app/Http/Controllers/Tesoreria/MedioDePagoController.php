<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MedioDePagoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page = request()->get('page', 1);
        $cacheKey = 'medios_de_pago_page_' . $page;

        $mediosDePago = Cache::remember($cacheKey, now()->addDay(), function () {
            return MedioDePago::ordenado()->paginate(10);
        });

        return view('tesoreria.configuracion.medios-de-pago.index', compact('mediosDePago'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tesoreria.configuracion.medios-de-pago.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        MedioDePago::create($request->all());

        Cache::flush();

        return redirect()->route('tesoreria.configuracion.medios-de-pago.index')
            ->with('success', 'Medio de pago creado exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $medioDePago = MedioDePago::findOrFail($id);

        return view('tesoreria.configuracion.medios-de-pago.show', compact('medioDePago'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $medioDePago = MedioDePago::findOrFail($id);

        return view('tesoreria.configuracion.medios-de-pago.edit', compact('medioDePago'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $medioDePago = MedioDePago::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        $medioDePago->update($request->all());

        Cache::flush();

        return redirect()->route('tesoreria.configuracion.medios-de-pago.index')
            ->with('success', 'Medio de pago actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $medioDePago = MedioDePago::findOrFail($id);

        // Verificar si el medio de pago está siendo usado
        $enUso = DB::table('tes_arrendamientos')->where('medio_de_pago', $medioDePago->nombre)->exists() ||
                 DB::table('tes_eventuales')->where('medio_de_pago', $medioDePago->nombre)->exists();

        if ($enUso) {
            return redirect()->route('tesoreria.configuracion.medios-de-pago.index')
                ->with('error', 'No se puede eliminar el medio de pago porque está siendo utilizado.');
        }

        $medioDePago->delete();

        Cache::flush();

        return redirect()->route('tesoreria.configuracion.medios-de-pago.index')
            ->with('success', 'Medio de pago eliminado exitosamente.');
    }
}
