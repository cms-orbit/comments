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
        Schema::create('comment_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->nullableMorphs('rater');
            $table->string('category'); // overall, cleanliness, service, facility, value, etc.
            $table->decimal('rating', 2, 1); // 0.0 to 5.0 (allows half stars)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'rater_type', 'rater_id', 'category'], 'unique_rating');
            $table->index(['comment_id', 'category']);
            $table->index(['rater_type', 'rater_id']);
            $table->index(['rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_ratings');
    }
}; 