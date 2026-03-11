<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Exceptions;

use Neos\Flow\Error\Exception;

class UndetectedAssetTypeException extends Exception
{
    public function __construct(string $resource, string $mimeType)
    {
        parent::__construct(sprintf('The mime type "%s" of the resource "%s" does not match to the asset types.', $mimeType, $resource), 1769605790);
    }
}
