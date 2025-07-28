<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;
    protected static ?int $navigationSort = 52;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    public static function getNavigationLabel(): string
    {
        return __('accounts');
    }

    public static function getPluralLabel(): ?string
    {
        return __('accounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getLabel(): ?string
    {
        return __('account');
    }
public static function singularLabel(): ?string{
   return __('account');

}
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label(__('name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('iban')->label('IBAN')
                    ->required()->length(24),

                Forms\Components\TextInput::make('balance')->label(__('balanc'))
                    ->required()
                    ->maxLength(255)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('iban')->label("IBAN")
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')->label(__('balance'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListAccounts::route('/'),

            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
