<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Service;

use Neos\Flow\Annotations as Flow;
use VentusForge\AssetImport\Exceptions\UndetectedAssetTypeException;

/**
 * The File Service
 */

#[Flow\Scope("singleton")]
class FileService
{

    #[Flow\InjectConfiguration(path: "mimeTypes", package: "VentusForge.AssetImport")]
    protected array $mimeTypes;

    /**
     * Get the mime type of a resource
     *
     * @param string $resource The path of the resource
     * @return string The mime type of the resource
     */
    public function getMimeType(string $resource): string
    {
        return mime_content_type($resource);
    }

    /**
     * Get the asset type from a mime type
     * 
     * @param string $mimeType The mime type of the resource
     * @throws UndetectedAssetTypeException If the mime type does not match to the asset types
     * @return string The asset type of the resource
     */
    public function getAssetType(string $mimeType): string
    {
        if (array_key_exists($mimeType, $this->mimeTypes)) {
            return $this->mimeTypes[$mimeType];
        }

        $prefix = substr($mimeType, 0, strpos($mimeType, '/')) . '/*';
        if (array_key_exists($prefix, $this->mimeTypes)) {
            return $this->mimeTypes[$prefix];
        }

        throw new UndetectedAssetTypeException($mimeType);
    }
}
