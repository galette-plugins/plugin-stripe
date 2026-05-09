<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-community.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe;

use DI\Attribute\Inject;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Plugins\DashboardProviderInterface;
use Galette\Core\Plugins\MenuProviderInterface;
use Galette\Core\Preferences;
use Galette\Core\GalettePlugin;

/**
 * Plugin Galette Legal Notices
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Mathieu PELLEGRIN <dev@pingveno.net>
 * @author manuelh78 <manuelh78dev@ik.me>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 */

class PluginGaletteStripe extends GalettePlugin implements MenuProviderInterface, DashboardProviderInterface
{
    #[Inject]
    private readonly Db $zdb; //@phpstan-ignore-line injected from DI

    /**
     * Get plugins menus
     *
     * @return array<string, string|array<string,mixed>>
     */
    public function getMenus(): array
    {
        /**
         * @var Login $login
         */
        global $login;
        $content = [
            'title' => _T("Stripe", "stripe"),
            'icon' => 'stripe'
        ];
        $content['items'] = [];

        if ($login->isAdmin() || $login->isStaff()) {
            $content['items'] = [
                [
                    'label' => _T("Stripe History", "stripe"),
                    'route' => [
                        'name' => 'stripe_history'
                    ]
                ],
                [
                    'label' => _T("Settings"),
                    'route' => [
                        'name' => 'stripe_preferences'
                    ]
                ]
            ];
        }

        $menus['plugin_stripe'] = $content;
        return $menus;
    }

    /**
     * Get plugins public menus
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getPublicMenus(): array
    {
        return [
            [
                'label' => _T("Payment form", "stripe"),
                'route' => [
                    'name' => 'stripe_form'
                ],
                'icon' => 'credit card outline'
            ]
        ];
    }

    /**
     * Get plugins dashboards
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getDashboards(): array
    {
        /** @var Login $login */
        global $login;
        /** @var Preferences $preferences */
        global $preferences;
        $contents = [];

        if ($preferences->showPublicPage($login, 'pref_publicpages_visibility_generic')) {
            $contents[] = [
                'label' => _T("Payment form", "stripe"),
                'route' => [
                    'name' => 'stripe_form'
                ],
                'icon' => 'credit_card'
            ];
        }
        return $contents;
    }

    /**
     * Get current logged-in user plugins dashboards
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getMyDashboards(): array
    {
        return [];
    }

    /**
     * Is the plugin fully installed (including database, extra configuration, etc.)?
     */
    public function isInstalled(): bool
    {
        return
            $this->zdb->tableExists(STRIPE_PREFIX . Stripe::TABLE)
            && $this->zdb->TableExists(STRIPE_PREFIX . StripeHistory::TABLE)
        ;
    }
}
