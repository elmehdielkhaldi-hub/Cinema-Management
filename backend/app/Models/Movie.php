<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Movie extends Model
{
    use HasFactory;

    protected $table = 'movies';

    protected $fillable = [
        'title', 'description', 'duration', 'release_date',
        'genre', 'director', 'image_url', 'trailer_url'
    ];

    // Relation avec les séances
    public function seances()
    {
        return $this->hasMany(Sceance::class, 'film_id');
    }

    public function notations()
    {
        return $this->hasMany(Notation::class, 'film_id');
    }

    public function moyenneNotation()
    {
        return $this->notations()->avg('note');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'film_id'); // Assuming 'movie_id' is the foreign key
    }
}