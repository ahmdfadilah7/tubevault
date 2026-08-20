<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_videos', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_id', 11)->unique();
            $table->string('title');
            $table->string('thumbnail_url');
            $table->string('channel_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('watch_count')->default(0);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_videos');
    }
};
