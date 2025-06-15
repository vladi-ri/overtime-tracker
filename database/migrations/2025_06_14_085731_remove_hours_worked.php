<?php
// database/migrations/xxxx_xx_xx_xxxxxx_remove_hours_worked_from_time_entries.php

use App\Models\TimeEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class RemoveHoursWorked
 *
 * Migration to remove the 'hours_worked' column from the 'time_entries' table.
 * If the column does not exist, it will not attempt to drop it.
 * If the column exists, it will be removed.
 *
 * @package App\Migrations
 *
 * @author  Vladislav Riemer <riemer-vladi@web.de>
 */
class RemoveHoursWorked extends Migration {
    /**
     * Run the migrations.
     * 
     * This method checks if the 'hours_worked' column exists in the 'time_entries' table.
     * If it does, it drops the column.
     * 
     * @access public
     * @return void
     */
    public function up() : void {
        $model      = new TimeEntry();
        $tableName  = $model->getTable();
        $columnName = $model->getColumnName('hours_worked');

        if (!Schema::hasColumn($tableName, $columnName)) {
            return; // Column does not exist, nothing to drop
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * This method checks if the 'hours_worked' column does not exist in the 'time_entries' table.
     * If it does not, it adds the column back as a nullable float.
     * 
     * @access public
     * @return void
     */
    public function down() : void {
        $model      = new TimeEntry();
        $tableName  = $model->getTable();
        $columnName = $model->getColumnName('hours_worked');

        if (Schema::hasColumn($tableName, $columnName)) {
            return; // Column already exists, nothing to add
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->float($columnName)->nullable();
        });
    }
};

return new RemoveHoursWorked();