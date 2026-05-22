<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Providers;

use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormSubmission;
use App\Core\DynamicForms\Models\FormVersion;
use App\Core\DynamicForms\Policies\FormPolicy;
use App\Core\DynamicForms\Policies\FormSubmissionPolicy;
use App\Core\DynamicForms\Policies\FormVersionPolicy;
use App\Core\Shared\Providers\CoreModuleServiceProvider;

final class DynamicFormsServiceProvider extends CoreModuleServiceProvider
{
    protected array $policies = [
        Form::class           => FormPolicy::class,
        FormVersion::class    => FormVersionPolicy::class,
        FormSubmission::class => FormSubmissionPolicy::class,
    ];

    protected function routesPath(): ?string
    {
        return __DIR__ . '/../Routes/api.php';
    }
}
