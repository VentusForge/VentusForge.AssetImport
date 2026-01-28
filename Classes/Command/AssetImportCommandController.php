<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Media\Exception\ThumbnailServiceException;
use VentusForge\AssetImport\Exceptions\PathIsNoDirectory;
use VentusForge\AssetImport\Service\AssetImportService;
use VentusForge\AssetImport\Service\FileService;

/**
 * Import resources as assets
 */
class AssetImportCommandController extends CommandController
{
    #[Flow\Inject]
    protected AssetImportService $assetImportService;

    #[Flow\Inject]
    protected FileService $fileService;

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

    /**
     * Import all files in a directory.
     * 
     * @param string $directory The directory to import the files from
     * @param string|null $extension The extension of the files to import (e.g. "jpg", "png", "mp4", "mp3", "pdf")
     * @param bool $dryRun If true, the files will not be imported, but the command will output what would have been imported
     * @param bool $interactive If true, the command will ask for confirmation before importing each file
     * @return void
     */
    public function directoryCommand(
        string $directory,
        ?string $extension = null,
        bool $dryRun = false,
        bool $interactive = false,
    ): void {
        try {
            $files = $this->fileService->getFilesInDirectory($directory, $extension);
        } catch (PathIsNoDirectory $e) {
            $this->outputLine('<error>%s</error>', [$e->getMessage()]);
            $this->sendAndExit(1);
        }

        if (empty($files)) {
            $this->outputLine('<error>No files found in directory: %s</error>', [$directory]);
            $this->sendAndExit(1);
        }

        $this->outputLine('<info>Found %s files in directory: %s</info>', [count($files), $directory]);

        foreach ($files as $file) {
            $import = $interactive ? $this->output->askConfirmation('<info>Confirm import of file: ' . $file . '? (y/n)</info> ', false) : true;
            if (!$import) {
                $this->outputLine('<success>Skipping file: %s</success>', [$file]);
                continue;
            }

            $this->fileCommand(
                resource: $file,
                dryRun: $dryRun
            );
        }
    }
}
