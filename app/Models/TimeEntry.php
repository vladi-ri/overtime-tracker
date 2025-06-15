<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TimeEntry
 *
 * Represents a time entry for a user, including the date and hours worked.
 * 
 * @extends Model
 * @package App\Models
 * @author  Vladislav Riemer <riemer-vladi@web.de>
 */
class TimeEntry extends Model
{
    /**
     * The table associated with the model.
     *
     * @access private
     * @var    string
     */
    private string $_TABLE_NAME = 'time_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @access protected
     * @var    array
     */
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'break_minutes'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @access protected
     * @var    array
     */
    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate hours worked based on start_time, end_time, and break_minutes
     * 
     * @access public
     * @return float
     */
    public function getHoursWorkedAttribute() : float {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        // Ensure start_time and end_time are Carbon instances
        $start         = Carbon::parse($this->start_time);
        $end           = Carbon::parse($this->end_time);

        // If end is before or equal to start, add 1 day to end
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        // Calculate the total minutes worked, subtracting break time
        $break         = (int) $this->break_minutes;
        $minutesWorked = $start->diffInMinutes($end) - $break;
        $minutesWorked = max(0, $minutesWorked);

        // Return hours worked as a float, rounded to 2 decimal places
        return round($minutesWorked / 60, 2);
    }

    /**
     * Get the table name for the model.
     *
     * @access public
     * @return string
     */
    public function getTableName() : string {
        return $this->_TABLE_NAME;
    }

    /**
     * Get column names for the model.
     *
     * @access public
     * @return string
     */
    public function getColumnName(string $columnName) : string {
        switch ($columnName) {
            case 'date':          $columnName = 'date'; break;
            case 'start_time':    $columnName = 'start_time'; break;
            case 'end_time':      $columnName = 'end_time'; break;
            case 'break_minutes': $columnName = 'break_minutes'; break;
            default:              $columnName = 'unknown_column'; break;
        }
        return $columnName;
    }
}
