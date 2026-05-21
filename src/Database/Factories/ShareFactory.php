<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\DAM\Models\Share;

class ShareFactory extends Factory
{
    protected $model = Share::class;

    public function definition(): array
    {
        return [
            'token'      => Str::random(40),
            'share_type' => Share::TYPE_ASSET,
            'target_id'  => 0,
            'created_by' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function forAsset(int $assetId): self
    {
        return $this->state(fn () => [
            'share_type' => Share::TYPE_ASSET,
            'target_id'  => $assetId,
        ]);
    }

    public function forDirectory(int $directoryId): self
    {
        return $this->state(fn () => [
            'share_type' => Share::TYPE_DIRECTORY,
            'target_id'  => $directoryId,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function revoked(): self
    {
        return $this->state(fn () => [
            'revoked_at' => now()->subMinute(),
            'is_active'  => false,
        ]);
    }

    public function noExpiry(): self
    {
        return $this->state(fn () => [
            'expires_at' => null,
        ]);
    }
}
