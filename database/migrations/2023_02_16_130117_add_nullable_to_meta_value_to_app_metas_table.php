<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNullableToMetaValueToAppMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_metas', function (Blueprint $table) {
            $table->longText('meta_value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('app_metas')->whereNull('meta_value')->update(['meta_value' => '']);

        Schema::table('app_metas', function (Blueprint $table) {
            $table->longText('meta_value')->nullable(false)->change();
        });
    }
}
