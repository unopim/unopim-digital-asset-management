<?php

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use Webkul\DAM\Exceptions\BlockedMediaHostException;
use Webkul\DAM\Exceptions\InvalidMediaUrlException;
use Webkul\DAM\Exceptions\MediaTooLargeException;
use Webkul\DAM\Exceptions\UnreachableMediaException;

class RemoteMediaDownloader
{
    protected const MAX_REDIRECTS = 3;

    protected const CONNECT_TIMEOUT = 5;

    protected const READ_TIMEOUT = 15;

    protected const METADATA_IP = '169.254.169.254';

    public function download(string $url, int $maxBytes): string
    {
        $redirects = 0;

        while (true) {
            $pinnedIps = $this->assertUrlAllowed($url);

            try {
                $response = Http::withOptions([
                    'allow_redirects' => false,
                    'stream'          => true,
                    'curl'            => $this->curlResolveOptions($url, $pinnedIps),
                ])
                    ->connectTimeout(self::CONNECT_TIMEOUT)
                    ->timeout(self::READ_TIMEOUT)
                    ->get($url);
            } catch (\Throwable $e) {
                throw new UnreachableMediaException('Media URL could not be downloaded: '.$e->getMessage());
            }

            if ($response->status() >= 300 && $response->status() < 400) {
                if (++$redirects > self::MAX_REDIRECTS) {
                    throw new UnreachableMediaException('Too many redirects for media URL.');
                }

                $location = $response->header('Location');

                if ($location === '' || $location === null) {
                    throw new UnreachableMediaException('Redirect without a location for media URL.');
                }

                $url = $this->resolveLocation($url, $location);

                continue;
            }

            if (! $response->successful()) {
                throw new UnreachableMediaException('Media URL returned status '.$response->status().'.');
            }

            if ((int) $response->header('Content-Length') > $maxBytes) {
                throw new MediaTooLargeException('Media file exceeds the maximum allowed size.');
            }

            return $this->streamToTempFile($response->toPsrResponse()->getBody(), $maxBytes);
        }
    }

    protected function streamToTempFile(StreamInterface $body, int $maxBytes): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'dam-url-');
        $handle = fopen($temp, 'wb');
        $written = 0;

        try {
            while (! $body->eof()) {
                $chunk = $body->read(8192);

                if ($chunk === '') {
                    break;
                }

                $written += strlen($chunk);

                if ($written > $maxBytes) {
                    throw new MediaTooLargeException('Media file exceeds the maximum allowed size.');
                }

                fwrite($handle, $chunk);
            }
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($temp);

            throw $e;
        }

        fclose($handle);

        return $temp;
    }

    /**
     * @param  array<int, string>  $pinnedIps
     * @return array<int, mixed>
     */
    protected function curlResolveOptions(string $url, array $pinnedIps): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || filter_var($host, FILTER_VALIDATE_IP) || $pinnedIps === []) {
            return [];
        }

        $port = parse_url($url, PHP_URL_PORT) ?: (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);
        $ip = $pinnedIps[0];

        return [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]];
    }

    /**
     * @return array<int, string>
     */
    protected function assertUrlAllowed(string $url): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidMediaUrlException('Only http and https media URLs are allowed.');
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            throw new InvalidMediaUrlException('Media URL has no host.');
        }

        $ips = $this->resolveIps($host);

        foreach ($ips as $ip) {
            if ($ip === self::METADATA_IP) {
                throw new BlockedMediaHostException('Media URL resolves to a blocked host.');
            }

            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new BlockedMediaHostException('Media URL resolves to a private or reserved host.');
            }
        }

        return $ips;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            throw new UnreachableMediaException('Media URL host could not be resolved: '.$host);
        }

        return $ips;
    }

    protected function resolveLocation(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME)) {
            return $location;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME);
        $host = parse_url($base, PHP_URL_HOST);

        return sprintf('%s://%s/%s', $scheme, $host, ltrim($location, '/'));
    }
}
