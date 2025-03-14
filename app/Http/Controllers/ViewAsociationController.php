<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\asociation;


class ViewAsociationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $asociations = asociation::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->paginate(10);



        return Inertia::render('asociation/index', [
            'Asociations' => $asociations,
            'filters' => $request->only(['search'])
        ]);
    }
    public function show(Request $request)
    {
        $asociation = asociation::find($request->id);
        return Inertia::render('asociation/show', [
            'asociation' => $asociation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(asociation $asociation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        //validar los datos 
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'number_phone' => 'required',
            'city' => 'required',
            'estado' => 'required',
        ]);
        //actualizar asociacion con los datos cal $request->id
        $asociacion = asociation::findOrFail($request->id);
        $asociacion->update($validatedData);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function delete($dato)
    {
        $asociacion = asociation::findOrFail($dato); // Busca el registro o lanza un error 404
        $asociacion->delete(); // Elimina el registro
    }
}
