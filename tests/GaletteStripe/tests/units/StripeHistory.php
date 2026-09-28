<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\tests\units;

use Galette\Tests\GaletteTestCase;
use GaletteStripe\Filters\StripeHistoryList;

/**
 * Stripe history tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class StripeHistory extends GaletteTestCase
{
    protected int $seed = 20260928041005;

    /**
     * Add an history entry, as stored by previous versions
     *
     * @param string $request Stored request
     */
    private function addLegacyEntry(string $request): void
    {
        $insert = $this->zdb->insert(STRIPE_PREFIX . \GaletteStripe\StripeHistory::TABLE);
        $insert->values([
            'history_date' => date('Y-m-d H:i:s'),
            'intent_id' => 'pi_legacy',
            'amount' => 10,
            'comments' => 'donation in money',
            'request' => $request,
            'state' => \GaletteStripe\StripeHistory::STATE_PUBLIC,
            'member_id' => 0,
            'method' => ''
        ]);
        $this->zdb->execute($insert);
    }

    /**
     * Serialized entries of previous versions are read as plain data
     */
    public function testLegacySerializedEntries(): void
    {
        $this->addLegacyEntry(serialize(['item_id' => '5', 'item_name' => 'donation in money']));
        $this->addLegacyEntry(serialize(new \ArrayObject(['item_id' => '5'])));

        $history = new \GaletteStripe\StripeHistory($this->zdb, $this->login, $this->preferences);
        $history->setFilters(new StripeHistoryList());
        $entries = $history->getStripeHistory();

        $this->assertCount(2, $entries);
        $requests = array_column($entries, 'request');
        $this->assertContains(['item_id' => '5', 'item_name' => 'donation in money'], $requests);
        foreach ($requests as $request) {
            $this->assertNotInstanceOf(\ArrayObject::class, $request);
        }
    }
}
