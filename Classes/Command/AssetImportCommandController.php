<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Media\Exception\ThumbnailServiceException;
use VentusForge\AssetImport\Service\AssetImportService;

/**
 * Import resources as assets
 */
class AssetImportCommandController extends CommandController
{
    #[Flow\Inject]
    protected AssetImportService $assetImportService;

    /**
     * Import an asset from a given path or uri.
     *
     * @param string $resource The path or the uri of the asset to import
     * @param string|null $title the title of the asset
     * @param string|null $caption the caption of the asset
     * @param string|null $copyrightNotice the copyright notice
     * @param string|null $filename override the filename
     * @param bool $dryRun If true, the file will not be imported, but the command will output what would have been imported
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function fileCommand(
        string $resource,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
        bool $dryRun = false,
    ): void {
        $isRemote = filter_var($resource, FILTER_VALIDATE_URL);

        if (!$isRemote && !file_exists($resource)) {
            $this->outputLine('<error>The given path does not exist.</error>');
            $this->sendAndExit(1);
        }

        if (!$dryRun) {
            $this->assetImportService->import($resource, $title, $caption, $copyrightNotice, $filename);
            $this->outputLine('<success>Successfully imported file: %s</success>', [$resource]);
        } else {
            $this->outputLine('<success>Dry run: Would have imported file: %s</success>', [$resource]);
        }
    }
}
