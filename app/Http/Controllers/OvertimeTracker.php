<?php

namespace App\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;

/**
 * Class OvertimeTracker
 * 
 * This class tracks overtime for a user based on their time entries.
 * 
 * It calculates the total overtime for a given month and year by comparing
 * the hours worked against a standard number of hours per day.
 * 
 * Usage:
 * - $tracker  = new OvertimeTracker($userId, $month, $year);
 * - $overtime = $tracker->getMonthlyOvertime();
 * 
 * @extends Controller
 * @package App\Controllers
 * @author  Vladislav Riemer <riemer-vladi@web.de>
 */
class OvertimeTracker extends Controller
{
    protected int $userID;
    protected int $month;
    protected int $year;
    protected int $standardHoursPerMonth = 39;

    /**
     * @param int $userID
     * @param int $month
     * @param int $year
     * @param int $standardHoursPerMonth (optional, default 39)
     */
    public function __construct(int $userID, int $month, int $year, int $standardHoursPerMonth = 39) : void {
        $this->userID                = $userID;
        $this->month                 = $month;
        $this->year                  = $year;
        $this->standardHoursPerMonth = $standardHoursPerMonth;
    }

    /**
     * Calculate the overtime for the month.
     * 
     * @access public
     * @return float
     */
    public function getMonthlyOvertime() : float {
        $entries = TimeEntry::where('user_id', $this->userID)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->get();

        $totalWorked = $entries->sum('hours_worked');
        $overtime = max(0, $totalWorked - $this->standardHoursPerMonth);

        return $overtime;
    }
}