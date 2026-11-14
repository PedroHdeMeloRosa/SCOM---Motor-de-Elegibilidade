<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('renda_per_capita', 8, 2)->nullable();
            $table->string('tipo_escola')->nullable();
            $table->string('etnia')->nullable();
            $table->decimal('nota_media_enem', 6, 2)->nullable();
            $table->string('cep')->nullable();
            $table->string('cidade_origem')->default('Sorocaba');
            $table->string('estado_origem')->default('SP');
            $table->decimal('lat_origem', 10, 7)->default(-23.5015);
            $table->decimal('lng_origem', 10, 7)->default(-47.4581);
            $table->string('nivel_mobilidade')->nullable();
            $table->decimal('orcamento_moradia', 8, 2)->nullable();
            $table->boolean('interesse_republica')->default(false);
            $table->boolean('precisa_auxilio_moradia')->default(false);
            $table->boolean('pcd')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['renda_per_capita', 'tipo_escola', 'etnia', 'nota_media_enem', 'cep', 'cidade_origem', 'estado_origem', 'lat_origem', 'lng_origem', 'nivel_mobilidade', 'orcamento_moradia', 'interesse_republica', 'precisa_auxilio_moradia', 'pcd']);
        });
    }
};
