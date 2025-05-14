<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MovieApiSeeder extends Seeder
{
    private $apiKey;
    private $language;
    private $pagesToFetch;
    private $queryTypes;
    private $regions;

    public function __construct()
    {
        $this->apiKey = '0398075009e685cef25d4e7d59ca7ce7';
        $this->language = 'fr-FR';
        $this->pagesToFetch = 5; // Augmenté à 5 pages
        $this->queryTypes = [
            'popular',
            'top_rated',
            'now_playing',
            'upcoming'
        ];
        $this->regions = ['US', 'FR', 'DE', 'IT', 'ES', 'GB', 'JP', 'IN', 'BR', 'RU', 'CA', 'AU', 'KR'];
    }

    public function run()
    {
        try {
            $totalAdded = 0;

            foreach ($this->queryTypes as $type) {
                $region = $this->getRandomRegion();
                $this->command->info("Recherche de films: {$type} dans la région {$region}");

                for ($page = 1; $page <= $this->pagesToFetch; $page++) {
                    $response = $this->makeApiRequest("https://api.themoviedb.org/3/movie/{$type}", [
                        'page' => $page,
                        'region' => $region
                    ]);

                    if (!$response->successful()) {
                        Log::error("Erreur page $page (type: $type, région: $region): " . $response->body());
                        continue;
                    }

                    $movies = $response->json()['results'];
                    $pageAdded = 0;

                    foreach ($movies as $movieData) {
                        try {
                            if ($this->addMovieIfNotExists($movieData['id'])) {
                                $pageAdded++;
                            }
                        } catch (\Exception $e) {
                            Log::error("Film {$movieData['id']}: " . $e->getMessage());
                        }
                    }

                    $totalAdded += $pageAdded;
                    $this->command->info("Page $page: $pageAdded nouveaux films ajoutés");

                    // Pause plus longue pour éviter le rate limiting
                    if ($page < $this->pagesToFetch) {
                        sleep(2);
                    }
                }
            }

            $this->command->info("Total: $totalAdded nouveaux films ajoutés");

        } catch (\Exception $e) {
            $this->command->error("ERREUR: " . $e->getMessage());
        }
    }

    private function addMovieIfNotExists($tmdbId)
    {
        if (Movie::where('tmdb_id', $tmdbId)->exists()) {
            return false;
        }

        $details = $this->makeApiRequest("https://api.themoviedb.org/3/movie/{$tmdbId}", [
            'append_to_response' => 'credits,videos'
        ])->json();

        $movieData = [
            'tmdb_id' => $tmdbId,
            'title' => $details['title'],
            'description' => $details['overview'] ?? '',
            'duration' => $details['runtime'] ?? 120,
            'release_date' => $details['release_date'] 
                ? Carbon::parse($details['release_date'])
                : null,
            'genre' => $this->formatGenres($details['genres'] ?? []),
            'director' => $this->getDirector($details['credits']['crew'] ?? []),
            'image_url' => $details['poster_path']
                ? "https://image.tmdb.org/t/p/w500{$details['poster_path']}"
                : null,
            'trailer_url' => $this->getTrailer($details['videos']['results'] ?? []),
        ];

        Movie::create($movieData);
        return true;
    }

    private function formatGenres($genres)
    {
        return collect($genres)->take(3)->pluck('name')->implode(', ');
    }

    private function getDirector($crew)
    {
        return collect($crew)->firstWhere('job', 'Director')['name'] ?? 'Inconnu';
    }

    private function getTrailer($videos)
    {
        $trailer = collect($videos)->first(function ($video) {
            return $video['type'] === 'Trailer' && $video['site'] === 'YouTube';
        });

        return $trailer ? "https://youtu.be/{$trailer['key']}" : null;
    }

    private function makeApiRequest($url, $params = [])
    {
        $defaultParams = [
            'api_key' => $this->apiKey,
            'language' => $this->language
        ];

        return Http::withoutVerifying()
            ->timeout(30)
            ->retry(3, 1000) // Augmenté le délai entre les tentatives
            ->get($url, array_merge($defaultParams, $params));
    }

    private function getRandomRegion()
    {
        return $this->regions[array_rand($this->regions)];
    }
}