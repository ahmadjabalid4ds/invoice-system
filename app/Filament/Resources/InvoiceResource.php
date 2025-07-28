<?php

namespace App\Filament\Resources;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Support\Collection;
use TomatoPHP\FilamentAccounts\Components\AccountColumn;
use TomatoPHP\FilamentAccounts\Models\Account;
use TomatoPHP\FilamentEcommerce\Facades\FilamentEcommerce;
use TomatoPHP\FilamentEcommerce\Models\Branch;
use TomatoPHP\FilamentEcommerce\Models\Company;
use TomatoPHP\FilamentEcommerce\Models\Coupon;
use TomatoPHP\FilamentEcommerce\Models\Product;
use TomatoPHP\FilamentInvoices\Facades\FilamentInvoices;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStatsWidget;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Events\SendWhatsappEvent;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TomatoPHP\FilamentLocations\Models\City;
use TomatoPHP\FilamentLocations\Models\Country;
use TomatoPHP\FilamentLocations\Models\Currency;
use TomatoPHP\FilamentTypes\Components\TypeColumn;
use App\Utils\Helper;
use TomatoPHP\FilamentTypes\Models\Type;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';


    public static function getNavigationLabel(): string
    {
        return trans('messages.invoices.title');
    }

    public static function getPluralLabel(): ?string
    {
        return trans('messages.invoices.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('messages.invoices.group');
    }

    public static function getLabel(): ?string
    {
        return trans('messages.invoices.title');
    }

    public static function getWidgets(): array
    {
        return [
            InvoiceStatsWidget::class
        ];
    }

    public static function form(Form $form): Form
    {
        $types = Type::query()
            ->where('for', 'invoices')
            ->where('type', 'type');

        $statues = Type::query()
            ->where('for', 'invoices')
            ->where('type', 'status');

        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->unique(ignoreRecord: true)
                    ->disabled(fn(Invoice $invoice) => $invoice->exists)
                    ->label(trans('messages.invoices.columns.uuid'))
                    ->default(fn() => 'INV-' . \Illuminate\Support\Str::random(8))
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),

                Forms\Components\Grid::make([
                    'sm' => 1,
                    'lg' => 12,
                ])->schema([
                    Forms\Components\Section::make(trans('messages.invoices.sections.from_type.title'))
                        ->schema([
                            Forms\Components\Select::make('from_type')
                                ->label(trans('messages.invoices.sections.from_type.columns.from_type'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->options(FilamentInvoices::getFrom()->pluck('label', 'model')->toArray())
                                ->columnSpanFull(),
                            Forms\Components\Select::make('from_id')
                                ->label(trans('messages.invoices.sections.from_type.columns.from'))
                                ->required()
                                ->searchable()
                                ->disabled(fn(Forms\Get $get) => !$get('from_type'))
                                ->options(fn(Forms\Get $get) => $get('from_type') ? $get('from_type')::query()->pluck(FilamentInvoices::getFrom()->where('model', $get('from_type'))->first()?->column ?? 'name', 'id')->toArray() : [])
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpan(6)
                        ->collapsible()
                        ->collapsed(fn($record) => $record),
                    Forms\Components\Section::make(trans('messages.invoices.sections.billed_from.title'))
                        ->schema([
                            Forms\Components\Select::make('for_type')
                                ->label(trans('messages.invoices.sections.billed_from.columns.for_type'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->options(FilamentInvoices::getFor()->pluck('label', 'model')->toArray())
                                ->columnSpanFull(),
                            Forms\Components\Select::make('for_id')
                                ->label(trans('messages.invoices.sections.billed_from.columns.for'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    $forType = $get('for_type');
                                    $forId = $get('for_id');
                                    if ($forType && $forId) {
                                        $for = $forType::find($forId);
                                        $set('name', $for->name);
                                        $set('phone', $for->phone);
                                        $set('address', $for->address);
                                    }
                                })
                                ->disabled(fn(Forms\Get $get) => !$get('for_type'))
                                ->options(fn(Forms\Get $get) => $get('for_type') ? $get('for_type')::query()->pluck(FilamentInvoices::getFor()->where('model', $get('for_type'))->first()?->column ?? 'name', 'id')->toArray() : [])
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpan(6)
                        ->collapsible()
                        ->collapsed(fn($record) => $record),
                    Forms\Components\Section::make(trans('messages.invoices.sections.customer_data.title'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(trans('messages.invoices.sections.customer_data.columns.name')),
                            Forms\Components\TextInput::make('phone')
                                ->label(trans('messages.invoices.sections.customer_data.columns.phone')),
                            Forms\Components\Textarea::make('address')
                                ->label(trans('messages.invoices.sections.customer_data.columns.address')),
                        ])
                        ->columns(1)
                        ->columnSpan(6)
                        ->collapsible()
                        ->collapsed(fn($record) => $record),
                    Forms\Components\Section::make(trans('messages.invoices.sections.invoice_data.title'))
                        ->schema([
                            Forms\Components\DatePicker::make('date')
                                ->label(trans('messages.invoices.sections.invoice_data.columns.date'))
                                ->required()
                                ->default(Carbon::now()),
                            Forms\Components\DatePicker::make('due_date')
                                ->label(trans('messages.invoices.sections.invoice_data.columns.due_date'))
                                ->required()
                                ->default(Carbon::now()),
                            Forms\Components\Select::make('type')
                                ->label(trans('messages.invoices.sections.invoice_data.columns.type'))
                                ->required()
                                ->default('push')
                                ->searchable()
                                ->options($types->pluck('name', 'key')->toArray()),
                            Forms\Components\Select::make('status')
                                ->label(trans('messages.invoices.sections.invoice_data.columns.status'))
                                ->required()
                                ->default('draft')
                                ->searchable()
                                ->options($statues->pluck('name', 'key')->toArray()),
                            Forms\Components\Select::make('currency_id')
                                ->label(trans('messages.invoices.sections.invoice_data.columns.currency'))
                                ->required()
                                ->columnSpanFull()
                                ->default(Currency::query()->where('iso', 'USD')->first()?->id)
                                ->searchable()
                                ->options(Currency::query()->pluck('name', 'id')->toArray()),
                        ])
                        ->columns(2)
                        ->columnSpan(6)
                        ->collapsible()
                        ->collapsed(fn($record) => $record),
                ]),
                Forms\Components\Repeater::make('items')
                    ->hiddenLabel()
                    ->collapsible()
                    ->collapsed(fn($record) => $record)
                    ->cloneable()
                    ->relationship('invoicesItems')
                    ->label(trans('messages.invoices.columns.items'))
                    ->itemLabel(trans('messages.invoices.columns.item'))
                    ->schema([
                        Forms\Components\TextInput::make('item')
                            ->label(trans('messages.invoices.columns.item_name'))
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('description')
                            ->label(trans('messages.invoices.columns.description'))
                            ->columnSpan(8),
                        Forms\Components\TextInput::make('qty')
                            ->live()
                            ->columnSpan(2)
                            ->label(trans('messages.invoices.columns.qty'))
                            ->default(1)
                            ->numeric(),
                        Forms\Components\TextInput::make('price')
                            ->label(trans('messages.invoices.columns.price'))
                            ->columnSpan(3)
                            ->default(0)
                            ->numeric(),
                        Forms\Components\TextInput::make('discount')
                            ->label(trans('messages.invoices.columns.discount'))
                            ->columnSpan(2)
                            ->default(0)
                            ->numeric(),
                        Forms\Components\TextInput::make('vat')
                            ->label(trans('messages.invoices.columns.vat'))
                            ->columnSpan(2)
                            ->default(0)
                            ->numeric(),
                        Forms\Components\TextInput::make('total')
                            ->label(trans('messages.invoices.columns.total'))
                            ->columnSpan(3)
                            ->default(0)
                            ->numeric(),
                    ])
                    ->lazy()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        $items = $get('items');
                        $total = 0;
                        $discount = 0;
                        $vat = 0;
                        $collectItems = [];
                        foreach ($items as $invoiceItem) {
                            $getTotal = ((($invoiceItem['price'] + $invoiceItem['vat']) - $invoiceItem['discount']) * $invoiceItem['qty']);
                            $total += $getTotal;
                            $invoiceItem['total'] = $getTotal;
                            $discount += ($invoiceItem['discount'] * $invoiceItem['qty']);
                            $vat +=  ($invoiceItem['vat'] * $invoiceItem['qty']);

                            $collectItems[] = $invoiceItem;
                        }
                        $set('total', $total);
                        $set('discount', $discount);
                        $set('vat', $vat);

                        $set('items', $collectItems);
                    })
                    ->columns(12)
                    ->columnSpanFull(),
                Forms\Components\Section::make(trans('messages.invoices.sections.totals.title'))
                    ->schema([
                        Forms\Components\TextInput::make('shipping')
                            ->lazy()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $items = $get('items');
                                $total = 0;
                                foreach ($items as $invoiceItem) {
                                    $total += ((($invoiceItem['price'] + $invoiceItem['vat']) - $invoiceItem['discount']) * $invoiceItem['qty']);
                                }

                                $set('total', $total + (int)$get('shipping'));
                            })
                            ->label(trans('messages.invoices.columns.shipping'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('vat')
                            ->disabled()
                            ->label(trans('messages.invoices.columns.vat'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('discount')
                            ->disabled()
                            ->label(trans('messages.invoices.columns.discount'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total')
                            ->disabled()
                            ->label(trans('messages.invoices.columns.total'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label(trans('messages.invoices.columns.notes'))
                            ->columnSpanFull(),
                    ])->collapsible()->collapsed(fn($record) => $record),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label(trans('messages.invoices.columns.uuid'))
                    ->description(fn($record) => $record->type . ' ' . trans('messages.invoices.columns.by') . ' ' . $record->user?->name)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('for_id')
                    ->state(fn($record) => $record->for_type::find($record->for_id)?->name)
                    ->description(fn($record) => trans('messages.invoices.columns.from') . ': ' . $record->from_type::find($record->from_id)?->name)
                    ->label(trans('messages.invoices.columns.account'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(trans('messages.invoices.columns.date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(trans('messages.invoices.columns.due_date'))
                    ->tooltip(fn($record) => $record->due_date->isFuture() ? $record->due_date->diffForHumans() : ($record->due_date->isToday() ? 'Due Today!' : 'Over Due!'))
                    ->color(fn($record) => $record->due_date->isFuture() ? 'success' : ($record->due_date->isToday() ? 'warning' : 'danger'))
                    ->icon(fn($record) => $record->due_date->isFuture() ? 'heroicon-s-check-circle' : ($record->due_date->isToday() ? 'heroicon-s-exclamation-circle' : 'heroicon-s-x-circle'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TypeColumn::make('status')
                    ->label(trans('messages.invoices.columns.status'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('messages.invoices.columns.name'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn($record) => $record->phone)
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(trans('messages.invoices.columns.phone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label(trans('messages.invoices.columns.address'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shipping')
                    ->label(trans('messages.invoices.columns.shipping'))
                    ->money(locale: 'en', currency: (fn($record) => $record->currency?->iso))
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat')
                    ->label(trans('messages.invoices.columns.vat'))
                    ->money(locale: 'en', currency: (fn($record) => $record->currency?->iso))
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label(trans('messages.invoices.columns.discount'))
                    ->money(locale: 'en', currency: (fn($record) => $record->currency?->iso))
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label(trans('messages.invoices.columns.total'))
                    ->money(locale: 'en', currency: (fn($record) => $record->currency?->iso))
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid')
                    ->label(trans('messages.invoices.columns.paid'))
                    ->money(locale: 'en', currency: (fn($record) => $record->currency?->iso))
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(trans('messages.invoices.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Type::query()->where('for', 'invoices')->where('type', 'status')->pluck('name', 'key')->toArray())
                    ->label(trans('messages.invoices.filters.status'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(Type::query()->where('for', 'invoices')->where('type', 'type')->pluck('name', 'key')->toArray())
                    ->label(trans('messages.invoices.filters.type'))
                    ->searchable(),
                Tables\Filters\Filter::make('due')
                    ->form([
                        Forms\Components\Toggle::make('overdue')
                            ->label(trans('messages.invoices.filters.due.columns.overdue')),
                        Forms\Components\Toggle::make('today')
                            ->label(trans('messages.invoices.filters.due.columns.today')),
                    ])
                    ->label(trans('messages.invoices.filters.due.label'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['overdue'], function (Builder $query, $value) {
                            if ($value) {
                                $query->whereDate('due_date', '<', Carbon::now());
                            }
                        })->when($data['today'], function (Builder $query, $value) {
                            if ($value) {
                                $query->whereDate('due_date', Carbon::today());
                            }
                        });
                    }),
                Tables\Filters\Filter::make('for_id')
                    ->form([
                        Forms\Components\Select::make('for_type')
                            ->searchable()
                            ->live()
                            ->options(FilamentInvoices::getFor()->pluck('label', 'model')->toArray())
                            ->label(trans('messages.invoices.filters.for.columns.for_type')),
                        Forms\Components\Select::make('for_id')
                            ->searchable()
                            ->options(fn(Forms\Get $get) => $get('for_type') ? $get('for_type')::query()->pluck(FilamentInvoices::getFor()->where('model', $get('for_type'))->first()?->column ?? 'name', 'id')->toArray() : [])
                            ->label(trans('messages.invoices.filters.for.columns.for_name')),
                    ])
                    ->label(trans('messages.invoices.filters.for.label'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['for_type'], function (Builder $query, $value) {
                            if ($value) {
                                $query->where('for_type', $value);
                            }
                        })->when($data['for_id'], function (Builder $query, $value) {
                            if ($value) {
                                $query->where('for_id', $value);
                            }
                        });
                    }),
                Tables\Filters\Filter::make('from_id')
                    ->form([
                        Forms\Components\Select::make('from_type')
                            ->searchable()
                            ->live()
                            ->options(FilamentInvoices::getFor()->pluck('label', 'model')->toArray())
                            ->label(trans('messages.invoices.filters.from.columns.from_type')),
                        Forms\Components\Select::make('from_id')
                            ->searchable()
                            ->options(fn(Forms\Get $get) => $get('from_type') ? $get('from_type')::query()->pluck(FilamentInvoices::getFrom()->where('model', $get('from_type'))->first()?->column ?? 'name', 'id')->toArray() : [])
                            ->label(trans('messages.invoices.filters.from.columns.from_name')),
                    ])
                    ->label(trans('messages.invoices.filters.from.label'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['from_type'], function (Builder $query, $value) {
                            if ($value) {
                                $query->where('from_type', $value);
                            }
                        })->when($data['from_id'], function (Builder $query, $value) {
                            if ($value) {
                                $query->where('from_id', $value);
                            }
                        });
                    }),
            ])
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('send_whatsapp')
                ->hidden(fn($record) => ($record->total === $record->paid) || $record->status === 'paid' || $record->status === 'estimate')
                ->requiresConfirmation()
                ->iconButton()
                ->color('success')
                ->action(function (array $data, Invoice $record) {
                    // $record->update([
                    //     'paid' => $record->paid + $data['amount']
                    // ]);

                    // $record->invoiceMetas()->create([
                    //     'key' => 'payments',
                    //     'value' => $data['amount']
                    // ]);
                    $record->update([
                        'token' => Helper::generateToken()
                    ]);

                    event(new SendWhatsappEvent($record));
                    $record->invoiceLogs()->create([
                        // 'log' => 'Paid ' . number_format($data['amount'], 2) . ' ' . $record->currency->iso . ' By: ' . auth()->user()->name,
                        'log' => 'Sent to whatsapp ' . number_format($record->total, 2) . ' ' . $record->currency->iso . ' By: ' . auth()->user()->name . ' to number ' . $record->phone,
                        'type' => 'payment',
                    ]);

                    // if ($record->total === $record->paid) {
                    //     $record->update([
                    //         'status' => 'paid'
                    //     ]);
                    // }

                    Notification::make()
                        ->title(trans('messages.invoices.actions.pay.notification.title'))
                        ->body(trans('messages.invoices.actions.pay.notification.body'))
                        ->success()
                        ->send();
                })
                ->icon('heroicon-s-device-phone-mobile')
                ->label(trans('messages.invoices.actions.pay.label'))
                ->modalHeading(trans('messages.invoices.actions.pay.label'))
                ->tooltip(trans('messages.invoices.actions.pay.label')),

                Tables\Actions\Action::make('pay')
                    ->hidden(fn($record) => ($record->total === $record->paid) || $record->status === 'paid' || $record->status === 'estimate')
                    ->requiresConfirmation()
                    ->iconButton()
                    ->color('info')
                    ->fillForm(fn($record) => [
                        'total' => $record->total,
                        'paid' => $record->paid,
                        'amount' => $record->total - $record->paid,
                    ])
                    ->form([
                        Forms\Components\TextInput::make('total')
                            ->label(trans('messages.invoices.actions.total'))
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('paid')
                            ->label(trans('messages.invoices.actions.paid'))
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label(trans('messages.invoices.actions.amount'))
                            ->required()
                            ->numeric(),
                    ])
                    ->action(function (array $data, Invoice $record) {
                        $record->update([
                            'paid' => $record->paid + $data['amount']
                        ]);

                        $record->invoiceMetas()->create([
                            'key' => 'payments',
                            'value' => $data['amount']
                        ]);

                        $record->invoiceLogs()->create([
                            'log' => 'Paid ' . number_format($data['amount'], 2) . ' ' . $record->currency->iso . ' By: ' . auth()->user()->name,
                            'type' => 'payment',
                        ]);

                        if ($record->total === $record->paid) {
                            $record->update([
                                'status' => 'paid'
                            ]);
                        }

                        Notification::make()
                            ->title(trans('messages.invoices.actions.pay.notification.title'))
                            ->body(trans('messages.invoices.actions.pay.notification.body'))
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-s-credit-card')
                    ->label(trans('messages.invoices.actions.pay.label'))
                    ->modalHeading(trans('messages.invoices.actions.pay.label'))
                    ->tooltip(trans('messages.invoices.actions.pay.label')),

                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip(trans('messages.invoices.actions.view_invoice')),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip(trans('messages.invoices.actions.edit_invoice')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-s-archive-box')
                    ->label(trans('messages.invoices.actions.archive_invoice'))
                    ->modalHeading(trans('messages.invoices.actions.archive_invoice'))
                    ->tooltip(trans('messages.invoices.actions.archive_invoice')),
                Tables\Actions\ForceDeleteAction::make()
                    ->iconButton()
                    ->tooltip(trans('messages.invoices.actions.delete_invoice_forever')),
                Tables\Actions\RestoreAction::make()
                    ->iconButton()
                    ->tooltip(trans('messages.invoices.actions.restore_invoice')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('status')
                        ->label(trans('messages.invoices.actions.status.label'))
                        ->tooltip(trans('messages.invoices.actions.status.tooltip'))
                        ->icon('heroicon-s-cursor-arrow-rays')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Forms\Components\Select::make('status')
                                ->searchable()
                                ->options(Type::query()->where('for', 'invoices')->where('type', 'status')->pluck('name', 'key')->toArray())
                                ->label(trans('messages.invoices.actions.status.title'))
                                ->default('draft')
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));

                            Notification::make()
                                ->title(trans('messages.invoices.actions.status.notification.title'))
                                ->body(trans('messages.invoices.actions.status.notification.body'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvoiceLogManager::make(),
            RelationManagers\InvoicePaymentsManager::make(),
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}/show'),
        ];
    }
}
