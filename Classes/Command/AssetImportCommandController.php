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
    /**
     * @Flow\Inject
     * @var AssetImportService
     */
    protected AssetImportService $assetImportService;

    /**
     * Import an asset from a given path.
     *
     * @param string $resource the path or the uri of the asset to import
     * @param string $assetType
     * @param string $title the title of the asset
     * @param string $caption the caption of the asset
     * @param string $copyrightNotice the copyright notice
     * @param string|null $filename override the filename
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function importCommand(
        string  $resource,
        string  $assetType,
        string $title = '',
        string $caption = '',
        string $copyrightNotice = '',
        ?string $filename = null,
    ): void {
        if (filter_var($resource, FILTER_VALIDATE_URL) && !$filename) {
            $this->outputLine('<error>You are importing a remote asset. Please define a filename</error>');
            $this->sendAndExit(1);
        } elseif (!filter_var($resource, FILTER_VALIDATE_URL) && !file_exists($resource)) {
            $this->outputLine('<error>The given path does not exist.</error>');
            $this->sendAndExit(1);
        }

        switch ($assetType) {
            case 'image':
                $this->assetImportService->importImage($resource, $title, $caption, $copyrightNotice, $filename);
                break;
            case 'video':
                $this->assetImportService->importVideo($resource, $title, $caption, $copyrightNotice, $filename);
                break;
            case 'audio':
                $this->assetImportService->importAudio($resource, $title, $caption, $copyrightNotice, $filename);
                break;
            case 'document':
                $this->assetImportService->importDocument($resource, $title, $caption, $copyrightNotice, $filename);
                break;
            default:
                $this->outputLine('<error>The given asset type is not supported.</error>');
                $this->sendAndExit(1);
        }

        $this->outputLine('<success>Asset imported.</success>');
    }
}
