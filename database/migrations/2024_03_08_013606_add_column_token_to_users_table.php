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
            $table->string('token')->nullable();


            // $table->renameColumn('token', 'api_token');
            //mengganti nama kolom, argumen 1 nama kolom yg lama, argumen 2 nama kolom yg baru

            // $table->text('token')->change();
            //mengganti tipe data dari string ke text lalu di ikuti method chanfe (larena merubah, bukan menambah)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('token');

            // $table->renameColumn('api_token', 'token');
        });
    }
};
