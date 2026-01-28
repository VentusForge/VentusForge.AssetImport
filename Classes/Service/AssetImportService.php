<?php

declare(strict_types=1);

namespace VentusForge\AssetImport\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Model\Video;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Exception\ThumbnailServiceException;
use VentusForge\AssetImport\Exceptions\UnableToImportResource;
use VentusForge\AssetImport\Exceptions\UndetectedAssetTypeException;

/**
 * The Asset Import Service
 */

#[Flow\Scope("singleton")]
class AssetImportService
{
    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    #[Flow\Inject]
    protected ThumbnailService $thumbnailService;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected FileService $fileService;

    #[Flow\Inject]
    protected RemoteFileService $remoteFileService;

    #[Flow\InjectConfiguration(path: "asyncThumbnails", package: "Neos.Media")]
    protected bool $asyncThumbnails;

    /**
     * Import a resource
     *
     * @param string $path The path of the resource
     * @param string|null $title The title of the resource
     * @param string|null $caption The caption of the resource
     * @param string|null $copyrightNotice The copyright notice of the resource
     * @param string|null $filename The filename of the resource
     * @throws UnableToImportResource If the resource could not be imported
     * @throws UndetectedAssetTypeException If the asset type of the resource could not be detected
     * @return void
     */
    public function import(
        string $path,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
    ): void
    {
        $isRemote = filter_var($path, FILTER_VALIDATE_URL);
        if ($isRemote) {
            $fileInfo = $this->remoteFileService->getFileInfo($path);
            $mimeType = $fileInfo['mimeType'];
            $filename = $fileInfo['filename'];
        } else {
            $mimeType = $this->fileService->getMimeType($path);
        }

        $assetType = $this->fileService->getAssetType($mimeType);
        switch ($assetType) {
            case 'image':
                $this->importImage($path, $title, $caption, $copyrightNotice, $filename);
                return;
            case 'video':
                $this->importVideo($path, $title, $caption, $copyrightNotice, $filename);
                return;
            case 'audio':
                $this->importAudio($path, $title, $caption, $copyrightNotice, $filename);
                return;
            case 'document':
                $this->importDocument($path, $title, $caption, $copyrightNotice, $filename);
                return;
        }

        throw new UnableToImportResource($path);
    }

    /**
     * Import an image file
     *
     * @param string $resource
     * @param string|null $title
     * @param string|null $caption
     * @param string|null $copyrightNotice
     * @param string|null $filename
     * @return void
     * @throws ThumbnailServiceException
     * @throws IllegalObjectTypeException
     * @throws Exception
     */
    public function importImage(
        string  $resource,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
    ): void
    {
        $imageResource = $this->resourceManager->importResource($resource);
        if ($filename) {
            $imageResource->setFilename($filename);
        }

        $image = new Image($imageResource);
        $image->setTitle($title ?? '');
        $image->setCaption($caption ?? '');
        $image->setCopyrightNotice($copyrightNotice ?? '');

        $imageVariant = new ImageVariant($image);

        $this->assetRepository->add($image);
        $this->assetRepository->add($imageVariant);
        $this->generateThumbnails($image);
    }

    /**
     * Import a video file
     *
     * @param string $resource
     * @param string|null $title
     * @param string|null $caption
     * @param string|null $copyrightNotice
     * @param string|null $filename
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function importVideo(
        string  $resource,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
    ): void
    {
        $videoResource = $this->resourceManager->importResource($resource);
        if ($filename) {
            $videoResource->setFilename($filename);
        }

        $video = new Video($videoResource);
        $video->setTitle($title ?? '');
        $video->setCaption($caption ?? '');
        $video->setCopyrightNotice($copyrightNotice ?? '');

        $this->assetRepository->add($video);
        $this->generateThumbnails($video);
    }

    /**
     * Import an audio file
     *
     * @param string $resource
     * @param string|null $title
     * @param string|null $caption
     * @param string|null $copyrightNotice
     * @param string|null $filename
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function importAudio(
        string  $resource,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
    ): void
    {
        $audioResource = $this->resourceManager->importResource($resource);
        if ($filename) {
            $audioResource->setFilename($filename);
        }

        $audio = new Audio($audioResource);
        $audio->setTitle($title ?? '');
        $audio->setCaption($caption ?? '');
        $audio->setCopyrightNotice($copyrightNotice ?? '');

        $this->assetRepository->add($audio);
        $this->generateThumbnails($audio);
    }

    /**
     * Import a document file
     *
     * @param string $resource
     * @param string|null $title
     * @param string|null $caption
     * @param string|null $copyrightNotice
     * @param string|null $filename
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function importDocument(
        string  $resource,
        ?string $title = null,
        ?string $caption = null,
        ?string $copyrightNotice = null,
        ?string $filename = null,
    ): void
    {
        $documentResource = $this->resourceManager->importResource($resource);
        if ($filename) {
            $documentResource->setFilename($filename);
        }

        $document = new Document($documentResource);
        $document->setTitle($title ?? '');
        $document->setCaption($caption ?? '');
        $document->setCopyrightNotice($copyrightNotice ?? '');

        $this->assetRepository->add($document);
        $this->generateThumbnails($document);
    }

    /**
     * Generate thumbnails for the given asset.
     *
     * @param Asset $asset
     * @return void
     * @throws ThumbnailServiceException
     */
    protected function generateThumbnails(Asset $asset): void
    {
        $async = $async ?? $this->asyncThumbnails;
        $presets = array_keys($this->thumbnailService->getPresets());

        $presetThumbnailConfigurations = [];
        foreach ($presets as $presetName) {
            $presetThumbnailConfigurations[] = $this->thumbnailService->getThumbnailConfigurationForPreset($presetName, $async);
        }

        /** @var ThumbnailConfiguration $presetThumbnailConfiguration */
        foreach ($presetThumbnailConfigurations as $presetThumbnailConfiguration) {
            $this->thumbnailService->getThumbnail($asset, $presetThumbnailConfiguration);
            $this->persistenceManager->persistAll();
        }
    }
}
