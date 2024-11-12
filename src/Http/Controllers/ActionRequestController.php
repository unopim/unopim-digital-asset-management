<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Webkul\DAM\Models\ActionRequest;

class ActionRequestController
{
    public function fetchStatus($eventType)
    {
        try {
            $request = ActionRequest::findOneWhere([
                'event_type'  => $eventType,
                'admin_id'    => Auth::id(),
            ]);

            return new JsonResponse([
                'status'  => $request?->status,
                'message' => $request?->erorr_message,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
