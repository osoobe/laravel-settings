<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_metas', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('meta_key')->unique();
            $table->string('meta_type')->nullable();
            $table->longText('meta_value');
            $table->string('category')->nullable()->default('default')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_metas');
    }
}
