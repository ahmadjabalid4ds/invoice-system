@extends('layout')

@section('title', 'Payment Successful')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle fa-4x"></i>
                        </div>
                        <h2 class="card-title text-success">Payment Successful!</h2>
                        <p class="card-text">Your payment has been processed successfully.</p>

                        @if(isset($transaction_id))
                            <p><strong>Transaction ID:</strong> {{ $transaction_id }}</p>
                        @endif

                        @if(isset($amount))
                            <p><strong>Amount:</strong> ${{ number_format($amount, 2) }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
