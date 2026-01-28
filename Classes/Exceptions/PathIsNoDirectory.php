<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Exceptions;

use Neos\Flow\Error\Exception;

class PathIsNoDirectory extends Exception
{
    public function __construct(string $resource)
    {
        parent::__construct(sprintf('The path %s is not a directory.', $resource), 1769609727);
    }
}
