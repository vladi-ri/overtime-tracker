<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TimeEntry;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Class TimeEntryController
 * 
 * This class manages time entries for users, allowing them to create, edit,
 * update, and delete their time entries. It also calculates overtime and
 * monthly limits based on the user's hourly wage and minijob earnings limit.
 * 
 * @extends Controller
 * 
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
    private int $_HOURLY_WAGE            = 14;

    /**
     * Current minijob monthly limit
     * 
     * @access private
     * @var    int
     * 
     * @default 556
     */
    private int $_LIMIT_MINIJOB          = 556;

    /**
     * Default break time in minutes
     * 
     * @access private
     * @var    int
     * 
     * @default 15
     */
    private int $_DEFAULT_BREAK_TIME     = 15;

    /**
     * Number of minutes in an hour
     * 
     * @access private
     * @var    int
     */
    private int $_HOUR_IN_MINUTES        = 60;

    /**
     * Time selection date key for eloquent queries.
     * 
     * @access private
     * @var    string
     */
    private string $_TIME_SELECTION_DATE = 'date';

    /**
     * Display the form for creating a new time entry.
     * 
     * @param Request $request Request parameters
     * 
     * @access public
     * @return View
     */
    public function create(Request $request) : View {
        $month                      = $request->input('month', now()->month);
        $year                       = $request->input('year', now()->year);
        $lang                       = $request->input('lang') ?? 'en';
        App::setLocale($lang);
        $selectedMonth              = $month;
        $selectedYear               = $year;
        $currentYear                = now()->year;
        $months                     = __('messages.months');

        $entries                    = TimeEntry::whereMonth($this->_TIME_SELECTION_DATE, $month)
            ->whereYear($this->_TIME_SELECTION_DATE, $year)
            ->orderBy($this->_TIME_SELECTION_DATE, 'desc')
            ->get();

        // Calculate overtime till last month
        $entriesTillLastMonth       = TimeEntry::where(
            function ($query) use ($year, $month) {
                $query->where($this->_TIME_SELECTION_DATE, '<', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01");
            }
        )->get();

        $monthsCountTillLastMonth   = $entriesTillLastMonth->groupBy(
            function ($item) {
                return Carbon::parse($item->date)->format('Y-m');
            }
        )->count();

        $monthlyLimit               = $this->calculateMonthlyLimit();
        $totalWorkedTillLastMonth   = $entriesTillLastMonth->sum('hours_worked');
        $totalLimitTillLastMonth    = $monthsCountTillLastMonth * $monthlyLimit;
        $totalOvertimeTillLastMonth = max(0, $totalWorkedTillLastMonth - $totalLimitTillLastMonth);
        $totalWorked                = $entries->sum('hours_worked');
        $overtime                   = max(0, $totalWorked - $monthlyLimit);

        // Calculate total overtime for all months
        $allEntries                 = TimeEntry::all();
        $totalWorkedAll             = $allEntries->sum('hours_worked');

        // Calculate how many full months are in the data
        $monthsCount                = $allEntries->groupBy(
            function ($item) {
                return Carbon::parse($item->date)->format('Y-m');
            }
        )->count();
        $totalLimitAll              = $monthsCount * $monthlyLimit;
        $totalOvertimeAll           = max(0, $totalWorkedAll - $totalLimitAll);

        // Create range of years from first time entry + 5 years
        $firstEntry                 = TimeEntry::orderBy($this->_TIME_SELECTION_DATE, 'asc')->first();
        $firstYear                  = $firstEntry ? Carbon::parse($firstEntry->date)->year : now()->year;
        $years                      = range($firstYear, $firstYear + 4);

        // Get current payout amount
        $currentPayout              = $this->calculateCurrentPayout();

        return view(
            'create', [
                'entries'                    => $entries,
                'totalWorked'                => $totalWorked,
                'overtime'                   => $overtime,
                'monthlyLimit'               => $monthlyLimit,
                'months'                     => $months,
                'month'                      => $month,
                'year'                       => $year,
                'years'                      => $years,
                'totalWorkedAll'             => $totalWorkedAll,
                'totalOvertimeAll'           => $totalOvertimeAll,
                'totalLimitAll'              => $totalLimitAll,
                'totalOvertimeTillLastMonth' => $totalOvertimeTillLastMonth,
                'currentPayout'              => $currentPayout,
                'selectedMonth'              => $selectedMonth,
                'selectedYear'               => $selectedYear,
                'currentYear'                => $currentYear,
                'lang'                       => $lang
            ]
        );
    }

    /**
     * Store a new time entry.
     *
     * @param Request $request The request object containing parameters
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
                'end_time'      => 'required|date_format:H:i',
                'break_minutes' => 'nullable|integer|min:0|max:480',
                'working_place' => 'nullable|string|max:255'
            ]
        );

        $start         = $this->_parseTime($request->start_time);
        $end           = $this->_parseTime($request->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $workingPlace  = $request->input('working_place', null);

        // Calculate total minutes worked
        $minutesWorked = $start && $end ? $start->diffInMinutes($end) : 0;
        $hours         = $minutesWorked / $this->_HOUR_IN_MINUTES;

        // Prevent negative values
        $minutesWorked = max(0, $minutesWorked);

        // Always calculate break based on hours worked
        $break         = $this->calculateMinimumBreakTime($hours);

        // If 'no_break' is set, set break_minutes to 0
        if ($request->has('no_break')) {
            $break = 0;
        }

        TimeEntry::create(
            [
                'date'          => $request->date,
                'start_time'    => $request->start_time,
                'end_time'      => $request->end_time,
                'break_minutes' => $break,
                'working_place' => $workingPlace
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
        $entryToEdit                = TimeEntry::findOrFail($id);

        // Calculate total overtime for all months
        $allEntries                 = TimeEntry::all();
        $totalWorkedAll             = $allEntries->sum('hours_worked');
        $monthlyLimit               = $this->calculateMonthlyLimit();

        // Calculate how many full months are in the data
        $monthsCount                = $allEntries->groupBy(
            function ($item) {
                return Carbon::parse($item->date)->format('Y-m');
            }
        )->count();
        $totalLimitAll              = $monthsCount * $monthlyLimit;

        // Get current month/year for entries list
        $month         = request('month', now()->month);
        $year          = request('year', now()->year);
        $lang          = request('lang', 'en');
        $selectedMonth = $month;
        $selectedYear  = $year;
        $months        = __('messages.months');

        $entries = TimeEntry::whereMonth($this->_TIME_SELECTION_DATE, $month)
            ->whereYear($this->_TIME_SELECTION_DATE, $year)
            ->orderBy($this->_TIME_SELECTION_DATE, 'desc')
            ->get();

        // Calculate overtime till last month
        $entriesTillLastMonth       = TimeEntry::where(
            function ($query) use ($year, $month) {
                $query->where($this->_TIME_SELECTION_DATE, '<', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01");
            }
        )->get();

        $monthsCountTillLastMonth   = $entriesTillLastMonth->groupBy(
            function ($item) {
                return Carbon::parse($item->date)->format('Y-m');
            }
        )->count();

        $monthlyLimit               = $this->calculateMonthlyLimit();
        $totalWorkedTillLastMonth   = $entriesTillLastMonth->sum('hours_worked');
        $totalLimitTillLastMonth    = $monthsCountTillLastMonth * $monthlyLimit;
        $totalOvertimeTillLastMonth = max(0, $totalWorkedTillLastMonth - $totalLimitTillLastMonth);
        $totalWorked                = $entries->sum('hours_worked');
        $totalLimitAll              = $monthsCount * $monthlyLimit;
        $totalOvertimeAll           = max(0, $totalWorkedAll - $totalLimitAll);
        $overtime                   = max(0, $totalWorked - $monthlyLimit);

        // Create range of years from first time entry + 5 years
        $firstEntry                 = TimeEntry::orderBy($this->_TIME_SELECTION_DATE, 'asc')->first();
        $firstYear                  = $firstEntry ? Carbon::parse($firstEntry->date)->year : now()->year;
        $years                      = range($firstYear, $firstYear + 4);

        // Get current payout amount
        $currentPayout              = $this->calculateCurrentPayout();

        return view(
            'create', [
                'entries'                    => $entries,
                'totalWorked'                => $totalWorked,
                'overtime'                   => $overtime,
                'monthlyLimit'               => $monthlyLimit,
                'month'                      => $month,
                'year'                       => $year,
                'years'                      => $years,
                'entryToEdit'                => $entryToEdit,
                'selectedMonth'              => $selectedMonth,
                'selectedYear'               => $selectedYear,
                'currentYear'                => now()->year,
                'months'                     => $months,
                'lang'                       => $lang,
                'totalWorkedAll'             => $totalWorkedAll,
                'totalOvertimeAll'           => $totalOvertimeAll,
                'totalOvertimeTillLastMonth' => $totalOvertimeTillLastMonth,
                'totalLimitAll'              => $totalLimitAll,
                'currentPayout'              => $currentPayout
            ]
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
                'end_time'      => 'required|date_format:H:i',
                'break_minutes' => 'nullable|integer|min:0|max:480'
            ]
        );

        $start         = $this->_parseTime($request->start_time);
        $end           = $this->_parseTime($request->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        // Calculate minutes worked (without break)
        $minutesWorked = $start && $end ? $start->diffInMinutes($end) : 0;
        $hours         = $minutesWorked / $this->_HOUR_IN_MINUTES;
        $break         = $this->calculateMinimumBreakTime($hours);

        // If 'no_break' is set, set break_minutes to 0
        if ($request->has('no_break')) {
            $break = 0;
        }

        $entry = TimeEntry::findOrFail($id);
        $entry->update(
            [
                'date'          => $request->date,
                'start_time'    => $request->start_time,
                'end_time'      => $request->end_time,
                'break_minutes' => $break,
                'working_place' => $request->input('working_place', null)
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
            ->route(
                'time-entry.create', [
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
        return (int) $this->_getSetting('hourly_wage', 14);
    }
    /**
     * Get the limit for minijob earnings.
     *
     * @access public
     * @return int
     */
    public function getMinijobLimit() : int {
        return (int) $this->_getSetting('minijob_limit', 556);
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
        $this->_setSetting('hourly_wage', $wage);
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
        $this->_setSetting('minijob_limit', $limit);
    }

    /**
     * Get the monthly limit for hours worked.
     *
     * @access public
     * @return int
     */
    public function calculateMonthlyLimit() : int {
        return $this->_LIMIT_MINIJOB / $this->_HOURLY_WAGE;
    }

    /**
     * Show a form to edit default values (hourly wage and minijob limit).
     *
     * @access public
     * @return View
     */
    public function getEditDefaultsView() : View {
        $hourlyWage   = $this->getHourlyWage();
        $minijobLimit = $this->getMinijobLimit();

        return view(
            'edit-defaults', [
                'hourlyWage'   => $hourlyWage,
                'minijobLimit' => $minijobLimit
            ]
        );
    }

    /**
     * Update default values (hourly wage and minijob limit).
     *
     * @param Request $request The request object containing parameters
     * 
     * @access public
     * @return RedirectResponse
     */
    public function updateDefaults(Request $request) : RedirectResponse {
        $request->validate(
            [
                'hourly_wage'   => 'required|integer|min:1',
                'minijob_limit' => 'required|integer|min:1'
            ]
        );

        $this->setHourlyWage((int)$request->hourly_wage);
        $this->setMinijobLimit((int)$request->minijob_limit);

        return redirect()
            ->route('edit-defaults')
            ->with('success', 'Default values updated!');
    }

    /**
     * Calculate the current payout amount based on the total hours worked and hourly wage.
     * 
     * @access public
     * @return array
     */
    public function calculateCurrentPayout() : array {
        $totalWorked  = TimeEntry::all()->sum('hours_worked');
        $hourlyWage   = $this->getHourlyWage();
        $minijobLimit = $this->getMinijobLimit();
        $maxHours     = $minijobLimit / $hourlyWage;

        // TODO:
        // Add the rest to the next months payout if the total worked hours exceed the minijob limit
        // This is a simple implementation, you might want to store the rest in a database or
        // handle it differently based on your requirements.
        // If total worked hours exceed the max hours for minijob, calculate the rest hours
        if ($totalWorked > $maxHours) {
            $restHours = $totalWorked - $maxHours;
            $this->_setSetting('rest_hours', $restHours);
        } else {
            $this->_setSetting('rest_hours', 0);
        }

        // If total worked hours multiplied by hourly wage exceeds the minijob limit,
        // calculate the rest hours and cap the payout to the minijob limit
        if ($totalWorked * $hourlyWage > $minijobLimit && $totalWorked > $maxHours) {
            $restHours = $totalWorked - $maxHours;
            $this->_setSetting('rest_hours', $restHours);
        } else {
            $this->_setSetting('rest_hours', 0);
        }

        if ($totalWorked * $hourlyWage > $minijobLimit) {
            $restHours = $totalWorked - $maxHours;

            // If total worked hours exceed the minijob limit, cap the payout to the limit
            return [
                'currentPayout' => $this->getMinijobLimit(),
                'rest'          => $restHours * $hourlyWage,
                'restHours'     => $restHours
            ];
        }

        // Calculate the payout based on total worked hours and hourly wage
        return [
            'currentPayout' => $totalWorked * $hourlyWage,
            'rest'          => 0,
            'restHours'     => 0
        ];
    }

    /**
     * Calculate break time based on hours worked.
     * 
     * @param int $hoursWorked The number of hours worked
     * 
     * @access public
     * @return int
     */
    public function calculateMinimumBreakTime(int $hoursWorked) : int {
        // Calculate break time based on hours worked
        if ($hoursWorked <= 6) {
            // 15 minutes for up to 6 hours
            return $this->_DEFAULT_BREAK_TIME;
        } else if ($hoursWorked > 6 && $hoursWorked <= 10) {
            // 30 minutes for more than 6 hours
            return $this->_DEFAULT_BREAK_TIME * 2;
        } else {
            // 45 minutes for more than 10 hours
            return $this->_DEFAULT_BREAK_TIME * 3;
        }
    }

    /**
     * Get a setting value by key, or return a default value if not found.
     * 
     * @param string $key     The key of the setting to retrieve
     * @param mixed  $default The default value to return if the setting is not found
     * 
     * @access private
     * @return int
     */
    private function _getSetting(string $key, $default) : int {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key, creating it if it does not exist.
     * 
     * @param string $key   The key of the setting to set
     * @param int    $value The value to set for the setting
     * 
     * @access private
     * @return void
     */
    private function _setSetting(string $key, int $value) : void {
        Setting::updateOrCreate(
            ['key'   => $key],
            ['value' => $value]
        );
    }

    /**
     * Helper function to parse a time string into a Carbon instance.
     *
     * @param string|null $time The time string to parse
     * 
     * @access private
     * @return \Carbon\Carbon|null
     */
    private function _parseTime($time) : ?Carbon {
        if (!$time) {
            return null;
        }

        // Try H:i:s first, then H:i
        try {
            return Carbon::parse($time);
        } catch (\Exception $e) {
            try {
                return Carbon::parse('H:i', $time);
            } catch (\Exception $e) {
                return null;
            }
        }
    }
}
