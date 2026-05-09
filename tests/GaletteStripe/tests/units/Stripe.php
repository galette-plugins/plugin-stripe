<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-community.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Stripe tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 */
class Stripe extends GaletteTestCase
{
    protected int $seed = 20250617153548;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(STRIPE_PREFIX . \GaletteStripe\Stripe::TABLE);
        $this->zdb->execute($delete);
        parent::tearDown();
    }

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $stripe = new \GaletteStripe\Stripe($this->zdb, $this->preferences);
        $this->assertSame('', $stripe->getPubKey());
        $this->assertSame('', $stripe->getPrivKey());
        $this->assertSame('', $stripe->getWebhookSecret());
        $this->assertSame('FR', $stripe->getCountry());
        $this->assertSame('eur', $stripe->getCurrency());

        $amounts = $stripe->getAmounts($this->login);
        $this->assertCount(0, $amounts);
        $this->assertCount(7, $stripe->getAllAmounts());
        $this->assertTrue($stripe->areAmountsLoaded());
        $this->assertTrue($stripe->isLoaded());
    }
}
