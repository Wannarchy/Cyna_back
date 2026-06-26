<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/orders',
    summary: 'Lister mes commandes',
    tags: ['Commandes'],
    security: [['sanctum' => []]],
    description: 'Email vérifié requis.',
    responses: [
        new OA\Response(response: 200, description: 'Liste des commandes'),
        new OA\Response(response: 403, description: 'Email non vérifié'),
    ]
)]
#[OA\Post(
    path: '/orders',
    summary: 'Créer une commande',
    tags: ['Commandes'],
    security: [['sanctum' => []]],
    description: 'Email vérifié requis. Paiement via Stripe PaymentMethod.',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['billing_name', 'billing_address', 'payment_method', 'items'],
            properties: [
                new OA\Property(property: 'billing_name', type: 'string'),
                new OA\Property(property: 'billing_address', type: 'string'),
                new OA\Property(property: 'shipping_name', type: 'string'),
                new OA\Property(property: 'shipping_address', type: 'string'),
                new OA\Property(property: 'payment_method', type: 'string', description: 'ID Stripe PaymentMethod (pm_...)'),
                new OA\Property(property: 'promo_code', type: 'string'),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        required: ['product_id', 'cycle'],
                        properties: [
                            new OA\Property(property: 'product_id', type: 'integer'),
                            new OA\Property(property: 'cycle', type: 'string', enum: ['monthly', 'yearly']),
                        ]
                    )
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Commande créée'),
        new OA\Response(response: 422, description: 'Paiement refusé ou validation échouée'),
    ]
)]
#[OA\Get(
    path: '/orders/{id}',
    summary: 'Détail d\'une commande',
    tags: ['Commandes'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Commande trouvée'),
        new OA\Response(response: 404, description: 'Commande introuvable'),
    ]
)]
#[OA\Get(
    path: '/subscriptions',
    summary: 'Lister mes abonnements',
    tags: ['Abonnements'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Liste des abonnements produits')]
)]
#[OA\Post(
    path: '/subscriptions/{id}/cancel',
    summary: 'Résilier un abonnement',
    tags: ['Abonnements'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Résiliation programmée'),
        new OA\Response(response: 404, description: 'Abonnement introuvable'),
    ]
)]
#[OA\Post(
    path: '/subscriptions/{id}/change-cycle',
    summary: 'Changer le cycle de facturation',
    tags: ['Abonnements'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['cycle'],
            properties: [new OA\Property(property: 'cycle', type: 'string', enum: ['monthly', 'yearly'])]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Cycle mis à jour'),
        new OA\Response(response: 404, description: 'Abonnement introuvable'),
        new OA\Response(response: 422, description: 'Cycle déjà actif ou erreur Stripe'),
    ]
)]
#[OA\Get(
    path: '/payment-methods',
    summary: 'Lister mes moyens de paiement',
    tags: ['Paiement'],
    security: [['sanctum' => []]],
    responses: [new OA\Response(response: 200, description: 'Cartes Stripe enregistrées')]
)]
#[OA\Post(
    path: '/payment-methods',
    summary: 'Ajouter un moyen de paiement',
    tags: ['Paiement'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['payment_method'],
            properties: [new OA\Property(property: 'payment_method', type: 'string', description: 'ID Stripe PaymentMethod')]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Carte ajoutée et définie par défaut')]
)]
#[OA\Delete(
    path: '/payment-methods/{id}',
    summary: 'Supprimer un moyen de paiement',
    tags: ['Paiement'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Carte supprimée'),
        new OA\Response(response: 404, description: 'Moyen de paiement introuvable'),
    ]
)]
#[OA\Post(
    path: '/payment-methods/{id}/default',
    summary: 'Définir la carte par défaut',
    tags: ['Paiement'],
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [new OA\Response(response: 200, description: 'Carte par défaut mise à jour')]
)]
#[OA\Post(
    path: '/promo-codes/validate',
    summary: 'Valider un code promo',
    tags: ['Promo'],
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code', 'amount'],
            properties: [
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'amount', type: 'number', format: 'float'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Remise calculée'),
        new OA\Response(response: 404, description: 'Code promo introuvable'),
    ]
)]
class CommercePaths {}
