<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class TimeEntryController
 * 
 * This class manages time entries for users, allowing them to create, edit,
 * update, and delete their time entries. It also calculates overtime and
 * monthly limits based on the user's hourly wage and minijob earnings limit.
 * 
 * @extends Controller
 * @package App\Controllers
 * @author  Vladislav Riemer <riemer-vladi@web.de>
 */
class TimeEntryController extends Controller
{
    /**
     * Hourly wage
     * 
     * @access private
     * @var    int
     * 
     * @default 14
     */
    private int $_hourlyWage   = 14;

    /**
     * Current minijob monthly limit
     * 
     * @access private
     * @var    int
     * 
     * @default 556
     */
    private int $_limitMinijob = 556;

    /**
     * Display the form for creating a new time entry.
     * 
     * @param Request $request Request parameters
     * 
     * @access public
     * @return View
     */
    public function create(Request $request) : View {
        $month            = $request->input('month', now()->month);
        $year             = $request->input('year', now()->year);

        $entries          = TimeEntry::where('user_id', Auth::id() ?? 1)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $monthlyLimit     = $this->calculateMonthlyLimit();
        $totalWorked      = $entries->sum('hours_worked');
        $overtime         = max(0, $totalWorked - $monthlyLimit);

        // Calculate total overtime for all months
        $allEntries       = TimeEntry::where('user_id', Auth::id() ?? 1)->get();
        $totalWorkedAll   = $allEntries->sum('hours_worked');

        // Calculate how many full months are in the data
        $monthsCount      = $allEntries->groupBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m');
        })->count();
        $totalLimitAll    = $monthsCount * $monthlyLimit;
        $totalOvertimeAll = max(0, $totalWorkedAll - $totalLimitAll);

        return view(
            'create', compact(
                'entries',
                'totalWorked',
                'overtime',
                'monthlyLimit',
                'month',
                'year',
                'totalWorkedAll',
                'totalOvertimeAll',
                'totalLimitAll'
            )
        );
    }

    /**
     * Store a new time entry.
     *
     * @param Request $request
     * 
     * @throws \Illuminate\Validation\ValidationException
     * 
     * @access public
     * @return RedirectResponse
     */
    public function store(Request $request) : RedirectResponse {
        $request->validate(
            [
                'date'          => 'required|date',
                'start_time'    => 'required|date_format:H:i',
                'end_time'      => 'required|date_format:H:i|after:start_time',
                'break_minutes' => 'nullable|integer|min:0|max:480'
            ]
        );

        $start         = $this->_parseTime($request->start_time);
        $end           = $this->_parseTime($request->end_time);
        $break         = (int) $request->break_minutes;

        // Calculate total minutes worked
        $minutesWorked = $start->diffInMinutes($end) - $break;

        // Prevent negative values
        $minutesWorked = max(0, $minutesWorked);

        // Round hours worked to two decimal places
        $hoursWorked   = round($minutesWorked / 60, 2);

        TimeEntry::create(
            [
                'user_id'       => Auth::id() ?? 1,
                'date'          => $request->date,
                'start_time'    => $request->start_time,
                'end_time'      => $request->end_time,
                'hours_worked'  => $hoursWorked,
                'break_minutes' => $break
            ]
        );

        return redirect()
            ->route(
                'time-entry.create', [
                    'month' => $request->input('month', now()->month),
                    'year'  => $request->input('year', now()->year)
                ]
            )
            ->with('success', 'Entry saved!');
    }

    /**
     * Edit a time entry.
     * 
     * @param int $id The ID of the time entry to be edited
     * 
     * @access public
     * @return View
     */
    public function edit(int $id) : View {
        $entryToEdit = TimeEntry::findOrFail($id);

        // Get current month/year for entries list
        $month       = request('month', now()->month);
        $year        = request('year', now()->year);

        $entries = TimeEntry::where('user_id', Auth::id() ?? 1)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $monthlyLimit = $this->calculateMonthlyLimit();
        $totalWorked  = $entries->sum('hours_worked');
        $overtime     = max(0, $totalWorked - $monthlyLimit);

        return view(
            'create',
            compact(
                'entries',
                'totalWorked',
                'overtime',
                'monthlyLimit',
                'month',
                'year',
                'entryToEdit'
            )
        );
    }

    /**
     * Update an existing time entry.
     * 
     * @param Request $request The request object containing parameters
     * @param int     $id      The ID of the time entry to update
     * 
     * @throws \Illuminate\Validation\ValidationException
     * 
     * @access public
     * @return RedirectResponse
     */
    public function update(Request $request, int $id) : RedirectResponse {
        $request->validate(
            [
                'date'          => 'required|date',
                'start_time'    => 'required|date_format:H:i',
                'end_time'      => 'required|date_format:H:i|after:start_time',
                'break_minutes' => 'nullable|integer|min:0|max:480'
            ]
        );

        $start         = $this->_parseTime($request->start_time);
        $end           = $this->_parseTime($request->end_time);
        $break         = (int) $request->break_minutes;
        $minutesWorked = $start->diffInMinutes($end) - $break;
        $minutesWorked = max(0, $minutesWorked);
        $hoursWorked   = round($minutesWorked / 60, 2);

        $entry         = TimeEntry::findOrFail($id);
        $entry->update(
            [
                'date'          => $request->date,
                'start_time'    => $request->start_time,
                'end_time'      => $request->end_time,
                'break_minutes' => $break,
                'hours_worked'  => $hoursWorked
            ]
        );

        return redirect()
            ->route(
                'time-entry.create', [
                    'month' => $request->input('month', now()->month),
                    'year'  => $request->input('year', now()->year)
                ]
            )->with('success', 'Entry updated!');
    }

    /**
     * Delete a time entry.
     * 
     * @param Request $request The request object containing parameters
     * @param int     $id      The ID of the time entry to delete
     * 
     * @access public
     * @return RedirectResponse
     */
    public function destroy(Request $request, int $id) : RedirectResponse {
        $entry = TimeEntry::findOrFail($id);
        $entry->delete();

        return redirect()
            ->route('time-entry.create', [
                    'month' => $request->input('month', now()->month),
                    'year'  => $request->input('year', now()->year)
                ]
            )
            ->with('success', 'Entry deleted!');
    }

    /**
     * Get the hourly wage.
     *
     * @access public
     * @return int
     */
    public function getHourlyWage() : int {
        return $this->_hourlyWage;
    }
    /**
     * Get the limit for minijob earnings.
     *
     * @access public
     * @return int
     */
    public function getMinijobLimit() : int {
        return $this->_limitMinijob;
    }

    /**
     * Set the hourly wage.
     *
     * @param int $wage The new hourly wage
     * 
     * @access public
     * @return void
     */
    public function setHourlyWage(int $wage) : void {
        $this->_hourlyWage = $wage;
    }
    /**
     * Set the limit for minijob wage.
     *
     * @param int $limit The new limit for minijob wage
     * 
     * @access public
     * @return void
     */
    public function setMinijobLimit(int $limit) : void {
        $this->_limitMinijob = $limit;
    }

    /**
     * Get the monthly limit for hours worked.
     *
     * @access public
     * @return int
     */
    public function calculateMonthlyLimit() : int {
        return $this->_limitMinijob / $this->_hourlyWage;
    }

    /**
     * Helper function to parse a time string into a Carbon instance.
     *
     * @param string|null $time The time string to parse
     * 
     * @access private
     * @return \Carbon\Carbon|null
     */
    private function _parseTime($time) : ?\Carbon\Carbon {
        if (!$time) return null;
        // Try H:i:s first, then H:i
        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $time);
        } catch (\Exception $e) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i', $time);
            } catch (\Exception $e) {
                return null;
            }
        }
    }
}