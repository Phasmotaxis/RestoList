<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/DiscoveryController.php';

/**
 * Restaurant Discovery Controller: Customer Portal restaurant discovery.
 *
 * Provides sort allowlist and restaurant browsing per audit specification.
 */
class RestaurantDiscoveryController extends DiscoveryController
{
    public const SORT_ALLOWLIST = [
        'name' => 't.name',
        'cuisine_type' => 'cuisine_type',
        'city' => 'city',
        'created_at' => 't.created_at',
    ];
}
