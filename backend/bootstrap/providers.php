<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Core\IdentityAuth\IdentityAuthServiceProvider::class,
    App\Core\Tenancy\Providers\TenancyServiceProvider::class,
    App\Core\Projects\Providers\ProjectsServiceProvider::class,
    App\Core\DynamicForms\Providers\DynamicFormsServiceProvider::class,
];
