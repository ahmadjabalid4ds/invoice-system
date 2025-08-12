<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cr_number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('entity_number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('bank_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_holder_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('iban')
                    ->maxLength(255),
                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship(
                        name: 'owner',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('is_admin', false)
                            ->whereNull('tenant_id')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cr_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_holder_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('iban')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->numeric()
                    ->sortable(),
                // Invoice count column
                Tables\Columns\TextColumn::make('invoices_count')
                    ->label('Total Invoices')
                    ->counts('invoices')
                    ->sortable(),
                // Latest invoice date
                Tables\Columns\TextColumn::make('latest_invoice_date')
                    ->label('Latest Invoice')
                    ->getStateUsing(function (Tenant $record) {
                        $latestInvoice = $record->invoices()->latest()->first();
                        return $latestInvoice ? $latestInvoice->created_at->format('M d, Y') : 'No invoices';
                    })
                    ->sortable(),
                // Total invoice amount (if your Invoice model has an 'amount' field)
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Amount')
                    ->getStateUsing(function (Tenant $record) {
                        return $record->invoices()->sum('total');
                    })
                    ->money('SAR') // Adjust currency as needed
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                // Add filter for tenants with/without invoices
                Tables\Filters\TernaryFilter::make('has_invoices')
                    ->label('Has Invoices')
                    ->queries(
                        true: fn (Builder $query) => $query->has('invoices'),
                        false: fn (Builder $query) => $query->doesntHave('invoices'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Add action to view invoices
                Tables\Actions\Action::make('view_invoices')
                    ->label('View Invoices')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Tenant $record) => route('filament.admin.resources.invoices.index', [
                        'tableFilters[tenant_id][value]' => $record->id
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
