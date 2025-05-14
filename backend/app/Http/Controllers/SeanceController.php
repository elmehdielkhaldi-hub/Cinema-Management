<?php

namespace App\Http\Controllers;

use App\Models\Sceance;
use Carbon\Carbon;

class SeanceController extends Controller
{
    /**
     * Affiche toutes les séances disponibles avec les films associés
     */
    public function index()
    {
        // Récupérer toutes les séances avec les informations du film
        $seances = Sceance::with('movie')->get();

        // Retourner la réponse JSON
        return response()->json($seances);
    }
    /**
     * Affiche une séance spécifique avec son film
     */
    public function show($id)
    {
        try {
            $seance = Sceance::with('movie')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'seance' => [
                    'id' => $seance->id,
                    'date_heure' => $seance->date_heure,
                    'salle' => $seance->salle,
                    'statut' => $seance->statut,
                    'film' => [
                        'titre' => $seance->movie->title,
                        'image' => $seance->movie->image_url,
                        'description' => $seance->movie->description,
                        'genre' => $seance->movie->genre,
                        'duree' => $seance->movie->duration,
                        'realisateur' => $seance->movie->director
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Séance non trouvée'
            ], 404);
        }
    }

    
}