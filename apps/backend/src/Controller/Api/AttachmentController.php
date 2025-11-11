<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Task;
use App\Entity\TaskAttachment;
use App\Entity\User;
use App\Service\FileUploadService;
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

#[Route('/api/tasks/{taskId}/attachments', name: 'api_task_attachments_')]
#[OA\Tag(name: 'Task Attachments')]
class AttachmentController extends AbstractController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    #[OA\Post(
        summary: 'Upload file to task',
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
            new OA\Response(
                response: 201,
                description: 'File uploaded successfully',
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid file or file too large',
            ),
        ],
    )]
    public function upload(
        int $taskId,
        Request $request,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the task
        if ($task->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $attachment = $this->fileUploadService->uploadFile($file, $task, $user);

            return $this->json([
                'id'           => $attachment->getId(),
                'fileName'     => $attachment->getFileName(),
                'originalName' => $attachment->getOriginalName(),
                'mimeType'     => $attachment->getMimeType(),
                'fileSize'     => $attachment->getFileSize(),
                'fileType'     => $attachment->getFileType(),
                'filePath'     => $attachment->getFilePath(),
                'uploadedAt'   => $attachment->getUploadedAt()->format('Y-m-d H:i:s'),
                'uploadedBy'   => [
                    'id'    => $attachment->getUploadedBy()->getId(),
                    'email' => $attachment->getUploadedBy()->getEmail(),
                ],
            ], Response::HTTP_CREATED);
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete attachment',
        responses: [
            new OA\Response(
                response: 204,
                description: 'Attachment deleted successfully',
            ),
            new OA\Response(
                response: 404,
                description: 'Attachment not found',
            ),
        ],
    )]
    public function delete(
        int $taskId,
        int $id,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $attachment = $this->entityManager->getRepository(TaskAttachment::class)->find($id);

        if (!$attachment) {
            return $this->json(['error' => 'Attachment not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the task
        if ($attachment->getTask()->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->fileUploadService->deleteFile($attachment);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get list of task attachments',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of attachments',
            ),
        ],
    )]
    public function list(
        int $taskId,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the task
        if ($task->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $attachments = $task->getAttachments()->toArray();

        $response = array_map(fn (TaskAttachment $a) => [
            'id'            => $a->getId(),
            'fileName'      => $a->getFileName(),
            'originalName'  => $a->getOriginalName(),
            'mimeType'      => $a->getMimeType(),
            'fileSize'      => $a->getFileSize(),
            'fileSizeHuman' => $a->getHumanReadableSize(),
            'fileType'      => $a->getFileType(),
            'filePath'      => $a->getFilePath(),
            'uploadedAt'    => $a->getUploadedAt()->format('Y-m-d H:i:s'),
        ], $attachments);

        return $this->json($response);
    }
}
