<?php

namespace App\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TimeEntryController;
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
    /**
     * Month for which to track overtime.
     * 
     * @var    int $month
     * @access protected
     */
    protected int $month;

    /**
     * Year for which to track overtime.
     * 
     * @var    int $year
     * @access protected
     */
    protected int $year;

    /**
     * Standard hours per month.
     * 
     * @var    int $standardHoursPerMonth
     * @access protected
     */
    protected int $standardHoursPerMonth;

    /**
     * Constructor to initialize the OvertimeTracker.
     * 
     * @param int $month                 (1-12)
     * @param int $year                  (4-digit year)
     * @param int $standardHoursPerMonth (optional, default 39)
     */
    public function __construct(int $month, int $year, int $standardHoursPerMonth) {
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
        $timeEntryCtrl         = new TimeEntryController();
        $standardHoursPerMonth = $timeEntryCtrl->calculateMonthlyLimit();

        $entries               = TimeEntry::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->get();

        $totalWorked           = $entries->sum('hours_worked');
        $overtime              = max(0, $totalWorked - $standardHoursPerMonth);

        return $overtime;
    }
}
