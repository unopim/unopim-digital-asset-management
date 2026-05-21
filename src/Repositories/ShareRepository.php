<?php

namespace Webkul\DAM\Repositories;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Exceptions\ShareAlreadyActiveException;
use Webkul\DAM\Exceptions\ShareNeedsEnableException;
use Webkul\DAM\Exceptions\ShareNotEnableableException;
use Webkul\DAM\Models\Share;

class ShareRepository extends Repository
{
    public function model(): string
    {
        return Share::class;
    }

    public function createForAsset(int $assetId, ?DateTimeInterface $expiresAt, ?int $userId): Share
    {
        return $this->createForTarget(Share::TYPE_ASSET, $assetId, $expiresAt, $userId);
    }

    public function createForDirectory(int $directoryId, ?DateTimeInterface $expiresAt, ?int $userId): Share
    {
        return $this->createForTarget(Share::TYPE_DIRECTORY, $directoryId, $expiresAt, $userId);
    }

    public function createForTarget(string $type, int $targetId, ?DateTimeInterface $expiresAt, ?int $userId): Share
    {
        return DB::transaction(function () use ($type, $targetId, $expiresAt, $userId) {
            $existing = $this->model->newQuery()
                ->where('share_type', $type)
                ->where('target_id', $targetId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isActive()) {
                    throw new ShareAlreadyActiveException;
                }

                // Revoked but not yet expired → caller should hit /enable
                // to refresh the same token. Expired (with or without revoke)
                // falls through to a fresh row + new token (Renew).
                if (! $existing->isExpired()) {
                    throw new ShareNeedsEnableException;
                }

                $existing->delete();
            }

            return $this->create([
                'token'      => $this->generateUniqueToken(),
                'share_type' => $type,
                'target_id'  => $targetId,
                'created_by' => $userId,
                'expires_at' => $expiresAt ? Carbon::instance($expiresAt) : null,
                'is_active'  => true,
            ]);
        });
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

    public function findForTarget(string $type, int $targetId): ?Share
    {
        return $this->model->newQuery()
            ->where('share_type', $type)
            ->where('target_id', $targetId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function revoke(int $id): bool
    {
        $share = $this->find($id);

        if (! $share || ! $share->is_active) {
            return false;
        }

        $share->is_active = false;
        $share->revoked_at = now();
        $share->save();

        return true;
    }

    public function enable(int $id, ?DateTimeInterface $expiresAt = null): Share
    {
        $share = $this->find($id);

        if (! $share) {
            throw new ShareNotEnableableException('not-found');
        }

        if ($share->isActive()) {
            return $share;
        }

        $share->is_active = true;
        $share->expires_at = $expiresAt ? Carbon::instance($expiresAt) : null;
        $share->save();

        return $share->fresh();
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
