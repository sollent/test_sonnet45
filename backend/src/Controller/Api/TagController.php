<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Response\Tag\TagResponseDto;
use App\Entity\Tag;
use App\Repository\Database\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/tags')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Tags')]
class TagController extends AbstractController
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_tags_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get list of user tags',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: null)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tags',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TagResponseDto::class))
                )
            )
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $search = $request->query->get('search');
        $limit = $request->query->getInt('limit');

        if ($search) {
            $tags = $this->tagRepository->searchTags($user, $search);
        } else {
            $tags = $this->tagRepository->findUserTags($user, $limit ?: null);
        }

        $response = array_map(
            fn($tag) => TagResponseDto::fromEntity($tag),
            $tags
        );

        return $this->json($response);
    }

    #[Route('/most-used', name: 'api_tags_most_used', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get most used tags',
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of most used tags',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TagResponseDto::class))
                )
            )
        ]
    )]
    public function mostUsed(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 5);
        $tags = $this->tagRepository->getMostUsedTags($this->getUser(), $limit);

        $response = array_map(
            fn($tag) => TagResponseDto::fromEntity($tag),
            $tags
        );

        return $this->json($response);
    }

    #[Route('/{id}', name: 'api_tag_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get single tag',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag details',
                content: new OA\JsonContent(ref: new Model(type: TagResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Tag not found')
        ]
    )]
    public function show(Tag $tag): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $tag);

        return $this->json(TagResponseDto::fromEntity($tag));
    }

    #[Route('', name: 'api_tag_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create new tag',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 50),
                    new OA\Property(property: 'color', type: 'string', example: '#3B82F6'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tag created',
                content: new OA\JsonContent(ref: new Model(type: TagResponseDto::class))
            ),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 409, description: 'Tag with this name already exists')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Check if tag already exists
        $existingTag = $this->tagRepository->findByNameAndUser($data['name'] ?? '', $user);
        if ($existingTag) {
            return $this->json(
                ['message' => 'Tag with this name already exists'],
                Response::HTTP_CONFLICT
            );
        }

        $tag = new Tag();
        $tag->setName($data['name'] ?? '');
        $tag->setColor($data['color'] ?? '#3B82F6');
        $tag->setIcon($data['icon'] ?? null);
        $tag->setUser($user);

        // Validate
        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $this->json(
            TagResponseDto::fromEntity($tag),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_tag_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(
        summary: 'Update tag',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 50),
                    new OA\Property(property: 'color', type: 'string', example: '#3B82F6'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag updated',
                content: new OA\JsonContent(ref: new Model(type: TagResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 409, description: 'Tag with this name already exists')
        ]
    )]
    public function update(Tag $tag, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $tag);

        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Update fields
        if (isset($data['name']) && $data['name'] !== $tag->getName()) {
            // Check if new name already exists
            $existingTag = $this->tagRepository->findByNameAndUser($data['name'], $user);
            if ($existingTag && $existingTag->getId() !== $tag->getId()) {
                return $this->json(
                    ['message' => 'Tag with this name already exists'],
                    Response::HTTP_CONFLICT
                );
            }
            $tag->setName($data['name']);
        }

        if (isset($data['color'])) {
            $tag->setColor($data['color']);
        }

        if (array_key_exists('icon', $data)) {
            $tag->setIcon($data['icon']);
        }

        // Validate
        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json(TagResponseDto::fromEntity($tag));
    }

    #[Route('/{id}', name: 'api_tag_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete tag',
        responses: [
            new OA\Response(response: 204, description: 'Tag deleted'),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function delete(Tag $tag): Response
    {
        $this->denyAccessUnlessGranted('delete', $tag);

        $this->entityManager->remove($tag);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
