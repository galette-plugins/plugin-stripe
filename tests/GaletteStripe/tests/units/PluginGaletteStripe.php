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
 */
class PluginGaletteStripe extends GaletteTestCase
{
    protected int $seed = 20260928043512;
    //tables are created: on MySQL, that would commit the test transaction
    protected bool $db_transactions = false;

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
