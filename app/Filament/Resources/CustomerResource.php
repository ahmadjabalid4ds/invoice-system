<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 51;
    public static function getNavigationLabel(): string
    {
        return trans('customers');
    }

    public static function getPluralLabel(): ?string
    {
        return trans('customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('Settings');
    }

    public static function getLabel(): ?string
    {
        return trans('customer');
    }

    public static function singularLabel(): ?string
    {
        return trans('customer');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('Name')),
                    PhoneInput::make('phone')

                    ->required()
                    ->label(__('Phone')) ->onlyCountries(['sa','us', 'jo'])->defaultCountry('sa'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->label(__('Email')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('Name')),
                    PhoneColumn::make('phone')->displayFormat(PhoneInputNumberType::NATIONAL)
                    ->label(__('phone'))->searchable(true),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Created At')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Updated At')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
