<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\FilmController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\MovieControllerAdmin;
use App\Http\Controllers\SceanceController;
use App\Http\Controllers\BilletController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SeanceController;



Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);

Route::get('/dashboard', [UsersController::class, 'dashboard']);

Route::post('/logout', [UsersController::class, 'logout']);


Route::prefix('admin')->group(function () {
    Route::get('/movies', [MovieControllerAdmin::class,'index']);
    Route::post('/movies', [MovieControllerAdmin::class,'store']); // Route POST pour ajouter un film
    Route::put('/movies/{id}', [MovieControllerAdmin::class,'update']); // Route POST pour ajouter un film
    Route::delete('/movies/{id}', [MovieControllerAdmin::class,'destroy']);

    Route::get('/seances', [SceanceController::class, 'index']);
    Route::post('/seances', [SceanceController::class, 'store']);
    Route::put('/seances/{id}', [SceanceController::class, 'update']);
    Route::delete('/seances/{id}', [SceanceController::class, 'destroy']);


   
    // Routes pour les billets
Route::apiResource('billets', BilletController::class);
Route::post('billets/{id}/send-confirmation', [BilletController::class, 'sendConfirmation']);





 // Route pour le dashboard
 Route::get('/stats/dashboard', [StatsController::class, 'getDashboardStats']);
    
 // Route pour les alertes
 Route::get('/stats/alerts', [StatsController::class, 'getAlerts']);
 
 // Route pour les statistiques des films
 Route::get('/stats/movies', [StatsController::class, 'getMovieStats']);
 
 // Route pour les statistiques des séances (avec paramètre optionnel period)
 Route::get('/stats/screenings', [StatsController::class, 'getScreeningStats']);


});




//ayoub routes:

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
});

Route::prefix('movies')->group(function () {
    // GET /api/movies - Liste tous les films
    Route::get('/', [MovieController::class, 'index']);
    
  
    // GET /api/movies/{id} - Affiche un film spécifique
    Route::get('/{id}', [MovieController::class, 'show']);

});
// Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum']], function() {
//     Route::get('/seances', [SceanceController::class, 'index']); // Lister
//     Route::post('/seances', [SceanceController::class, 'store']); // Créer
//     Route::get('/seances/{id}', [SceanceController::class, 'show']); // Afficher
//     Route::put('/seances/{id}', [SceanceController::class, 'update']); // Mettre à jour
//     Route::delete('/seances/{id}', [SceanceController::class, 'destroy']); // Supprimer
//     });


// // Routes publiques
// Route::get('/films', [FilmController::class, 'index']);
// Route::get('/films/{film}', [FilmController::class, 'show']);

// // Routes protégées (admin)
// Route::middleware([RoleMiddleware::class . ':admin'])->group(function () {
//     Route::post('/films', [FilmController::class, 'store']);
//     Route::put('/films/{film}', [FilmController::class, 'update']);
//     Route::delete('/films/{film}', [FilmController::class, 'destroy']);
// });

Route::get('/seances', [SeanceController::class, 'index']); // Liste filtrée
// Affiche une séance spécifique
Route::get('/seances/{id}', [SeanceController::class, 'show']);