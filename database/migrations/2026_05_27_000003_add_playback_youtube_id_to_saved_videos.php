<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_videos', function (Blueprint $table) {
            $table->string('playback_youtube_id', 11)->nullable()->after('spotify_type');
        });
    }

    public function down(): void
    {
        Schema::table('saved_videos', function (Blueprint $table) {
            $table->dropColumn('playback_youtube_id');
        });
    }
};
