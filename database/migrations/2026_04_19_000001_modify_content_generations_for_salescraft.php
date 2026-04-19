<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'topic',
                'keywords',
                'target_audience',
                'tone',
                'language',
                'generated_content',
                'word_count',
            ]);
        });

        Schema::table('content_generations', function (Blueprint $table) {
            $table->string('product_name');
            $table->text('description');
            $table->text('features');
            $table->string('target_audience')->nullable();
            $table->string('price');
            $table->string('usp')->nullable();
            $table->longText('generated_html');
            $table->string('style_template')->default('modern');
            $table->integer('char_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn([
                'product_name',
                'description',
                'features',
                'target_audience',
                'price',
                'usp',
                'generated_html',
                'style_template',
                'char_count',
            ]);
        });

        Schema::table('content_generations', function (Blueprint $table) {
            $table->string('content_type');
            $table->string('topic');
            $table->string('keywords')->nullable();
            $table->string('target_audience')->nullable();
            $table->string('tone');
            $table->string('language')->default('Bahasa Indonesia');
            $table->longText('generated_content');
            $table->integer('word_count')->default(0);
        });
    }
};
