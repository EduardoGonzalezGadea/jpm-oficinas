<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\Multa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MultaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('tesoreria.multas.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tesoreria.multas.create');
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
            'articulo' => 'required|string|max:10',
            'apartado' => 'nullable|string|max:10',
            'descripcion' => 'required|string',
            'importe_original' => 'required|numeric|min:0',
            'importe_unificado' => 'nullable|numeric|min:0',
            'decreto' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ]);

        DB::transaction(function () use ($request) {
            Multa::create($request->all());
            Cache::flush();
        });

        return redirect()->route('tesoreria.multas.index')->with('success', 'Multa creada exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tesoreria\Multa  $multa
     * @return \Illuminate\Http\Response
     */
    public function show(Multa $multa)
    {
        return view('tesoreria.multas.show', compact('multa'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tesoreria\Multa  $multa
     * @return \Illuminate\Http\Response
     */
    public function edit(Multa $multa)
    {
        return view('tesoreria.multas.edit', compact('multa'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tesoreria\Multa  $multa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Multa $multa)
    {
        $request->validate([
            'articulo' => 'required|string|max:10',
            'apartado' => 'nullable|string|max:10',
            'descripcion' => 'required|string',
            'importe_original' => 'required|numeric|min:0',
            'importe_unificado' => 'nullable|numeric|min:0',
            'decreto' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $multa) {
            $multa->update($request->all());
            Cache::flush();
        });

        return redirect()->route('tesoreria.multas.index')->with('success', 'Multa actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tesoreria\Multa  $multa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Multa $multa)
    {
        DB::transaction(function () use ($multa) {
            $multa->delete();
            Cache::flush();
        });

        return redirect()->route('tesoreria.multas.index')->with('success', 'Multa eliminada exitosamente.');
    }
}
