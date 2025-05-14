<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Sceance;
use App\Models\Billet;
use App\Models\User;
use Carbon\Carbon;

class StatsController extends Controller
{
    // Constante pour la capacité des salles (à adapter selon votre configuration)
    const SALLE_CAPACITY = 100; // Capacité par défaut d'une salle

    /**
     * Récupère les statistiques globales du dashboard
     */
    public function getDashboardStats()
    {
        // Statistiques des films
        $totalMovies = Movie::count();
        $newMoviesThisMonth = Movie::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // Statistiques des séances
        $totalScreenings = Sceance::count();
        $todayScreenings = Sceance::whereDate('date_heure', Carbon::today())->count();
        
        // Prochaines séances avec calcul d'occupation
        $upcomingScreenings = Sceance::with(['movie', 'billets'])
            ->where('date_heure', '>', Carbon::now())
            ->orderBy('date_heure')
            ->take(5)
            ->get()
            ->map(function($screening) {
                return [
                    'id' => $screening->id,
                    'movie' => $screening->movie->title,
                    'time' => Carbon::parse($screening->date_heure)->format('H:i'),
                    'salle' => $screening->salle,
                    'occupancy' => $this->calculateOccupancy($screening),
                ];
            });

        // Statistiques des billets
        $totalTicketsSold = Billet::sum('quantite');
        $todayTicketsSold = Billet::whereDate('created_at', Carbon::today())->sum('quantite');
        $dailyRevenue = Billet::whereDate('created_at', Carbon::today())->sum('prix');

        return response()->json([
            'stats' => [
                'movies' => $totalMovies,
                'movies_added_this_month' => $newMoviesThisMonth,
                'total_screenings' => $totalScreenings,
                'today_screenings' => $todayScreenings,
                'tickets_sold' => $totalTicketsSold,
                'today_tickets_sold' => $todayTicketsSold,
                'daily_revenue' => $dailyRevenue,
            ],
            'upcoming_screenings' => $upcomingScreenings
        ]);
    }

    /**
     * Récupère les alertes importantes
     */
    public function getAlerts()
    {
        $alerts = [];

        // Séances du jour avec occupation
        $todayScreenings = Sceance::with(['movie', 'billets'])
            ->whereDate('date_heure', Carbon::today())
            ->where('date_heure', '>', Carbon::now())
            ->get();

        foreach ($todayScreenings as $screening) {
            $occupancy = $this->calculateOccupancy($screening);
            $time = Carbon::parse($screening->date_heure)->format('H:i');
            
            if ($occupancy > 80) {
                $alerts[] = [
                    'type' => 'success',
                    'message' => "Salle {$screening->salle} : Séance de {$time} ({$screening->movie->title}) presque complète ({$occupancy}%)",
                ];
            } elseif ($occupancy < 30) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Salle {$screening->salle} : Faibles réservations pour {$time} ({$screening->movie->title}) - {$occupancy}%",
                ];
            }
        }

        // Ajouter d'autres types d'alertes si nécessaire
        // Exemple: séances annulées
        $cancelledScreenings = Sceance::where('statut', 'annulee')
            ->whereDate('date_heure', '>=', Carbon::today())
            ->get();

        foreach ($cancelledScreenings as $screening) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Séance annulée: {$screening->movie->title} à " . 
                            Carbon::parse($screening->date_heure)->format('H:i') . 
                            " (Salle {$screening->salle})"
            ];
        }

        return response()->json($alerts);
    }

    /**
     * Statistiques détaillées des films
     */
    public function getMovieStats()
    {
        // Films les plus populaires (avec le plus de séances)
        $mostScreenedMovies = Movie::withCount('seances')
            ->orderBy('seances_count', 'desc')
            ->take(5)
            ->get();

        // Films les mieux notés
        $topRatedMovies = Movie::withAvg('notations', 'note')
            ->having('notations_avg_note', '>', 0)
            ->orderBy('notations_avg_note', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'most_screened' => $mostScreenedMovies,
            'top_rated' => $topRatedMovies
        ]);
    }

    /**
     * Statistiques détaillées des séances
     */
    public function getScreeningStats(Request $request)
    {
        $period = $request->input('period', 'week'); // week, month, year

        $query = Sceance::with(['movie', 'billets'])
            ->where('date_heure', '>=', Carbon::now()->subMonth()) // Par défaut: dernier mois
            ->withCount('billets');

        switch ($period) {
            case 'week':
                $query->where('date_heure', '>=', Carbon::now()->subWeek());
                break;
            case 'month':
                $query->where('date_heure', '>=', Carbon::now()->subMonth());
                break;
            case 'year':
                $query->where('date_heure', '>=', Carbon::now()->subYear());
                break;
        }

        $screenings = $query->get()
            ->map(function($screening) {
                return [
                    'id' => $screening->id,
                    'movie' => $screening->movie->title,
                    'date' => Carbon::parse($screening->date_heure)->format('Y-m-d H:i'),
                    'salle' => $screening->salle,
                    'tickets_sold' => $screening->billets_count,
                    'revenue' => $screening->billets->sum('prix'),
                    'occupancy' => $this->calculateOccupancy($screening)
                ];
            });

        return response()->json([
            'period' => $period,
            'total_screenings' => $screenings->count(),
            'total_tickets_sold' => $screenings->sum('tickets_sold'),
            'total_revenue' => $screenings->sum('revenue'),
            'screenings' => $screenings
        ]);
    }

    /**
     * Calcule le taux d'occupation d'une séance
     */
    private function calculateOccupancy(Sceance $screening)
    {
        $ticketsSold = $screening->billets->sum('quantite');
        return min(100, round(($ticketsSold / self::SALLE_CAPACITY) * 100));
    }
}