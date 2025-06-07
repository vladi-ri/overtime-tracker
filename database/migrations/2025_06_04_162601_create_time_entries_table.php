<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeEntriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'time_entries',
            function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->integer('break_minutes')->default(0);
                $table->decimal('hours_worked', 5, 2);
                $table->timestamps();

                // If you have a users table, add the foreign key:
                // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down() : void {
        Schema::dropIfExists('time_entries');
    }
}

return new CreateTimeEntriesTable();