<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Policies;

use App\Core\CondoFlow\Models\Building;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;

class BuildingPolicy
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly MembershipResolver $resolver,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function view(User $user, Building $building): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function update(User $user, Building $building): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function delete(User $user, Building $building): bool
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
