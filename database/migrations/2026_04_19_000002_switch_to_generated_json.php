<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn(['generated_html', 'char_count']);
        });

        Schema::table('content_generations', function (Blueprint $table) {
            $table->longText('generated_json');
        });
    }

    public function down(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn('generated_json');
        });

        Schema::table('content_generations', function (Blueprint $table) {
            $table->longText('generated_html');
            $table->integer('char_count')->default(0);
        });
    }
};
