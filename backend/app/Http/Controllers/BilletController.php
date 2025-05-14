<?php

namespace App\Http\Controllers;

use App\Models\Billet;
use App\Models\Sceance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationBillet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class BilletController extends Controller
{
    /**
     * Afficher tous les billets
     */
    public function index()
    {
        $billets = Billet::with(['sceance.movie', 'sceance.salle'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $billets
        ]);
    }

    /**
     * Créer un nouveau billet
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seance_id' => 'required|exists:seances,id',
            'type' => 'required|in:standard,premium,vip',
            'quantite' => 'required|integer|min:1',
            'user_email' => 'required|email',
            'prix' => 'required|numeric|min:0',
        ]);
    
        $billet = Billet::create($validated);
        
        // Chargez les relations nécessaires
        $billet->load(['sceance.movie', 'sceance.salle']);
    
        return response()->json([
            'success' => true,
            'data' => $billet
        ], 201);
    }
    /**
     * Afficher un billet spécifique
     */
    public function show($id)
    {
        $billet = Billet::with(['sceance.film', 'sceance.salle'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $billet
        ]);
    }

    /**
     * Mettre à jour un billet
     */
    public function update(Request $request, $id)
    {
        $billet = Billet::findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'sometimes|in:standard,premium,vip',
            'quantite' => 'sometimes|integer|min:1|max:10',
            'user_email' => 'sometimes|email',
            'prix' => 'sometimes|numeric',
            'confirmation_envoyee' => 'sometimes|boolean',
        ]);

        $billet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Billet mis à jour avec succès',
            'data' => $billet
        ]);
    }

    /**
     * Supprimer un billet
     */
    public function destroy($id)
    {
        $billet = Billet::findOrFail($id);
        $billet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Billet supprimé avec succès'
        ]);
    }

    /**
     * Envoyer une confirmation par email
     */
    // public function sendConfirmation($id)
    // {
    //     $billet = Billet::with(['sceance.film', 'sceance.salle'])->findOrFail($id);
        
    //     // Envoyer l'email
    //     try {
    //         Mail::to($billet->user_email)->send(new ConfirmationBillet($billet));
            
    //         // Mettre à jour le statut de confirmation
    //         $billet->confirmation_envoyee = true;
    //         $billet->save();
            
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Email de confirmation envoyé avec succès'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}