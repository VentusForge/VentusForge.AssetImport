<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Exceptions;

use Neos\Flow\Error\Exception;

class UnableToImportResource extends Exception
{
    public function __construct(string $resource)
    {
        parent::__construct(sprintf('The resource %s could not be imported.', $resource), 1769605921);
    }
}
