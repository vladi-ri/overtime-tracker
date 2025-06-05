@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Default Values</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('update-defaults') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="hourly_wage" class="form-label">Hourly Wage</label>
            <input type="number" class="form-control" id="hourly_wage" name="hourly_wage" value="{{ old('hourly_wage', $hourlyWage) }}" required min="1">
        </div>
        <div class="mb-3">
            <label for="minijob_limit" class="form-label">Minijob Limit</label>
            <input type="number" class="form-control" id="minijob_limit" name="minijob_limit" value="{{ old('minijob_limit', $minijobLimit) }}" required min="1">
        </div>
        <div class="row">
            <div class="col-6 d-grid">
                <button type="submit" class="btn btn-primary">Save Defaults</button>
            </div>
            <div class="col-6 d-grid">
                <a href="{{ route('time-entry.create') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </form>
</div>
@endsection