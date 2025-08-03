@extends('layout')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-danger mb-3">
                            <i class="fas fa-times-circle fa-4x"></i>
                        </div>
                        <h2 class="card-title text-danger">Payment Failed</h2>
                        <p class="card-text">We're sorry, but your payment could not be processed.</p>

                        @if(isset($error_message))
                            <div class="alert alert-danger">
                                {{ $error_message }}
                            </div>
                        @endif

                        @if(isset($order_id))
                            <p><strong>Order ID:</strong> {{ $order_id }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
