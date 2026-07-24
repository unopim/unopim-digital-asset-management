<?php

namespace Webkul\DAM\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Webkul\DAM\Models\ActionRequest as ActionRequestModel;
use Webkul\User\Models\Admin;

trait ActionRequest
{
    protected $actionRequest;

    protected $user;

    public function getActionRequest(): self
    {
        return $this->actionRequest;
    }

    public function getUser()
    {
        return $this->user ?? $this->user = Admin::find(Auth::id());
    }

    public function start(string $eventType, array $options = []): self
    {
        $this->user = Admin::find(Auth::id());

        $whereCondition = [
            'event_type' => $eventType,
            'admin_id'   => Auth::id(),
        ];

        if (! empty($options)) {
            $whereCondition = array_merge($whereCondition, $options);
        }

        $request = ActionRequestModel::firstOrNew($whereCondition);

        $request->fill([
            'status'   => 'pending',
            'admin_id' => Auth::id(),
        ])->save();

        $this->actionRequest = $request;

        return $this;
    }

    public function completed(string $eventType, int $userId, array $options = []): self
    {
        $whereCondition = [
            'event_type' => $eventType,
            'status'     => 'pending',
            'admin_id'   => $userId,
        ];

        if (! empty($options)) {
            $whereCondition = array_merge($whereCondition, $options);
        }

        $request = ActionRequestModel::findOneWhere($whereCondition);

        if ($request) {
            $request->update(['status' => 'completed']);
        }

        $this->actionRequest = $request;

        return $this;
    }

    public function markFailed(string $eventType, int $userId, ?string $error = null, array $options = []): self
    {
        $whereCondition = [
            'event_type' => $eventType,
            'status'     => 'pending',
            'admin_id'   => $userId,
        ];

        if (! empty($options)) {
            $whereCondition = array_merge($whereCondition, $options);
        }

        $request = ActionRequestModel::findOneWhere($whereCondition);

        if ($request) {
            $request->update([
                'status'        => 'failed',
                'error_message' => $error,
            ]);
        }

        $this->actionRequest = $request;

        return $this;
    }

    public function updateProgress(string $eventType, int $userId, int $percent): void
    {
        $percent = max(0, min(100, $percent));

        Cache::put("dam.progress.{$eventType}.{$userId}", $percent, now()->addHours(2));
    }

    public function clearProgress(string $eventType, int $userId): void
    {
        Cache::forget("dam.progress.{$eventType}.{$userId}");
    }

    public function checkedUser($userId)
    {
        $this->user = Admin::find($userId);

        return $this->user;
    }
}
