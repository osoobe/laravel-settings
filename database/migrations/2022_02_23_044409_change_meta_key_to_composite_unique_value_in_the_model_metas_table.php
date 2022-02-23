<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMetaKeyToCompositeUniqueValueInTheModelMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('model_metas', function (Blueprint $table) {
            $table->dropUnique(['meta_key']);
            $table->unique(['model_id', 'model_type', 'meta_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('model_metas', function (Blueprint $table) {
            $table->unique(['meta_key']);
        });
    }
}
