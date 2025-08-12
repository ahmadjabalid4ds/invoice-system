@php
    $invoices = $getRecord()->invoices()->latest()->get();
@endphp

@if($invoices->count() > 0)
    <div class="relative" x-data="{ open: false }">
        <!-- Dropdown trigger -->
        <button
            @click="open = !open"
            class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-900 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
            {{ $invoices->count() }} {{ Str::plural('Invoice', $invoices->count()) }}
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown content -->
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-10 w-80 mt-1 bg-white border border-gray-300 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-600"
            style="display: none;"
        >
            <div class="max-h-60 overflow-y-auto">
                @foreach($invoices as $invoice)
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        #{{ $invoice->id }}
                                    </span>
                                    @if(isset($invoice->status))
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if($invoice->status === 'paid')
                                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($invoice->status === 'pending')
                                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else
                                                bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @endif
                                        ">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $invoice->created_at->format('M d, Y') }}
                                </div>
                                @if(isset($invoice->description))
                                    <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                        {{ Str::limit($invoice->description, 40) }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($invoice->total, 2) }} SAR
                                </div>
                            </div>
                        </div>
                        @if(isset($invoice->due_date))
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Due: {{ $invoice->due_date->format('M d, Y') }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Footer with total -->
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-b-md">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Total:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($invoices->sum('total'), 2) }} SAR
                    </span>
                </div>
            </div>
        </div>
    </div>
@else
    <span class="text-xs text-gray-500 dark:text-gray-400">No invoices</span>
@endif
