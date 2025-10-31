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
            $table->string('theme_color')->default('blue')->comment('主题颜色: blue, purple, green, orange, pink, cyan');
            $table->string('sidebar_style')->default('light')->comment('侧边栏风格: light, dark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme_color', 'sidebar_style']);
        });
    }
};
