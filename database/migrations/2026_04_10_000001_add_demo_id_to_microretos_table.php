<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->foreignId('demo_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('demos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropForeign(['demo_id']);
            $table->dropColumn('demo_id');
        });
    }
};
