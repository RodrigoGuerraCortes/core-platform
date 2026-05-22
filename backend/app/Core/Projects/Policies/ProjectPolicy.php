<?php

declare(strict_types=1);

namespace App\Core\Projects\Policies;

use App\Core\Projects\Models\Project;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;

/**
 * Authorization policy for Project resources.
 *
 * Reads membership_role via MembershipResolver (request-scoped cache) so repeated
 * policy checks within a single request do not generate additional DB queries.
 * Does NOT bypass TenantScope or assume platform admin privileges.
 *
 * Role matrix:
 *   owner / admin → viewAny, view, create, update, delete
 *   member        → viewAny, view
 *   non-member    → (blocked by tenant.member middleware before policy is reached)
 *
 * Platform admin status does NOT grant write access. A platform admin
 * who is only a 'member' of the tenant cannot create/update/delete projects.
 */
class ProjectPolicy
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly MembershipResolver $resolver,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isOwnerOrAdmin($user);
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
