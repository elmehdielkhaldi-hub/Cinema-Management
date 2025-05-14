<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notation extends Model
{
    use HasFactory;

    protected $table = 'notations'; // Ajout recommandé

    protected $fillable = [
        'note',
        'user_id',
        'film_id',
        'commentaire' // Optionnel : ajout d'un champ commentaire
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec le film
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'film_id');
    }
}