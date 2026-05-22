<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Policies;

use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormVersion;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;

/**
 * Authorization policy for FormVersion resources.
 *
 * Role matrix:
 *   owner / admin → viewAny, view, create
 *   member        → viewAny, view
 *
 * Versions are immutable — no update or delete for any role.
 */
class FormVersionPolicy
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly MembershipResolver $resolver,
    ) {}

    public function viewAny(User $user, Form $form): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function view(User $user, FormVersion $version): bool
    {
        if ($this->membershipRole($user) === null) {
            return false;
        }

        // FormVersion has no BelongsToTenant, so tenant isolation must be explicit.
        // tenant_id on the version always matches the parent form's tenant.
        return $version->tenant_id === $this->context->tenantId();
    }

    public function create(User $user, Form $form): bool
    {
        return $this->isOwnerOrAdmin($user) && ! $form->isArchived();
    }

    public function delete(User $user, FormVersion $version): bool
    {
        return false;
    }

    private function membershipRole(User $user): ?string
    {
        if (! $this->context->isResolved()) {
            return null;
        }

        return $this->resolver->roleFor($user, $this->context->tenantId());
    }

    private function isOwnerOrAdmin(User $user): bool
    {
        return in_array($this->membershipRole($user), ['owner', 'admin'], strict: true);
    }
}
