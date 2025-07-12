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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->nullableMorphs('author');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->text('content');
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_spam')->default(false);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('rating_data')->nullable();
            $table->json('reaction_data')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index(['author_type', 'author_id']);
            $table->index(['parent_id']);
            $table->index(['is_approved']);
            $table->index(['is_spam']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
}; 