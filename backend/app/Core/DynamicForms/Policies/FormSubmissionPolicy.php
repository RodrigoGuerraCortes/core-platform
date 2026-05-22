<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Policies;

use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormSubmission;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;

/**
 * Authorization policy for FormSubmission resources.
 *
 * Role matrix:
 *   owner / admin → viewAny (all submissions), view (any), create
 *   member        → view (own only), create
 *
 * No delete is permitted for any role.
 */
class FormSubmissionPolicy
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly MembershipResolver $resolver,
    ) {}

    /**
     * Whether the user can list ALL submissions for a form.
     * Members cannot list all submissions — they may only view their own.
     */
    public function viewAny(User $user, Form $form): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Whether the user can view a specific submission.
     * Members may only view their own submissions.
     */
    public function view(User $user, FormSubmission $submission): bool
    {
        if ($this->isOwnerOrAdmin($user)) {
            return true;
        }

        // Member: only their own submission
        return $submission->submitted_by === $user->id;
    }

    public function create(User $user, Form $form): bool
    {
        return $this->membershipRole($user) !== null;
    }

    public function delete(User $user, FormSubmission $submission): bool
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
