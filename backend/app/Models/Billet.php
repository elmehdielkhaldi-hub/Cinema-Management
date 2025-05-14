<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billet extends Model
{
    use HasFactory;

    protected $fillable = ['seance_id', 'type', 'prix', 'quantite', 'user_email'];

    // Relation (Exemple: Un billet appartient à un utilisateur)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation (Exemple: Un billet appartient à une séance)
    public function sceance()
    {
        return $this->belongsTo(Sceance::class);
    }
    
}
