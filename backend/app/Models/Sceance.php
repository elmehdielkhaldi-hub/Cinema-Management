<?php 
namespace App\Models;   

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;  

class Sceance extends Model 
{     
    use HasFactory;      

    protected $table = 'seances';

    protected $fillable = [
        'film_id', 
        'date_heure', 
        'salle', 
        'statut'
    ];

    // Relation avec le film
    public function movie()     
    {
        return $this->belongsTo(Movie::class, 'film_id');     
    }     

    // Relation avec les billets
    public function reservations()     
    {
        return $this->hasMany(Billet::class, 'seance_id');     
    }
    public function billets()     
    {
        return $this->hasMany(Billet::class, 'seance_id');     
    }     
     

    // Méthode pour vérifier la disponibilité
    public function estDisponible()
    {
        return $this->statut === 'disponible';
    }

    // Méthode pour calculer les billets restants
    public function billetsRestants($capaciteSalle)
    {
        $billetVendus = $this->billets()->sum('quantite');
        return max(0, $capaciteSalle - $billetVendus);
    }
}