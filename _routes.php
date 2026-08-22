<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

use Galette\Middleware\Authenticate;
use GaletteStripe\Controllers\StripeController;
use Slim\Routing\RouteCollectorProxy;

//Include specific classes (stripe/stripe-php)
require_once 'vendor/autoload.php';

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$app->get(
    '/preferences',
    [StripeController::class, 'preferences']
)->setName('stripe_preferences')->add(Authenticate::class);

$app->post(
    '/preferences',
    [StripeController::class, 'storePreferences']
)->setName('store_stripe_preferences')->add(Authenticate::class);

$app->get(
    '/form',
    [StripeController::class, 'form']
)->setName('stripe_form');

$app->post(
    '/form',
    [StripeController::class, 'formCheckout']
)->setName('stripe_formCheckout');

$app->get(
    '/logs[/{option:order|reset|page}/{value}]',
    [StripeController::class, 'history']
)->setName('stripe_history')->add(Authenticate::class);

//history filtering
$app->post(
    '/history/filter',
    [StripeController::class, 'filter']
)->setName('filter_stripe_history')->add(Authenticate::class);

$app->post(
    '/webhook',
    [StripeController::class, 'webhook']
)->setName('stripe_webhook');

$app->get(
    '/success',
    [StripeController::class, 'successUrl']
)->setName('stripe_success');

$app->get(
    '/cancel',
    [StripeController::class, 'cancelUrl']
)->setName('stripe_cancel');

$app->group('/ajax', function (RouteCollectorProxy $app): void {
    $app->post(
        '/currencies',
        [StripeController::class, 'refreshCurrencies']
    )->setName('refresh_currencies')->add(Authenticate::class);
});
