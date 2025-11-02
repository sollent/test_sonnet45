<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MediaObject;
use App\Entity\User;
use App\Repository\Database\MediaObjectRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class MediaObjectService
{
    private const UPLOAD_DIR = 'public/uploads/media';
    private const WEB_PATH = '/uploads/media';
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt'];

    public function __construct(
        private readonly MediaObjectRepository $repository,
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * Upload file and create MediaObject
     */
    public function uploadFile(UploadedFile $file, User $user): MediaObject
    {
        // Validate file
        $this->validateFile($file);

        // Get file info BEFORE moving
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType() ?? 'application/octet-stream';
        $fileSize = $file->getSize();
        $extension = $file->guessExtension() ?? pathinfo($originalName, PATHINFO_EXTENSION);

        // Generate unique filename
        $originalFilename = pathinfo($originalName, PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Move file
        $projectDir = dirname(__DIR__, 2);
        $uploadDirectory = $projectDir . '/' . self::UPLOAD_DIR;
        
        // Ensure directory exists with proper permissions
        if (!is_dir($uploadDirectory)) {
            @mkdir($uploadDirectory, 0777, true);
            @chmod($uploadDirectory, 0777);
        }

        $file->move($uploadDirectory, $newFilename);

        // Create MediaObject entity
        $mediaObject = new MediaObject();
        $mediaObject->setUploadedBy($user);
        $mediaObject->setFileName($newFilename);
        $mediaObject->setOriginalName($originalName);
        $mediaObject->setMimeType($mimeType);
        $mediaObject->setFileSize($fileSize);
        $mediaObject->setFilePath(self::WEB_PATH . '/' . $newFilename);
        $mediaObject->setFileType($mediaObject->determineFileType());

        $this->repository->save($mediaObject);

        return $mediaObject;
    }

    /**
     * Delete MediaObject and file
     */
    public function deleteMediaObject(MediaObject $mediaObject): void
    {
        // Delete physical file
        $projectDir = dirname(__DIR__, 2);
        $filePath = $projectDir . $mediaObject->getFilePath();
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete thumbnail if exists
        if ($mediaObject->getThumbnailPath()) {
            $thumbnailPath = $projectDir . $mediaObject->getThumbnailPath();
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }

        // Delete database record
        $this->repository->remove($mediaObject);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('File size exceeds 10MB limit');
        }

        // Check extension
        $extension = $file->guessExtension();
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \RuntimeException('File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
    }
}

