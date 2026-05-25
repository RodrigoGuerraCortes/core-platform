<?php

return [
    App\Core\CondoFlow\Providers\CondoFlowServiceProvider::class,
    App\Core\DynamicForms\Providers\DynamicFormsServiceProvider::class,
    App\Core\IdentityAuth\IdentityAuthServiceProvider::class,
    App\Core\Projects\Providers\ProjectsServiceProvider::class,
    App\Core\Tenancy\Providers\TenancyServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
