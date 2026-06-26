<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'CYNA API',
    description: 'Documentation API REST CYNA — cybersécurité SaaS'
)]
#[OA\Server(
    url: '/api',
    description: 'API CYNA'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token Bearer Sanctum (Authorization: Bearer {token})'
)]
#[OA\Tag(name: 'Auth', description: 'Authentification')]
#[OA\Tag(name: 'Catalogue', description: 'Produits et catégories (public)')]
#[OA\Tag(name: 'Contenu', description: "Page d'accueil")]
#[OA\Tag(name: 'Billing', description: 'Configuration paiement')]
#[OA\Tag(name: 'Contact', description: 'Formulaire de contact')]
#[OA\Tag(name: 'Profil', description: 'Profil utilisateur')]
#[OA\Tag(name: 'Adresses', description: 'Adresses facturation / livraison')]
#[OA\Tag(name: 'Chat', description: 'Chatbot')]
#[OA\Tag(name: 'Activité', description: 'Journalisation des pages vues')]
#[OA\Tag(name: 'Commandes', description: 'Commandes (email vérifié requis)')]
#[OA\Tag(name: 'Abonnements', description: 'Abonnements produits')]
#[OA\Tag(name: 'Paiement', description: 'Moyens de paiement Stripe')]
#[OA\Tag(name: 'Promo', description: 'Codes promotionnels')]
#[OA\Tag(name: 'Admin', description: 'Back-office administrateur')]
class OpenApi {}
