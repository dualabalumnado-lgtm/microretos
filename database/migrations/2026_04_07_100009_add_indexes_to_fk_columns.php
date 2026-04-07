<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            if (!$this->indexExists('microretos', 'microretos_empresa_id_index')) {
                $table->index('empresa_id');
            }
            if (!$this->indexExists('microretos', 'microretos_ciclo_id_index')) {
                $table->index('ciclo_id');
            }
        });

        Schema::table('empresa_familia', function (Blueprint $table) {
            if (!$this->indexExists('empresa_familia', 'empresa_familia_familia_id_index')) {
                $table->index('familia_id');
            }
        });

        Schema::table('empresas', function (Blueprint $table) {
            if (!$this->indexExists('empresas', 'empresas_centro_id_index')) {
                $table->index('centro_id');
            }
        });

        Schema::table('centro_ciclo', function (Blueprint $table) {
            if (!$this->indexExists('centro_ciclo', 'centro_ciclo_centro_id_index')) {
                $table->index('centro_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('microretos',     fn($t) => $t->dropIndex(['empresa_id', 'ciclo_id']));
        Schema::table('empresa_familia', fn($t) => $t->dropIndex(['familia_id']));
        Schema::table('empresas',       fn($t) => $t->dropIndex(['centro_id']));
        Schema::table('centro_ciclo',   fn($t) => $t->dropIndex(['centro_id']));
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains('Key_name', $index);
    }
};
