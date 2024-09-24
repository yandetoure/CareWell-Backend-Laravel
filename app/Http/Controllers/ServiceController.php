<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Créer un service
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:services',   
            'photo' => 'nullable|file|image|max:2048',
        ]);
    
        // Gestion du fichier
        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('service_photos', 'public'); // Stockage dans le dossier 'storage/app/public/service_photos'
        }
    
        $service = Service::create([
            'name' => $request->name,
            'photo' => $path,
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Service créé avec succès',
            'data' => $service,
        ], 201);
    }

    // Obtenir tous les services
    public function index()
    {
        $services = Service::all();
        return response()->json(['data' => $services]);
    }

    // Mettre à jour un service
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $id,
        ]);

        $service->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Service mis à jour avec succès',
            'data' => $service,
        ]);
    }

    // Supprimer un service
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'status' => true,
            'message' => 'Service supprimé avec succès',
        ]);
    }
}
