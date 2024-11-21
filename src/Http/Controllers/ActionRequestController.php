<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Webkul\DAM\Models\ActionRequest;

class ActionRequestController
{
    /**
     * Fetches the status of a specific action request based on the event type.
     */
    public function fetchStatus(string $eventType)
    {
        try {
            $request = ActionRequest::findOneWhere([
                'event_type'  => $eventType,
                'admin_id'    => Auth::id(),
            ]);

            return new JsonResponse([
                'status'  => $request?->status,
                'message' => $request?->error_message,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
