<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sceance;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SceanceController extends Controller
{
    /**
     * Display a listing of the screenings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $Sceances = Sceance::with(['Movie'])
                ->orderBy('date_heure', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $Sceances,
                'message' => 'Séances récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la récupération des séances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created screening in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'film_id' => 'required|exists:movies,id', // Référence à la table 'movies'
        'date_heure' => 'required|date|after_or_equal:now',
        'salle' => 'required|string',
        'statut' => 'required|in:disponible,complet,annulé',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    try {
        $seanceTime = Carbon::parse($request->date_heure);
        $movie = Movie::findOrFail($request->film_id);
        
        if (!$movie->duration) {
            return response()->json([
                'success' => false,
                'message' => 'Le film n\'a pas de durée définie'
            ], 400);
        }

        $endTime = $seanceTime->copy()->addMinutes($movie->duration + 30);

        $conflictingSeance = Sceance::where('salle', $request->salle)
            ->where(function($query) use ($seanceTime, $endTime) {
                $query->whereBetween('date_heure', [$seanceTime, $endTime])
                      ->orWhere(function($q) use ($seanceTime) {
                          $q->where('date_heure', '<', $seanceTime)
                            ->whereHas('movie', function($subQuery) use ($seanceTime) {
                                $subQuery->whereRaw('DATE_ADD(seances.date_heure, INTERVAL movies.duration + 30 MINUTE) > ?', [$seanceTime]);
                            });
                      });
            })
            ->exists();

        if ($conflictingSeance) {
            return response()->json([
                'success' => false,
                'message' => 'Conflit de réservation pour cette salle'
            ], 409);
        }

        $seance = Sceance::create([
            'film_id' => $request->film_id,
            'date_heure' => $seanceTime,
            'salle' => $request->salle,
            'statut' => $request->statut,
        ]);

        return response()->json([
            'success' => true,
            'data' => $seance->load('movie'),
            'message' => 'Séance créée avec succès'
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified screening.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $Sceance = Sceance::with(['Movie'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $Sceance,
                'message' => 'Séance récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Séance non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified screening in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'film_id' => 'required|exists:movies,id',
        'date_heure' => 'required|date|after_or_equal:now',
        'salle' => 'required|string',
        'statut' => 'required|in:disponible,complet,annulé',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
            'message' => 'Validation échouée'
        ], 422);
    }

    try {
        $Sceance = Sceance::findOrFail($id);
        
        $Sceance->update([
            'film_id' => $request->film_id,
            'date_heure' => $request->date_heure,
            'salle' => $request->salle,
            'statut' => $request->statut,
        ]);

        return response()->json([
            'success' => true,
            'data' => $Sceance->load(['movie']),
            'message' => 'Séance mise à jour avec succès'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Échec de la mise à jour de la séance',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified screening from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $Sceance = Sceance::findOrFail($id);
            
            // Check if there are any reservations for this Sceance
            if ($Sceance->reservations()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une séance avec des réservations existantes'
                ], 400);
            }

            $Sceance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Séance supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la suppression de la séance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}