<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained()->onDelete('cascade');
            $table->string('user_email');
            $table->text('texte')->nullable();
            $table->integer('note');
            $table->timestamps();
        });

        // Ajouter la contrainte CHECK avec une commande brute
        DB::statement('ALTER TABLE notations ADD CONSTRAINT note_check CHECK (note >= 1 AND note <= 5)');
    }

    public function down()
    {
        Schema::dropIfExists('notations');
    }
};
