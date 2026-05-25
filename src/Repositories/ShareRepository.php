<?php

namespace Webkul\DAM\Repositories;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Share;

class ShareRepository extends Repository
{
    public function model(): string
    {
        return Share::class;
    }

    public function createForAsset(int $assetId, ?DateTimeInterface $expiresAt, ?int $userId, ?string $name = null): Share
    {
        return $this->create([
            'token'      => $this->generateUniqueToken(),
            'name'       => $name,
            'share_type' => Share::TYPE_ASSET,
            'target_id'  => $assetId,
            'created_by' => $userId,
            'expires_at' => $expiresAt ? Carbon::instance($expiresAt) : null,
        ]);
    }

    public function createForDirectory(int $directoryId, ?DateTimeInterface $expiresAt, ?int $userId, ?string $name = null): Share
    {
        return $this->create([
            'token'      => $this->generateUniqueToken(),
            'name'       => $name,
            'share_type' => Share::TYPE_DIRECTORY,
            'target_id'  => $directoryId,
            'created_by' => $userId,
            'expires_at' => $expiresAt ? Carbon::instance($expiresAt) : null,
        ]);
    }

    public function findActiveByToken(string $token): ?Share
    {
        $share = $this->model->newQuery()->where('token', $token)->first();

        if (! $share || ! $share->isActive()) {
            return null;
        }

        return $share;
    }

    public function findByToken(string $token): ?Share
    {
        return $this->model->newQuery()->where('token', $token)->first();
    }

    public function revoke(int $id): bool
    {
        $share = $this->find($id);

        if (! $share || $share->isRevoked()) {
            return false;
        }

        $share->revoked_at = now();
        $share->save();

        return true;
    }

    public function reauthorize(int $id): bool
    {
        $share = $this->find($id);

        if (! $share || ! $share->isRevoked()) {
            return false;
        }

        $share->revoked_at = null;
        $share->save();

        return true;
    }

    public function incrementView(Share $share): void
    {
        $this->model->newQuery()
            ->where('id', $share->id)
            ->increment('view_count', 1, ['last_accessed_at' => now()]);
    }

    public function incrementDownload(Share $share): void
    {
        $this->model->newQuery()
            ->where('id', $share->id)
            ->increment('download_count', 1, ['last_accessed_at' => now()]);
    }

    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while ($this->model->newQuery()->where('token', $token)->exists());

        return $token;
    }
}
