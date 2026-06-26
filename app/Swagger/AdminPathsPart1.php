<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/admin/logs',
    summary: 'Journal d\'activité',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'actor_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['admin', 'user', 'guest'])),
        new OA\Parameter(name: 'admin_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'target_type', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'target_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Logs paginés')]
)]
#[OA\Get(
    path: '/admin/users',
    summary: 'Lister les utilisateurs',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Liste des utilisateurs')]
)]
#[OA\Get(
    path: '/admin/users/{id}',
    summary: 'Détail d\'un utilisateur',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Utilisateur trouvé'),
        new OA\Response(response: 404, description: 'Utilisateur introuvable'),
    ]
)]
#[OA\Put(
    path: '/admin/users/{id}',
    summary: 'Modifier un utilisateur',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'prenom', type: 'string'),
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'est_actif', type: 'boolean'),
                new OA\Property(property: 'is_admin', type: 'boolean'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Utilisateur mis à jour')]
)]
#[OA\Delete(
    path: '/admin/users/{id}',
    summary: 'Supprimer un utilisateur',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Utilisateur supprimé')]
)]
#[OA\Patch(
    path: '/admin/users/{id}/bloquer',
    summary: 'Bloquer / débloquer un utilisateur',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['bloquer'],
            properties: [new OA\Property(property: 'bloquer', type: 'boolean')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Statut de blocage mis à jour')]
)]
#[OA\Get(
    path: '/admin/products',
    summary: 'Lister les produits (admin)',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Tous les produits')]
)]
#[OA\Post(
    path: '/admin/products',
    summary: 'Créer un produit',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'category_id', 'price_monthly'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer'),
                new OA\Property(property: 'price_monthly', type: 'number'),
                new OA\Property(property: 'price_yearly', type: 'number'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'is_available', type: 'boolean'),
                new OA\Property(property: 'is_featured', type: 'boolean'),
                new OA\Property(property: 'requires_shipping', type: 'boolean'),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Produit créé')]
)]
#[OA\Put(
    path: '/admin/products/{id}',
    summary: 'Modifier un produit',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Produit mis à jour')]
)]
#[OA\Delete(
    path: '/admin/products/{id}',
    summary: 'Supprimer un produit',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Produit supprimé')]
)]
#[OA\Get(
    path: '/admin/categories',
    summary: 'Lister les catégories (admin)',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Toutes les catégories')]
)]
#[OA\Post(
    path: '/admin/categories',
    summary: 'Créer une catégorie',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'is_active', type: 'boolean'),
                new OA\Property(property: 'sort_order', type: 'integer'),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Catégorie créée')]
)]
#[OA\Put(
    path: '/admin/categories/{id}',
    summary: 'Modifier une catégorie',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Catégorie mise à jour')]
)]
#[OA\Delete(
    path: '/admin/categories/{id}',
    summary: 'Supprimer une catégorie',
    tags: ['Admin'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Catégorie supprimée')]
)]
class AdminPathsPart1 {}
