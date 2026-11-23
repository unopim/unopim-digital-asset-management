<?php

namespace Webkul\DAM\Services;

use Webkul\DAM\Traits\ResolvesAssetPathsInValues;
use Webkul\Product\Contracts\Product;
use Webkul\Webhook\Services\WebhookService as BaseWebhookService;

class WebhookService extends BaseWebhookService
{
    use ResolvesAssetPathsInValues;

    /**
     * Rewrite asset ids in the outgoing webhook `changes` diff to their paths.
     */
    protected function normalizeWebhookData(Product $product, array $productChanges = []): array
    {
        $data = parent::normalizeWebhookData($product, $productChanges);

        if (! empty($data['changes']) && is_array($data['changes'])) {
            $this->resolveAssetPathsInChanges($data['changes']);
        }

        return $data;
    }
}
