<?php

namespace App\Models;

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
        'break_minutes',
        'hours_worked'
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
}
