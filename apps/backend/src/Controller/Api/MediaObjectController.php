<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\MediaObject;
use App\Entity\User;
use App\Service\MediaObjectService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OpenApi\Attributes as OA;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/media', name: 'api_media_')]
#[OA\Tag(name: 'Media Objects')]
class MediaObjectController extends AbstractController
{
    public function __construct(
        private readonly MediaObjectService $mediaObjectService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    #[OA\Post(
        summary: 'Upload file',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'File uploaded successfully'),
            new OA\Response(response: 400, description: 'Invalid file'),
        ],
    )]
    public function upload(
        Request $request,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $mediaObject = $this->mediaObjectService->uploadFile($file, $user);

            return $this->json([
                'id'            => $mediaObject->getId(),
                'fileName'      => $mediaObject->getFileName(),
                'originalName'  => $mediaObject->getOriginalName(),
                'mimeType'      => $mediaObject->getMimeType(),
                'fileSize'      => $mediaObject->getFileSize(),
                'fileSizeHuman' => $mediaObject->getHumanReadableSize(),
                'fileType'      => $mediaObject->getFileType(),
                'filePath'      => $mediaObject->getFilePath(),
                'createdAt'     => $mediaObject->getCreatedAt()->format('Y-m-d H:i:s'),
            ], Response::HTTP_CREATED);
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete media object',
        responses: [
            new OA\Response(response: 204, description: 'Deleted successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function delete(
        int $id,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $mediaObject = $this->entityManager->getRepository(MediaObject::class)->find($id);

        if (!$mediaObject) {
            return $this->json(['error' => 'Media object not found'], Response::HTTP_NOT_FOUND);
        }

        // Check ownership
        if ($mediaObject->getUploadedBy()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->mediaObjectService->deleteMediaObject($mediaObject);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
