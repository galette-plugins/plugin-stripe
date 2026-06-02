<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-community.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

/** @var \Galette\Core\Plugins $this */
$this->register(
    name: 'Galette Stripe',                                     //Name
    desc: 'Stripe integration',                                 //Short description
    author: 'Mathieu PELLEGRIN, manuelh78, Guillaume AGNIERAY', //Author
    version: '1.0.0-alpha1',                                    //Version
    compver: '1.3.0',                                           //Galette compatible version
    route: 'stripe',                                            //Routing name and translation domain
    date: '2025-12-08',                                         //Release date
    acls: [                                                     //Permissions needed
        'stripe_preferences'        => 'staff',
        'store_stripe_preferences'  => 'staff',
        'stripe_history'            => 'staff',
        'filter_stripe_history'     => 'staff',
        'refresh_currencies'        => 'admin'
    ],
    dbver: 1.00                                                 //DB version
);

$this->setCsrfExclusions(
    [
        '/stripe_(webhook|success|cancel)/',
    ]
);
