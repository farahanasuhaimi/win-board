<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->unsignedInteger('xp')->default(0)->after('total_wins');
            $table->unsignedTinyInteger('level')->default(1)->after('xp');
            $table->unsignedTinyInteger('shields')->default(0)->after('level');
            $table->unsignedTinyInteger('comeback_days_left')->default(0)->after('shields');
        });
    }

    public function down(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn(['xp', 'level', 'shields', 'comeback_days_left']);
        });
    }
};
