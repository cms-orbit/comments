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
            $table->foreignId('parent_id')->index()->nullable()->constrained('comments')->onDelete('cascade');
            $table->text('content');
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_password')->nullable();
            $table->boolean('is_approved')->default(true)->index();
            $table->boolean('is_secret')->default(false)->index();
            $table->boolean('is_spam')->default(false)->index();
            $table->boolean('notify_reply')->default(false)->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_at']);
            $table->index(['updated_at']);
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
