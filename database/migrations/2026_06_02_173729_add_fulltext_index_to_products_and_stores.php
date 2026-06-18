<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->fullText('name'); 
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->fullText('name');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(['name']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropFullText(['name']);
        });
    }
};