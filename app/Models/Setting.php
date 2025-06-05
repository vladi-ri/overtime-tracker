<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Setting
 *
 * Represents a time entry for a user, including the date and hours worked.
 * 
 * @extends Model
 * @package App\Models
 * @author  Vladislav Riemer <riemer-vladi@web.de>
 */
class Setting extends Model
{
    /**
     * The table associated with the model.
     *
     * @access private
     * @var    string
     */
    private string $_TABLE_NAME = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @access protected
     * @var    array
     */
    protected $fillable = [
        'key',
        'value'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @access protected
     * @var    array
     */
    protected $casts = [
        'value' => 'string',
    ];
}
