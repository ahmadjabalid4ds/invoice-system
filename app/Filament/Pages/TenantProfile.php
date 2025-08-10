<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class TenantProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static string $view = 'filament.pages.tenant-profile';
    protected static ?string $title = 'My Organization';
    protected static ?string $navigationLabel = 'My Organization';

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $tenant = auth()->user()->tenant;

        if ($tenant) {
            $this->form->fill([
                'name' => $tenant->name,
                'cr_number' => $tenant->cr_number,
                'entity_number' => $tenant->entity_number,
                'bank_name' => $tenant->bank_name,
                'bank_holder_name' => $tenant->bank_holder_name,
                'iban' => $tenant->iban,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Organization Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cr_number')
                            ->label('CR Number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('entity_number')
                            ->label('Entity Number')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_holder_name')
                            ->label('Bank Account Holder Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = auth()->user()->tenant;

        if ($tenant) {
            $tenant->update($data);

            Notification::make()
                ->title('Organization updated successfully!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No organization found!')
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return (auth()->user()?->tenant !== null && auth()->user()->isTenantAdmin());
    }
}
