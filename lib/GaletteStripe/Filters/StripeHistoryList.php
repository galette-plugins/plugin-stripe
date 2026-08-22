<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\Filters;

use Galette\Filters\HistoryList;
use Analog\Analog;

/**
 * Stripe History lists filters and paginator
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 *
 * @property ?string $payment_filter
 * @property ?string $payer_filter
 * @property ?string $reason_filter
 * @property ?string $method_filter
 */

class StripeHistoryList extends HistoryList
{
    public const int ORDERBY_DATE = 0;
    public const int ORDERBY_PAYMENT = 1;
    public const int ORDERBY_PAYER = 2;
    public const int ORDERBY_MEMBER = 3;
    public const int ORDERBY_REASON = 4;
    public const int ORDERBY_AMOUNT = 5;
    public const int ORDERBY_METHOD = 6;
    public const int ORDERBY_STATE = 7;

    //filters
    private ?string $payment_filter = null;
    private ?string $payer_filter = null;
    private ?string $reason_filter = null;
    private ?string $method_filter = null;

    /** @var array<string>  */
    protected array $list_fields = [
        'start_date_filter',
        'raw_start_date_filter',
        'end_date_filter',
        'raw_end_date_filter',
        'payment_filter',
        'payer_filter',
        'reason_filter',
        'method_filter'
    ];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->reinit();
    }

    /**
     * Reinit default parameters
     */
    public function reinit(): void
    {
        parent::reinit();
        $this->payment_filter = null;
        $this->payer_filter = null;
        $this->reason_filter = null;
        $this->method_filter = null;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get(string $name): mixed
    {
        if (in_array($name, $this->pagination_fields)) {
            return parent::__get($name);
        } elseif (in_array($name, $this->list_fields)) {
            return match ($name) {
                'raw_start_date_filter' => $this->getDate('start_date_filter', true, false),
                'raw_end_date_filter' => $this->getDate('end_date_filter', true, false),
                'start_date_filter', 'end_date_filter' => $this->getDate($name),
                default => $this->$name,
            };
        }

        throw new \RuntimeException(
            sprintf(
                'Unable to get property "%s::%s"!',
                static::class,
                $name
            )
        );
    }

    /**
     * Global isset method
     * Required for twig to access properties via __get
     *
     * @param string $name name of the property we want to retrieve
     */
    public function __isset(string $name): bool
    {
        return parent::__isset($name);
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param mixed  $value a relevant value for the property
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, $this->pagination_fields)) {
            parent::__set($name, $value);
        } else {
            Analog::log(
                '[' . static::class . '] Setting property `' . $name . '`',
                Analog::DEBUG
            );

            switch ($name) {
                case 'start_date_filter':
                case 'end_date_filter':
                    $this->setFilterDate($name, $value, $name === 'start_date_filter');
                    break;
                default:
                    $this->$name = $value;
                    break;
            }
        }
    }
}
