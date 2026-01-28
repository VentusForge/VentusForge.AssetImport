<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;

/**
 * The Remote File Service
 */

#[Flow\Scope("singleton")]
class RemoteFileService
{
    #[Flow\Inject]
    protected Browser $browser;

    #[Flow\Inject]
    protected CurlEngine $browserRequestEngine;

    /**
     * Get the file info from a remote resource
     * 
     * @param string $resource
     * @throws \Exception
     * @return array{filename: string, mimeType: string}
     */
    public function getFileInfo(string $resource): array
    {
        $this->browser->setRequestEngine($this->browserRequestEngine);
        $response = $this->browser->request($resource);

        $mimeType = $response->getHeader('content-type')[0];
        $filename = $response->getHeader('content-disposition')[0] ?? null;

        if (!$filename) {
            // get the path from the url
            $path = parse_url($resource, PHP_URL_PATH);
            $filename = basename($path);
            if (!$filename) {
                throw new \Exception('Could not get filename from url');
            }
        }

        return [
            'mimeType' => $mimeType,
            'filename' => $filename,
        ];
    }
}
