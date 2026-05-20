<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Tenant not found.');
    }
}
