<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieControllerAdmin extends Controller
{
    // Ajouter un film
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|integer',
            'release_date' => 'required|date',
            'genre' => 'required|string',
            'director' => 'required|string',
            'image_url' => 'required|url',
            'trailer_url' => 'required|url',
        ]);

        // Si la validation échoue, renvoyer les erreurs
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Créer le film
        $movie = Movie::create($request->all());

        // Renvoyer une réponse JSON
        return response()->json(['data' => $movie], 201);
    }

    public function index()
{
    $movies = Movie::all();
    return response()->json(['data' => $movies], 200);
}
public function update(Request $request, $id)
{
    // Trouver le film à mettre à jour
    $movie = Movie::findOrFail($id);

    // Validation des données envoyées
    $validatedData = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'duration' => 'required|integer',
        'release_date' => 'required|date',
        'genre' => 'required|string',
        'director' => 'required|string',
        'image_url' => 'nullable|string',
        'trailer_url' => 'nullable|string',
    ]);

    // Mettre à jour le film avec les nouvelles données
    $movie->update($validatedData);

    // Retourner le film mis à jour
    return response()->json(['data' => $movie], 200);
}
public function destroy($id)
{
    $movie = Movie::findOrFail($id);  // Trouve le film par ID, si non trouvé, il renverra une erreur 404
    
    $movie->delete();  // Supprime le film de la base de données

    return response()->json(['message' => 'Movie deleted successfully.'], 200);  // Réponse indiquant la réussite
}
}