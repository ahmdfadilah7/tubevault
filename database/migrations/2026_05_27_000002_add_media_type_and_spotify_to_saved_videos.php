<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_videos', function (Blueprint $table) {
            $table->string('media_type', 20)->default('youtube')->after('id');
            $table->string('spotify_id', 64)->nullable()->after('youtube_id');
            $table->string('spotify_type', 20)->nullable()->after('spotify_id');
        });

        Schema::table('saved_videos', function (Blueprint $table) {
            $table->string('youtube_id', 11)->nullable()->change();
            $table->dropUnique(['youtube_id']);
            $table->index(['media_type', 'youtube_id']);
            $table->index(['media_type', 'spotify_id']);
        });
    }

    public function down(): void
    {
        Schema::table('saved_videos', function (Blueprint $table) {
            $table->dropIndex(['media_type', 'youtube_id']);
            $table->dropIndex(['media_type', 'spotify_id']);
            $table->string('youtube_id', 11)->nullable(false)->change();
            $table->unique('youtube_id');
            $table->dropColumn(['media_type', 'spotify_id']);
        });
    }
};
