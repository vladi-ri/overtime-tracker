<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This method creates the 'settings' table with columns for 'key', 'value', and timestamps.
     * The 'key' column is used to store the setting name, and the 'value' column stores the setting value.
     * 
     * @throws \Exception
     * 
     * @access public
     * @return void
     */
    public function up() : void {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * This method drops the 'settings' table if it exists.
     * 
     * @throws \Exception
     * 
     * @access public
     * @return void
     */
    public function down() : void {
        Schema::dropIfExists('settings');
    }
};
