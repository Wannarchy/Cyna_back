<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/auth/register',
    summary: 'Inscription',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['prenom', 'nom', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'prenom', type: 'string'),
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Compte créé, email de vérification envoyé'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
    ]
)]
#[OA\Post(
    path: '/auth/login',
    summary: 'Connexion',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Token Sanctum ou challenge OTP admin'),
        new OA\Response(response: 401, description: 'Identifiants invalides'),
        new OA\Response(response: 403, description: 'Compte désactivé'),
    ]
)]
#[OA\Post(
    path: '/auth/verify-admin-otp',
    summary: 'Valider le code OTP administrateur',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['challenge_token', 'code'],
            properties: [
                new OA\Property(property: 'challenge_token', type: 'string'),
                new OA\Property(property: 'code', type: 'string'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Connexion admin réussie'),
        new OA\Response(response: 422, description: 'Code invalide ou expiré'),
    ]
)]
#[OA\Post(
    path: '/auth/forgot-password',
    summary: 'Demande de réinitialisation du mot de passe',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Email envoyé si le compte existe')]
)]
#[OA\Post(
    path: '/auth/reset-password',
    summary: 'Réinitialiser le mot de passe',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Mot de passe réinitialisé'),
        new OA\Response(response: 422, description: 'Token invalide'),
    ]
)]
#[OA\Post(
    path: '/auth/verify-email',
    summary: 'Confirmer l\'adresse email',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token'],
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'token', type: 'string'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Email vérifié'),
        new OA\Response(response: 422, description: 'Lien invalide ou expiré'),
    ]
)]
#[OA\Post(
    path: '/auth/resend-verification-email',
    summary: 'Renvoyer l\'email de vérification (public)',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'id', type: 'integer'),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Email renvoyé si compte non confirmé')]
)]
#[OA\Post(
    path: '/auth/logout',
    summary: 'Déconnexion',
    tags: ['Auth'],
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Déconnexion réussie'),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]
#[OA\Post(
    path: '/auth/resend-verification',
    summary: 'Renvoyer l\'email de vérification (connecté)',
    tags: ['Auth'],
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Email renvoyé'),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]
class AuthPaths {}
