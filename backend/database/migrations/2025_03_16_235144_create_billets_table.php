<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('billets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seance_id')->constrained()->onDelete('cascade');
            $table->string('type'); // standard, premium
            $table->decimal('prix', 8, 2);
            $table->integer('quantite');
            $table->integer('vendus')->default(0);
            $table->string('user_email');
            $table->boolean('confirmation_envoyee')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('billets');
    }
};
