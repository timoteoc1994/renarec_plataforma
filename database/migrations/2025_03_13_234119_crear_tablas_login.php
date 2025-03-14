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
        // Tabla de ciudades
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        // Tabla de ciudadanos
        Schema::create('ciudadanos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telefono')->nullable();
            $table->string('direccion');
            $table->string('ciudad');
            $table->text('referencias_ubicacion')->nullable();
            $table->timestamps();
        });

        // Tabla de asociaciones
        Schema::create('asociaciones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number_phone');
            $table->string('direccion')->nullable();
            $table->string('city');
            $table->text('descripcion')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });

        // Tabla de recicladores
        Schema::create('recicladores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telefono');
            $table->string('ciudad');
            $table->unsignedBigInteger('asociacion_id');
            $table->enum('status', ['disponible', 'en_ruta', 'inactivo'])->default('disponible');
            $table->timestamps();

            $table->foreign('asociacion_id')->references('id')->on('asociaciones');
        });

        // Tabla de autenticación
        Schema::create('auth_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['ciudadano', 'reciclador', 'asociacion']);
            $table->unsignedBigInteger('profile_id');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Tabla de solicitudes de recolección
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ciudadano_id');
            $table->unsignedBigInteger('asociacion_id')->nullable();
            $table->unsignedBigInteger('reciclador_id')->nullable();
            $table->string('direccion');
            $table->string('ciudad');
            $table->text('referencias')->nullable();
            $table->text('materiales');
            $table->text('comentarios')->nullable();
            $table->date('fecha_solicitud');
            $table->date('fecha_recoleccion')->nullable();
            $table->enum('status', ['pendiente', 'asignada', 'en_progreso', 'completada', 'cancelada'])->default('pendiente');
            $table->timestamps();

            $table->foreign('ciudadano_id')->references('id')->on('ciudadanos');
            $table->foreign('asociacion_id')->references('id')->on('asociaciones');
            $table->foreign('reciclador_id')->references('id')->on('recicladores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('auth_users');
        Schema::dropIfExists('recicladores');
        Schema::dropIfExists('asociaciones');
        Schema::dropIfExists('ciudadanos');
        Schema::dropIfExists('cities');
    }
};
