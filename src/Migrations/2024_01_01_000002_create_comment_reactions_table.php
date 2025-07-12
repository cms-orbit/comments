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
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->nullableMorphs('reactor');
            $table->string('type'); // like, love, laugh, wow, sad, angry
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'reactor_type', 'reactor_id', 'type'], 'unique_reaction');
            $table->index(['comment_id', 'type']);
            $table->index(['reactor_type', 'reactor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
}; 