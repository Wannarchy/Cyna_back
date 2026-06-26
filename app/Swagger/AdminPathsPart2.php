<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/admin/orders',
    summary: 'Lister les commandes (admin)',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Toutes les commandes')]
)]
#[OA\Get(
    path: '/admin/orders/{id}',
    summary: 'Détail d\'une commande (admin)',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Commande trouvée'),
        new OA\Response(response: 404, description: 'Commande introuvable'),
    ]
)]
#[OA\Patch(
    path: '/admin/orders/{id}/status',
    summary: 'Mettre à jour le statut d\'une commande',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [new OA\Property(property: 'status', type: 'string')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Statut mis à jour')]
)]
#[OA\Get(
    path: '/admin/promo-codes',
    summary: 'Lister les codes promo',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Liste des codes promo')]
)]
#[OA\Post(
    path: '/admin/promo-codes',
    summary: 'Créer un code promo',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code', 'type', 'value'],
            properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'type', type: 'string', enum: ['fixed', 'percent']),
                new OA\Property(property: 'value', type: 'number'),
                new OA\Property(property: 'is_active', type: 'boolean'),
                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Code promo créé')]
)]
#[OA\Put(
    path: '/admin/promo-codes/{id}',
    summary: 'Modifier un code promo',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Code promo mis à jour')]
)]
#[OA\Delete(
    path: '/admin/promo-codes/{id}',
    summary: 'Supprimer un code promo',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Code promo supprimé')]
)]
#[OA\Put(
    path: '/admin/homepage/slides',
    summary: 'Mettre à jour les slides homepage',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'slides',
                    type: 'array',
                    items: new OA\Items(type: 'object')
                ),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Slides mis à jour')]
)]
#[OA\Delete(
    path: '/admin/homepage/slides/{id}',
    summary: 'Supprimer un slide homepage',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Slide supprimé')]
)]
#[OA\Put(
    path: '/admin/homepage/content',
    summary: 'Mettre à jour le contenu homepage',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(type: 'object')
    ),
    responses: [new OA\Response(response: 200, description: 'Contenu mis à jour')]
)]
#[OA\Get(
    path: '/admin/contact-messages',
    summary: 'Lister les messages de contact',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Messages de contact')]
)]
#[OA\Patch(
    path: '/admin/contact-messages/{id}/status',
    summary: 'Changer le statut d\'un message',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [new OA\Property(property: 'status', type: 'string')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Statut mis à jour')]
)]
#[OA\Post(
    path: '/admin/contact-messages/{id}/reply',
    summary: 'Répondre à un message de contact',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [new OA\Property(property: 'message', type: 'string')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Réponse envoyée')]
)]
#[OA\Post(
    path: '/admin/uploads/image',
    summary: 'Uploader une image',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['image'],
                properties: [new OA\Property(property: 'image', type: 'string', format: 'binary')]
            )
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Image uploadée')]
)]
class AdminPathsPart2 {}
