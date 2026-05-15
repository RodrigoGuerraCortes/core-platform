<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/** @mixin NewAccessToken */
class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->plainTextToken,
            'token_type' => 'Bearer',
            'token_name' => $this->accessToken->name,
            'created_at' => $this->accessToken->created_at?->toISOString(),
        ];
    }
}
