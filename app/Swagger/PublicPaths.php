<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/products',
    summary: 'Liste des produits',
    tags: ['Catalogue'],
    parameters: [
        new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'is_featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', maximum: 48)),
    ],
    responses: [new OA\Response(response: 200, description: 'Liste des produits disponibles')]
)]
#[OA\Get(
    path: '/products/{id}',
    summary: 'Détail d\'un produit',
    tags: ['Catalogue'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Produit trouvé'),
        new OA\Response(response: 404, description: 'Produit introuvable'),
    ]
)]
#[OA\Get(
    path: '/categories',
    summary: 'Liste des catégories actives',
    tags: ['Catalogue'],
    responses: [new OA\Response(response: 200, description: 'Liste des catégories')]
)]
#[OA\Get(
    path: '/homepage',
    summary: "Contenu de la page d'accueil",
    tags: ['Contenu'],
    responses: [new OA\Response(response: 200, description: 'Slides et contenu éditorial')]
)]
#[OA\Get(
    path: '/billing/config',
    summary: 'Configuration Stripe publique',
    tags: ['Billing'],
    responses: [new OA\Response(response: 200, description: 'Clé publique Stripe (pk_...)')]
)]
#[OA\Post(
    path: '/contact',
    summary: 'Envoyer un message de contact',
    tags: ['Contact'],
    description: 'Auth optionnelle (Sanctum). Invités acceptés.',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'sujet', 'message'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'sujet', type: 'string'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Message enregistré'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
        new OA\Response(response: 429, description: 'Trop de requêtes (10/min)'),
    ]
)]
class PublicPaths {}
