<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Policies;

use App\Core\DynamicForms\Models\Form;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;

/**
 * Authorization policy for Form resources.
 *
 * Role matrix:
 *   owner / admin → viewAny, view, create, update, publish, archive
 *   member        → viewAny, view
 *   non-member    → blocked by tenant.member middleware before policy fires
 *
 * Platform admin status does NOT grant write access.
 * No hard delete is permitted for any role.
 */
class FormPolicy
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly MembershipResolver $resolver,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function view(User $user, Form $form): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function update(User $user, Form $form): bool
    {
        return $this->isOwnerOrAdmin($user) && ! $form->isArchived();
    }

    public function publish(User $user, Form $form): bool
    {
        return $this->isOwnerOrAdmin($user) && ! $form->isArchived();
    }

    public function archive(User $user, Form $form): bool
    {
        return $this->isOwnerOrAdmin($user) && ! $form->isArchived();
    }

    public function delete(User $user, Form $form): bool
    {
        // Hard delete is never permitted via API. Archive instead.
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
