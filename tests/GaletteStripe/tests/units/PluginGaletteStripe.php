<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Stripe plugin tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 */
class PluginGaletteStripe extends GaletteTestCase
{
    protected int $seed = 20260928043512;
    //tables are created: on MySQL, that would commit the test transaction
    protected bool $db_transactions = false;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Get menu items routes
     *
     * @return array<string>
     */
    private function getMenuRoutes(): array
    {
        $plugin = $this->container->get(\GaletteStripe\PluginGaletteStripe::class);
        $menus = $plugin->getMenus();
        return array_map(
            fn($item) => $item['route']['name'],
            $menus['plugin_stripe']['items'] ?? []
        );
    }

    /**
     * Test menus by profile
     */
    public function testGetMenus(): void
    {
        $this->logSuperAdmin();
        $this->assertSame(
            [
                'stripe_history',
                'stripe_preferences',
            ],
            $this->getMenuRoutes()
        );
        $this->login->logout();

        $member = $this->getMemberOne();
        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
        $this->assertSame($mdata['login_adh'], $member->login);
        $this->assertSame([], $this->getMenuRoutes());
    }

    /**
     * The public form is declared to the core, with a visibility of its own
     */
    public function testPublicPage(): void
    {
        $name = 'pref_stripe_publicpages_visibility_form';
        $plugin = $this->container->get(\GaletteStripe\PluginGaletteStripe::class);

        $this->assertSame(['form' => ['routes' => ['stripe_form']]], $plugin->getPublicPages());
        $this->assertSame('Payment form', $plugin->getPublicPageLabel('stripe_form'));
        $this->assertTrue(\Galette\Core\PreferencesSchema::isPublicPage($name));
        $this->assertSame($name, \Galette\Core\PreferencesSchema::getPublicPageRight('stripe_form'));
        $this->assertSame(
            \Galette\Enums\PublicPageVisibility::Inherit->value,
            \Galette\Core\PreferencesSchema::get($name)['default']
        );

        //the public menu entry follows it, not the default visibility
        $this->setRawPreference('pref_bool_publicpages', true);
        $this->setRawPreference(
            'pref_publicpages_visibility_generic',
            \Galette\Enums\PublicPageVisibility::Hidden->value
        );
        $this->setRawPreference($name, \Galette\Enums\PublicPageVisibility::Everyone->value);
        $this->assertSame(['stripe_form'], array_map(
            fn($item) => $item['route']['name'],
            $plugin->getPublicMenuItems()
        ));

        $this->setRawPreference($name, \Galette\Enums\PublicPageVisibility::Inherit->value);
        $this->assertSame([], $plugin->getPublicMenuItems());
    }

    /**
     * Get plugin instance
     */
    private function getPlugin(): \GaletteStripe\PluginGaletteStripe
    {
        return $this->container->get(\GaletteStripe\PluginGaletteStripe::class);
    }

    /**
     * 0.0.x tables are detected, so that update scripts are run
     */
    public function testLegacyDbVersion(): void
    {
        $this->assertNull($this->getPlugin()->getLegacyDbVersion());

        $table = PREFIX_DB . STRIPE_PREFIX . 'types_cotisation_prices';
        $this->zdb->db->query(
            'CREATE TABLE ' . $table . ' (id_type_cotis integer NOT NULL, amount real)',
            \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        try {
            $this->assertSame(0.0, $this->getPlugin()->getLegacyDbVersion());
        } finally {
            $this->zdb->db->query('DROP TABLE ' . $table, \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        }
    }
}
