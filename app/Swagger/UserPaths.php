<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/profile',
    summary: 'Voir mon profil',
    tags: ['Profil'],
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Profil utilisateur'),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]
#[OA\Put(
    path: '/profile',
    summary: 'Mettre à jour mon profil',
    tags: ['Profil'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'prenom', type: 'string'),
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Profil mis à jour'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
    ]
)]
#[OA\Delete(
    path: '/profile',
    summary: 'Supprimer mon compte',
    tags: ['Profil'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['current_password'],
            properties: [new OA\Property(property: 'current_password', type: 'string', format: 'password')]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Compte supprimé'),
        new OA\Response(response: 422, description: 'Mot de passe incorrect'),
    ]
)]
#[OA\Get(
    path: '/addresses',
    summary: 'Lister mes adresses',
    tags: ['Adresses'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Liste des adresses')]
)]
#[OA\Post(
    path: '/addresses',
    summary: 'Ajouter une adresse',
    tags: ['Adresses'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['nom', 'prenom', 'rue', 'ville', 'code_postal'],
            properties: [
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'prenom', type: 'string'),
                new OA\Property(property: 'rue', type: 'string'),
                new OA\Property(property: 'ville', type: 'string'),
                new OA\Property(property: 'code_postal', type: 'string'),
                new OA\Property(property: 'pays', type: 'string'),
                new OA\Property(property: 'usage_type', type: 'string', enum: ['billing', 'shipping', 'both']),
                new OA\Property(property: 'is_default', type: 'boolean'),
                new OA\Property(property: 'is_default_shipping', type: 'boolean'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Adresse créée'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
    ]
)]
#[OA\Put(
    path: '/addresses/{id}',
    summary: 'Modifier une adresse',
    tags: ['Adresses'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'prenom', type: 'string'),
                new OA\Property(property: 'rue', type: 'string'),
                new OA\Property(property: 'ville', type: 'string'),
                new OA\Property(property: 'code_postal', type: 'string'),
                new OA\Property(property: 'usage_type', type: 'string', enum: ['billing', 'shipping', 'both']),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Adresse mise à jour'),
        new OA\Response(response: 404, description: 'Adresse introuvable'),
    ]
)]
#[OA\Delete(
    path: '/addresses/{id}',
    summary: 'Supprimer une adresse',
    tags: ['Adresses'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Adresse supprimée'),
        new OA\Response(response: 404, description: 'Adresse introuvable'),
    ]
)]
#[OA\Post(
    path: '/chat',
    summary: 'Envoyer un message au chatbot',
    tags: ['Chat'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['user_message'],
            properties: [
                new OA\Property(property: 'user_message', type: 'string', maxLength: 2000),
                new OA\Property(property: 'session_id', type: 'string', maxLength: 100),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Réponse du bot')]
)]
#[OA\Post(
    path: '/activity/page-view',
    summary: 'Enregistrer une vue de page',
    tags: ['Activité'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['page'],
            properties: [new OA\Property(property: 'page', type: 'string', maxLength: 120)]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Journalisé ou ignoré')]
)]
class UserPaths {}
