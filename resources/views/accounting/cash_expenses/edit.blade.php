@extends('layouts.app')

@section('title', 'Edit Cash Expense')

@section('content')
    <div class="container" style="max-width:600px">
        <h4 class="mb-3">Edit Cash Expense</h4>

        <form method="POST" action="{{ route('accounting.cash-expenses.update', $cashExpense) }}">
            @csrf
            @method('PUT')

            @include('accounting.cash_expenses.partials.form', ['cashExpense' => $cashExpense])

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('accounting.cash-expenses.show', $cashExpense) }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
@endsection
