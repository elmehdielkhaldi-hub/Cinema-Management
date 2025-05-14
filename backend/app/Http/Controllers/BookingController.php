<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;

class BookingController extends Controller
{
    /**
     * Crée une nouvelle réservation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'seats' => 'required|array',
            'seats.*' => 'integer|min:1|max:100',
            'showtime' => 'required|date',
            'theater' => 'required|string|max:255'
        ]);

        // Calcul du prix total
        $movie = Movie::findOrFail($validated['movie_id']);
        $ticketPrice = $movie->ticket_price ?? 12.99;
        $serviceFee = 2.50;
        $totalAmount = (count($validated['seats']) * $ticketPrice) + $serviceFee;

        // Création de la réservation
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'movie_id' => $validated['movie_id'],
            'seats' => $validated['seats'],
            'showtime' => $validated['showtime'],
            'theater' => $validated['theater'],
            'ticket_price' => $ticketPrice,
            'service_fee' => $serviceFee,
            'total_amount' => $totalAmount,
            'booking_reference' => 'BK-' . strtoupper(uniqid())
        ]);

        return response()->json([
            'message' => 'Réservation créée avec succès',
            'data' => $booking
        ], 201);
    }

    /**
     * Affiche une réservation spécifique
     */
    public function show(Booking $booking)
    {
        // Vérifie que l'utilisateur peut voir cette réservation
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'data' => $booking->load('movie')
        ]);
    }

    /**
     * Envoie le reçu par email
     */
    public function sendReceipt(Booking $booking)
    {
        // Vérifie que l'utilisateur peut voir cette réservation
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Envoie l'email
        Mail::to(auth()->user()->email)
            ->send(new BookingConfirmation($booking));

        return response()->json([
            'message' => 'Email de confirmation envoyé'
        ]);
    }
}