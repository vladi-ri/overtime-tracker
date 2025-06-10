<?php

use App\Models\TimeEntry;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnWorkingPlace extends Migration
{
    /**
     * Run the migrations.
     * This migration adds a new column 'working_place' to the 'time_entries' table.
     * The column is of type string, has a default value of 'A', and is placed after the 'hours_worked' column.
     * The 'down' method removes this column if the migration is rolled back.
     * 
     * @access public
     * @return void
     * @throws \Illuminate\Database\QueryException
     */
    public function up() : void {
        $modelTimeEntries = new TimeEntry();
        $tableName        = $modelTimeEntries->getTable();

        Schema::table(
            $tableName,
            function (Blueprint $table) {
                $modelTimeEntries  = new TimeEntry();
                $columnHoursWorked = $modelTimeEntries->getTableName()
                    . '.'
                    . $modelTimeEntries->getColumnName('hours_worked');

                $table->string('working_place')->default('Wirtshaus')->after('hours_worked');
            }
        );
    }

    /**
     * Reverse the migrations.
     * This method removes the 'working_place' column from the 'time_entries' table.
     * 
     * @access public
     * @return void
     */
    public function down() : void {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('working_place');
        });
    }
};
