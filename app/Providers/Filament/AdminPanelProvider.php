<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Orion\FilamentGreeter\GreeterPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()->font('IBM Plex Sans Arabic')
            ->profile()
            ->colors([
                'primary' => '#a223fd',
            ])->plugins([
                // \TomatoPHP\FilamentInvoices\FilamentInvoicesPlugin::make()->register(
                //     $panel->resources(
                //         [
                //             InvoiceResource::class
                //         ]
                //     )
                // ),
                \Filament\SpatieLaravelTranslatablePlugin::make()->defaultLocales(['en', 'ar']),
                \TomatoPHP\FilamentUsers\FilamentUsersPlugin::make(),
                \TomatoPHP\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \TomatoPHP\FilamentLocations\FilamentLocationsPlugin::make(),
                // ->countries(false)
                // ->languages(false)
                // ->currency(false)
                // ->locations(false),
                // \TomatoPHP\FilamentSettingsHub\FilamentSettingsHubPlugin::make()
                // ->allowSiteSettings()
                // ->allowSocialMenuSettings(),

            GreeterPlugin::make()
                ->message(__('Welcome'))

                ->title(__('desc'))
                ->avatar(size: 'w-16 h-16', url: asset('images/icon.png'))
                ->action(
                    Action::make('action')
                        ->label(__('Know More'))

                        ->url('https://d4ds.net')
                )
                ->sort(-1)
                ->columnSpan('full'),

                ])
                ->brandLogo(asset('images/favicon.png')) ->brandLogoHeight('2rem')->favicon(asset('images/favico.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
