<?php

namespace App\Filament\Resources;

use App\Events\SendWhatsappEvent;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStatsWidget;
use App\Models\Invoice;
use App\Models\SystemSetting;
use App\Utils\Helper;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TomatoPHP\FilamentInvoices\Facades\FilamentInvoices;
use TomatoPHP\FilamentLocations\Models\Currency;
use TomatoPHP\FilamentTypes\Components\TypeColumn;
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
        return [InvoiceStatsWidget::class];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::getUuidField(),
            static::getMainFormGrid(),
            static::getItemsRepeater(),
            static::getTotalsSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getBulkActions())
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->defaultSort('created_at', 'desc');
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

    // ==================== FORM COMPONENTS ====================

    protected static function getUuidField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('uuid')
            ->unique(ignoreRecord: true)
            ->disabled(fn(Invoice $invoice) => $invoice->exists)
            ->label(trans('messages.invoices.columns.uuid'))
            ->default(fn() => 'INV-' . \Illuminate\Support\Str::random(8))
            ->required()
            ->columnSpanFull()
            ->maxLength(255);
    }

    protected static function getMainFormGrid(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(['sm' => 1, 'lg' => 12])
            ->schema([
                static::getFromSection(),
                static::getBilledFromSection(),
                static::getCustomerDataSection(),
                static::getInvoiceDataSection(),
            ]);
    }

    protected static function getFromSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(trans('messages.invoices.sections.from_type.title'))
            ->schema([
                Forms\Components\Select::make('from_type')
                    ->label(trans('messages.invoices.sections.from_type.columns.from_type'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->options(FilamentInvoices::getFrom()->pluck('label', 'model')->toArray())
                    ->columnSpanFull()
                    ->disabled()
                    ->default('Tenant')
                    ->dehydrated(true), // Ensure the value is saved

                Forms\Components\Select::make('from_id')
                    ->label(trans('messages.invoices.sections.from_type.columns.from'))
                    ->required()
                    ->disabled()
                    ->options(function (Forms\Get $get) {
                        try {
                            $fromType = $get('from_type') ?: 'Tenant';
                            $modelClass = "App\\Models\\" . $fromType;

                            if (!class_exists($modelClass)) {
                                return [];
                            }

                            $tenantId = auth()->user()?->tenant_id;
                            if (!$tenantId) {
                                return [];
                            }

                            return $modelClass::query()
                                ->where('id', $tenantId)
                                ->pluck('name', 'id')
                                ->toArray();
                        } catch (\Exception $e) {
                            return [];
                        }
                    })
                    ->default(function () {
                        return auth()->user()?->tenant_id;
                    })
                    ->dehydrated(true) // Ensure the value is saved
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpan(6)
            ->collapsible()
            ->collapsed(fn($record) => $record);
    }

    protected static function getBilledFromSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(trans('messages.invoices.sections.billed_from.title'))
            ->schema([
                Forms\Components\Select::make('for_type')
                    ->label(trans('messages.invoices.sections.billed_from.columns.for_type'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabled()
                    ->default('Customer')
                    ->options(FilamentInvoices::getFor()->pluck('label', 'model')->toArray())
                    ->columnSpanFull(),

                Forms\Components\Select::make('for_id')
                    ->label(trans('messages.invoices.sections.billed_from.columns.for'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(static::getCustomerDataUpdater())
                    ->disabled(fn(Forms\Get $get) => !$get('for_type'))
                    ->options(function (Forms\Get $get) {
                        if (!$get('for_type')) {
                            return [];
                        }

                        $config = FilamentInvoices::getFor()->where('model', $get('for_type'))->first();
                        $column = $config?->column ?? 'name';
                        $user = auth()->user();

                        return ("App\\Models\\" . $get('for_type'))::query()->where('tenant_id', $user->tenant_id)->pluck($column, 'id')->toArray();
                    })
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpan(6)
            ->collapsible()
            ->collapsed(fn($record) => $record);
    }

    protected static function getCustomerDataSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(trans('messages.invoices.sections.customer_data.title'))
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
            ->collapsed(fn($record) => $record);
    }

    protected static function getInvoiceDataSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(trans('messages.invoices.sections.invoice_data.title'))
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
                    ->options(static::getInvoiceTypes()),

                Forms\Components\Select::make('status')
                    ->label(trans('messages.invoices.sections.invoice_data.columns.status'))
                    ->required()
                    ->default('draft')
                    ->searchable()
                    ->options(static::getInvoiceStatuses()),

                Forms\Components\Select::make('currency_id')
                    ->label(trans('messages.invoices.sections.invoice_data.columns.currency'))
                    ->required()
                    ->columnSpanFull()
                    ->default(Currency::query()->where('iso', 'USD')->first()?->id)
                    ->searchable()
                    ->disabled()
                    ->default(DB::table('currencies')->where('iso', 'SAR')->first()->id)
                    ->options(Currency::query()->pluck('name', 'id')->toArray()),
            ])
            ->columns(2)
            ->columnSpan(6)
            ->collapsible()
            ->collapsed(fn($record) => $record);
    }

    protected static function getItemsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('items')
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
                    ->default(SystemSetting::latest()->first()?->vat_percentage ?? 15)
                    ->disabled()
                    ->numeric(),

                Forms\Components\TextInput::make('total')
                    ->label(trans('messages.invoices.columns.total'))
                    ->columnSpan(3)
                    ->default(0)
                    ->numeric(),
            ])
            ->lazy()
            ->afterStateUpdated(static::getItemTotalsCalculator())
            ->columns(12)
            ->columnSpanFull();
    }

    protected static function getTotalsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make(trans('messages.invoices.sections.totals.title'))
            ->schema([
                Forms\Components\TextInput::make('shipping')
                    ->lazy()
                    ->afterStateUpdated(static::getShippingCalculator())
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
            ])
            ->collapsible()
            ->collapsed(fn($record) => $record);
    }

    // ==================== TABLE COMPONENTS ====================

    protected static function getTableColumns(): array
    {
        return [
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
                ->tooltip(static::getDueDateTooltip())
                ->color(static::getDueDateColor())
                ->icon(static::getDueDateIcon())
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
                ->money(locale: 'en', currency: fn($record) => $record->currency?->iso)
                ->color('warning')
                ->sortable(),

            Tables\Columns\TextColumn::make('vat')
                ->label(trans('messages.invoices.columns.vat'))
                ->money(locale: 'en', currency: fn($record) => $record->currency?->iso)
                ->color('warning')
                ->sortable(),

            Tables\Columns\TextColumn::make('discount')
                ->label(trans('messages.invoices.columns.discount'))
                ->money(locale: 'en', currency: fn($record) => $record->currency?->iso)
                ->color('danger')
                ->sortable(),

            Tables\Columns\TextColumn::make('total')
                ->label(trans('messages.invoices.columns.total'))
                ->money(locale: 'en', currency: fn($record) => $record->currency?->iso)
                ->color('success')
                ->sortable(),

            Tables\Columns\TextColumn::make('paid')
                ->label(trans('messages.invoices.columns.paid'))
                ->money(locale: 'en', currency: fn($record) => $record->currency?->iso)
                ->color('info')
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label(trans('messages.invoices.columns.updated_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TrashedFilter::make(),

            Tables\Filters\SelectFilter::make('status')
                ->options(static::getInvoiceStatuses())
                ->label(trans('messages.invoices.filters.status'))
                ->searchable(),

            Tables\Filters\SelectFilter::make('type')
                ->options(static::getInvoiceTypes())
                ->label(trans('messages.invoices.filters.type'))
                ->searchable(),

            static::getDueFilter(),
            static::getForFilter(),
            static::getFromFilter(),
        ];
    }

    protected static function getTableActions(): array
    {
        return [
            static::getSendWhatsappAction(),
            static::getPayAction(),
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
        ];
    }

    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getStatusBulkAction(),
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]),
        ];
    }

    // ==================== HELPER METHODS ====================

    protected static function getInvoiceTypes(): array
    {
        return Type::query()
            ->where('for', 'invoices')
            ->where('type', 'type')
            ->pluck('name', 'key')
            ->toArray();
    }

    protected static function getInvoiceStatuses(): array
    {
        return Type::query()
            ->where('for', 'invoices')
            ->where('type', 'status')
            ->pluck('name', 'key')
            ->toArray();
    }

    protected static function getCustomerDataUpdater(): \Closure
    {
        return function (Forms\Get $get, Forms\Set $set) {
            $forType = $get('for_type');
            $forId = $get('for_id');

            if ($forType && $forId) {
                $for = ("App\\Models\\" . $forType)::find($forId);
                if ($for) {
                    $set('name', $for->name ?? null);
                    $set('phone', $for->phone ?? null);
                    $set('address', $for->address ?? null);
                }
            }
        };
    }

    protected static function getItemTotalsCalculator(): \Closure
    {
        return function (Forms\Get $get, Forms\Set $set) {
            $items = $get('items') ?? [];
            $total = 0;
            $discount = 0;
            $vat = 0;
            $collectItems = [];

            foreach ($items as $invoiceItem) {
                $qty = (float) ($invoiceItem['qty'] ?? 1);
                $price = (float) ($invoiceItem['price'] ?? 0);
                $itemDiscount = (float) ($invoiceItem['discount'] ?? 0);
                $itemVatRate = (float) ($invoiceItem['vat'] ?? 0); // This should be VAT percentage (e.g., 15 for 15%)

                // Calculate subtotal (price * quantity)
                $subtotal = $price * $qty;

                // Calculate discount amount
                $discountAmount = $itemDiscount * $qty;

                // Calculate amount after discount
                $amountAfterDiscount = $subtotal - $discountAmount;

                // Calculate VAT amount (VAT rate as percentage of amount after discount)
                $itemVatAmount = ($amountAfterDiscount * $itemVatRate) / 100;

                // Calculate final total (amount after discount + VAT)
                $itemTotal = $amountAfterDiscount + $itemVatAmount;

                // Update totals
                $total += $itemTotal;
                $discount += $discountAmount;
                $vat += $itemVatAmount;

                // Store the calculated values back to the item
                $invoiceItem['total'] = $itemTotal;
                $invoiceItem['calculated_vat_amount'] = $itemVatAmount; // Optional: store calculated VAT amount

                $collectItems[] = $invoiceItem;
            }

            $set('total', $total);
            $set('discount', $discount);
            $set('vat', $vat);
            $set('items', $collectItems);
        };
    }

    protected static function getShippingCalculator(): \Closure
    {
        return function (Forms\Get $get, Forms\Set $set) {
            $items = $get('items') ?? [];
            $total = 0;

            foreach ($items as $invoiceItem) {
                $qty = (float) ($invoiceItem['qty'] ?? 1);
                $price = (float) ($invoiceItem['price'] ?? 0);
                $itemDiscount = (float) ($invoiceItem['discount'] ?? 0);
                $itemVat = (float) ($invoiceItem['vat'] ?? 0);

                $total += (($price + $itemVat) - $itemDiscount) * $qty;
            }

            $shipping = (float) ($get('shipping') ?? 0);
            $set('total', $total + $shipping);
        };
    }

    protected static function getDueDateTooltip(): \Closure
    {
        return fn($record) => $record->due_date->isFuture()
            ? $record->due_date->diffForHumans()
            : ($record->due_date->isToday() ? 'Due Today!' : 'Over Due!');
    }

    protected static function getDueDateColor(): \Closure
    {
        return fn($record) => $record->due_date->isFuture()
            ? 'success'
            : ($record->due_date->isToday() ? 'warning' : 'danger');
    }

    protected static function getDueDateIcon(): \Closure
    {
        return fn($record) => $record->due_date->isFuture()
            ? 'heroicon-s-check-circle'
            : ($record->due_date->isToday() ? 'heroicon-s-exclamation-circle' : 'heroicon-s-x-circle');
    }

    protected static function isInvoiceHidden(): \Closure
    {
        return fn($record) => ($record->total === $record->paid)
            || $record->status === 'paid'
            || $record->status === 'estimate';
    }

    // ==================== COMPLEX FILTERS ====================

    protected static function getDueFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('due')
            ->form([
                Forms\Components\Toggle::make('overdue')
                    ->label(trans('messages.invoices.filters.due.columns.overdue')),
                Forms\Components\Toggle::make('today')
                    ->label(trans('messages.invoices.filters.due.columns.today')),
            ])
            ->label(trans('messages.invoices.filters.due.label'))
            ->query(function (Builder $query, array $data) {
                return $query
                    ->when($data['overdue'], function (Builder $query) {
                        $query->whereDate('due_date', '<', Carbon::now());
                    })
                    ->when($data['today'], function (Builder $query) {
                        $query->whereDate('due_date', Carbon::today());
                    });
            });
    }

    protected static function getForFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('for_id')
            ->form([
                Forms\Components\Select::make('for_type')
                    ->searchable()
                    ->live()
                    ->options(FilamentInvoices::getFor()->pluck('label', 'model')->toArray())
                    ->label(trans('messages.invoices.filters.for.columns.for_type')),

                Forms\Components\Select::make('for_id')
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        if (!$get('for_type')) {
                            return [];
                        }

                        $config = FilamentInvoices::getFor()->where('model', $get('for_type'))->first();
                        $column = $config?->column ?? 'name';

                        return $get('for_type')::query()->pluck($column, 'id')->toArray();
                    })
                    ->label(trans('messages.invoices.filters.for.columns.for_name')),
            ])
            ->label(trans('messages.invoices.filters.for.label'))
            ->query(function (Builder $query, array $data) {
                return $query
                    ->when($data['for_type'], fn(Builder $query) => $query->where('for_type', $data['for_type']))
                    ->when($data['for_id'], fn(Builder $query) => $query->where('for_id', $data['for_id']));
            });
    }

    protected static function getFromFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('from_id')
            ->form([
                Forms\Components\Select::make('from_type')
                    ->searchable()
                    ->live()
                    ->options(FilamentInvoices::getFrom()->pluck('label', 'model')->toArray())
                    ->label(trans('messages.invoices.filters.from.columns.from_type')),

                Forms\Components\Select::make('from_id')
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        if (!$get('from_type')) {
                            return [];
                        }

                        $config = FilamentInvoices::getFrom()->where('model', $get('from_type'))->first();
                        $column = $config?->column ?? 'name';

                        return $get('from_type')::query()->pluck($column, 'id')->toArray();
                    })
                    ->label(trans('messages.invoices.filters.from.columns.from_name')),
            ])
            ->label(trans('messages.invoices.filters.from.label'))
            ->query(function (Builder $query, array $data) {
                return $query
                    ->when($data['from_type'], fn(Builder $query) => $query->where('from_type', $data['from_type']))
                    ->when($data['from_id'], fn(Builder $query) => $query->where('from_id', $data['from_id']));
            });
    }

    // ==================== ACTIONS ====================

    protected static function getSendWhatsappAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('send_whatsapp')
            ->hidden(static::isInvoiceHidden())
            ->requiresConfirmation()
            ->iconButton()
            ->color('success')
            ->icon('heroicon-s-device-phone-mobile')
            ->label(trans('messages.invoices.actions.pay.label'))
            ->modalHeading(trans('messages.invoices.actions.pay.label'))
            ->tooltip(trans('messages.invoices.actions.pay.label'))
            ->action(function (Invoice $record) {
                $record->update(['token' => Helper::generateToken()]);

                event(new SendWhatsappEvent($record));

                $record->invoiceLogs()->create([
                    'log' => sprintf(
                        'Sent to whatsapp %s %s By: %s to number %s',
                        number_format($record->total, 2),
                        $record->currency->iso,
                        auth()->user()->name,
                        $record->phone
                    ),
                    'type' => 'payment',
                ]);

                Notification::make()
                    ->title(trans('messages.invoices.actions.pay.notification.title'))
                    ->body(trans('messages.invoices.actions.pay.notification.body'))
                    ->success()
                    ->send();
            });
    }

    protected static function getPayAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('pay')
            ->hidden(static::isInvoiceHidden())
            ->requiresConfirmation()
            ->iconButton()
            ->color('info')
            ->icon('heroicon-s-credit-card')
            ->label(trans('messages.invoices.actions.pay.label'))
            ->modalHeading(trans('messages.invoices.actions.pay.label'))
            ->tooltip(trans('messages.invoices.actions.pay.label'))
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
                $record->update(['paid' => $record->paid + $data['amount']]);

                $record->invoiceMetas()->create([
                    'key' => 'payments',
                    'value' => $data['amount']
                ]);

                $record->invoiceLogs()->create([
                    'log' => sprintf(
                        'Paid %s %s By: %s',
                        number_format($data['amount'], 2),
                        $record->currency->iso,
                        auth()->user()->name
                    ),
                    'type' => 'payment',
                ]);

                if ($record->total === $record->paid) {
                    $record->update(['status' => 'paid']);
                }

                Notification::make()
                    ->title(trans('messages.invoices.actions.pay.notification.title'))
                    ->body(trans('messages.invoices.actions.pay.notification.body'))
                    ->success()
                    ->send();
            });
    }

    protected static function getStatusBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('status')
            ->label(trans('messages.invoices.actions.status.label'))
            ->tooltip(trans('messages.invoices.actions.status.tooltip'))
            ->icon('heroicon-s-cursor-arrow-rays')
            ->deselectRecordsAfterCompletion()
            ->form([
                Forms\Components\Select::make('status')
                    ->searchable()
                    ->options(static::getInvoiceStatuses())
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
            });
    }
}
