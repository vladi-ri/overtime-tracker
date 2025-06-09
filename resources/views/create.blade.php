@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <h2>Track Daily Working Hours</h2>
            </div>
        </div>
    </div>
    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ isset($entryToEdit) ? route('time-entry.update', $entryToEdit->id) : route('time-entry.store') }}">
        @csrf
        @if(isset($entryToEdit))
            @method('PUT')
        @endif
        <div class="row mb-2">
            <div class="col-md-3 mb-2 mb-md-0">
                <label for="date" class="form-label">Date:</label>
                <input type="date" name="date" class="form-control" required value="{{ old('date', $entryToEdit->date ?? '') }}">
                @error('date') <span style="color:red">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label for="start_time" class="form-label">Start Time:</label>
                <input type="time" name="start_time" class="form-control" required value="{{ old('start_time', isset($entryToEdit) ? (strlen($entryToEdit->start_time) === 5 ? $entryToEdit->start_time : \Carbon\Carbon::createFromFormat('H:i:s', $entryToEdit->start_time)->format('H:i')) : '') }}">
                @error('start_time') <span style="color:red">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label for="end_time" class="form-label">End Time:</label>
                <input type="time" name="end_time" class="form-control" required value="{{ old('end_time', isset($entryToEdit) ? (strlen($entryToEdit->end_time) === 5 ? $entryToEdit->end_time : \Carbon\Carbon::createFromFormat('H:i:s', $entryToEdit->end_time)->format('H:i')) : '') }}">
                @error('end_time') <span style="color:red">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-3">
                <label for="break_minutes" class="form-label">Break (minutes):</label>
                <input type="number" name="break_minutes" class="form-control" min="0" max="480" step="1" value="{{ old('break_minutes', $entryToEdit->break_minutes ?? 0) }}">
                @error('break_minutes') <span style="color:red">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="row">
            <div class="col mb-0 d-flex justify-content-center">
                <button type="submit" class="btn btn-primary mt-2 main-button" data-action={{ isset($entryToEdit) ? 'update' : 'save' }}>
                    @if (isset($entryToEdit))
                        <i class="fa-solid fa-arrows-rotate"></i>
                    @else
                        <i class="fa fa-save"></i>
                    @endif
                    {{ isset($entryToEdit) ? 'Update Entry' : 'Save Entry' }}
                </button>
                @if(isset($entryToEdit))
                    <a href="{{ route('time-entry.create') }}" class="btn btn-secondary mt-2 ms-2">Cancel</a>
                @endif
                <input type="hidden" name="month" value="{{ request('month', now()->month) }}">
                <input type="hidden" name="year" value="{{ request('year', now()->year) }}">
            </div>
        </div>
    </form>

    <hr>

    <div class="row">
        <div class="col">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <h3>View Entries by Month & Year</h3>
            </div>
        </div>
    </div>
    <form method="GET" action="{{ route('time-entry.create') }}" class="mb-3 d-flex align-items-end gap-2 flex-wrap">
        @php
            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            $currentYear   = now()->year;
            $selectedMonth = request('month', now()->month);
            $selectedYear  = request('year', $currentYear);
        @endphp
        <div class="container m-0 p-0 my-0 px-0" role="group" aria-label="Months">
            <div class="row g-2 mb-1">
                @foreach ($months as $num => $name)
                    @if (($num - 1) % 4 === 0 && $num !== 1)
                        </div><div class="row g-2 mb-1">
                    @endif
                    <div class="col-6 col-md-3 d-grid">
                        <button type="submit" name="month" value="{{ $num }}"
                            class="btn btn-outline-secondary btn-sm {{ $selectedMonth == $num ? 'active' : '' }}">
                            {{ $name }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
        <select name="year" class="form-select form-select-sm my-2 mx-auto" data-name="year-selection" onchange="this.form.submit()">
            @for ($year = 2025; $year <= $currentYear + 2; $year++)
                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endfor
        </select>
        <noscript><button type="submit" class="btn btn-primary btn-sm">Go</button></noscript>
    </form>

    <div class="row">
        <div class="col">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <h3>Your Entries for {{ $months[$selectedMonth] }} {{ $selectedYear }}</h3>
            </div>
        </div>
    </div>
    @if(isset($entries) && $entries->count())
        <table class="table table-bordered table-striped" style="background:#fafafa;">
            <thead>
                <tr>
                    <th style="min-width: 120px;">Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Break (min)</th>
                    <th>Hours Worked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($entry->date)->format('Y-m-d') }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $entry->start_time)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $entry->end_time)->format('H:i') }}</td>
                        <td>{{ $entry->break_minutes }}</td>
                        <td>{{ $entry->hours_worked }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('time-entry.edit', ['id' => $entry->id, 'month' => request('month', now()->month), 'year' => request('year', now()->year)]) }}"
                                class="btn btn-warning btn-sm flex-fill">
                                    Edit
                                </a>
                                <form action="{{ route('time-entry.destroy', $entry->id) }}" method="POST" style="display:inline; width:100%;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm flex-fill"
                                        onclick="return confirm('Are you sure you want to delete this entry?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="fw-bold">
            Total hours: {{ $entries->sum('hours_worked') }}
        </div>
    @else
        <p>No entries yet.</p>
    @endif

    {{-- Show overtime counter --}}
    <div class="alert {{ $overtime > 0 ? 'alert-danger' : 'alert-success' }} mt-3">
        <strong>This month:</strong>
        {{ number_format($totalWorked, 2) }} / {{ $monthlyLimit }} hours
        <br>
        <strong>Overtime this month:</strong> {{ number_format($overtime, 2) }} hours
    </div>
    <div class="alert {{ $totalOvertimeAll > 0 ? 'alert-danger' : 'alert-success' }}">
        <strong>Total tracked:</strong>
        {{ number_format($totalWorkedAll, 2) }} / {{ $totalLimitAll }} hours
        <br>
        <strong>Total overtime:</strong> {{ number_format($totalOvertimeAll, 2) }} hours
    </div>
    <div class="alert {{ $totalOvertimeTillLastMonth > 0 ? 'alert-danger' : 'alert-success' }}">
        <strong>Total overtime till last month:</strong>
        {{ number_format($totalOvertimeTillLastMonth, 2) }} hours
    </div>

    <div class="mt-3 d-flex justify-content-center">
        <a href="{{ route('edit-defaults') }}" class="btn btn-secondary main-button">
            <i class="fa fa-edit"></i>
            Edit Default Values
        </a>
    </div>
</div>
@endsection