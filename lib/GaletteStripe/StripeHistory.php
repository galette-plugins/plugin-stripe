<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Galette;
use Galette\Core\Login;
use Galette\Core\History;
use Galette\Core\Preferences;
use Galette\Entity\Adherent;
use GaletteStripe\Filters\StripeHistoryList;
use Stripe\StripeClient;
use Laminas\Db\Sql\Select;
use Safe\DateTime;
use Throwable;

/**
 * This class stores and serve the Stripe History.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Mathieu PELLEGRIN <dev@pingveno.net>
 * @author manuelh78 <manuelh78dev@ik.me>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 */
class StripeHistory extends History
{
    public const string TABLE = 'history';
    public const string PK = 'id_stripe';

    public const int STATE_NONE = 0;
    public const int STATE_PROCESSED = 1;
    public const int STATE_ERROR = 2;
    public const int STATE_PUBLIC = 3;
    public const int STATE_ALREADYDONE = 4;

    private int $id;
    /** @var array<int, string> */
    private array $methods;
    /** @var array<int, string> */
    private array $reasons;

    /**
     * Default constructor.
     *
     * @param Db                 $zdb         Database
     * @param Login              $login       Login
     * @param Preferences        $preferences Preferences
     * @param ?StripeHistoryList $filters     Filtering
     */
    public function __construct(Db $zdb, Login $login, Preferences $preferences, ?StripeHistoryList $filters = null)
    {
        $this->with_lists = false;
        parent::__construct($zdb, $login, $preferences, $filters);
    }

    /**
     * Add a new entry
     *
     * @param array<string, mixed>|string $action   the action to log
     * @param string                      $argument the argument
     * @param string                      $query    the query (if relevant)
     *
     * @return bool true if entry was successfully added, false otherwise
     */
    public function add(array|string $action, string $argument = '', string $query = ''): bool
    {
        $stripe = new Stripe($this->zdb, $this->preferences);
        $request = $action;
        $payment_method = $this->getStripePaymentMethod($request['data']['object']['payment_method']);
        $charge = $this->getStripeCharge($request['data']['object']['latest_charge']);

        try {
            $values = [
                'history_date'  => date('Y-m-d H:i:s'),
                'intent_id'     => $request['data']['object']['id'],
                'payer_name'    => $payment_method['billing_details']['name'],
                'member_id'     => $request['data']['object']['metadata']['member_id'] ?? 0,
                'comments'      => $request['data']['object']['metadata']['item_name'],
                'amount'        => $stripe->isZeroDecimal($stripe->getCurrency()) ? $request['data']['object']['amount'] : $request['data']['object']['amount'] / 100,
                'method'        => $payment_method['type'],
                'state'         => self::STATE_NONE,
                'receipt_url'   => $charge['receipt_url'],
                'request'       => Galette::jsonEncode($request)
            ];

            $insert = $this->zdb->insert($this->getTableName());
            $insert->values($values);
            $this->zdb->execute($insert);
            $this->id = (int)$this->zdb->driver->getLastGeneratedValue();

            Analog::log(
                'An entry has been added in stripe history',
                Analog::DEBUG
            );
        } catch (\Exception $e) {
            Analog::log(
                "An error occured trying to add log entry. " . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }

        return true;
    }

    /**
     * Get table's name
     *
     * @param bool $prefixed Whether table name should be prefixed
     */
    protected function getTableName(bool $prefixed = false): string
    {
        if ($prefixed === true) {
            return PREFIX_DB . STRIPE_PREFIX . self::TABLE;
        } else {
            return STRIPE_PREFIX . self::TABLE;
        }
    }

    /**
     * Get table's PK
     */
    protected function getPk(): string
    {
        return self::PK;
    }

    /**
     * Gets Stripe history
     *
     * @return array<int, object>
     */
    public function getStripeHistory(): array
    {
        $select = $this->zdb->select($this->getTableName());
        $this->buildLists($select);

        $orig = $this->getHistory();
        $new = [];
        if (count($orig) > 0) {
            foreach ($orig as $o) {
                try {
                    if (Galette::isSerialized($o['request'])) {
                        $oa = unserialize($o['request']);
                    } else {
                        $oa = Galette::jsonDecode($o['request']);
                    }

                    $o['member_fullname'] = $this->getMemberFullName($o['member_id']);
                    $o['raw_request'] = print_r($oa, true);
                    $o['request'] = $oa;

                    $new[] = $o;
                } catch (\Exception $e) {
                    Analog::log(
                        'Error loading Stripe history entry #' . $o[$this->getPk()]
                        . ' ' . $e->getMessage(),
                        Analog::WARNING
                    );
                }
            }
        }
        return $new;
    }

    /**
     * Gets Member full name
     *
     * @param int $id ID of the member to retrieve
     */
    private function getMemberFullName(int $id): string
    {
        $fullname = _T('None', 'stripe');

        $select = $this->zdb->select(Adherent::TABLE);
        $select->columns(['prenom_adh', 'nom_adh']);
        $select->where(['id_adh' => $id]);
        $result = $this->zdb->execute($select);
        $row = $result->current();

        if ($row) {
            $fullname = mb_strtoupper($row['nom_adh'], 'UTF-8') . ' ' . $row['prenom_adh'];
        }

        return $fullname;
    }

    /**
     * Builds payment reasons and methods lists
     *
     * @param Select $select Original select
     */
    protected function buildLists(Select $select): void
    {
        $this->reasons = [];
        try {
            $reasonsSelect = clone $select;
            $reasonsSelect->reset($reasonsSelect::COLUMNS);
            $reasonsSelect->reset($reasonsSelect::ORDER);
            $reasonsSelect->quantifier('DISTINCT')->columns(['comments']);
            $reasonsSelect->order(['comments ASC']);

            $results = $this->zdb->execute($reasonsSelect);

            foreach ($results as $result) {
                $rlabel = $result->comments;
                if ($rlabel === '') {
                    $rlabel = _T('None');
                }
                $this->reasons[] = $rlabel;
            }
        } catch (Throwable $e) {
            Analog::log(
                'Cannot list payment reasons from history! | ' . $e->getMessage(),
                Analog::WARNING
            );
        }

        $this->methods = [];
        try {
            $methodsSelect = clone $select;
            $methodsSelect->reset($methodsSelect::COLUMNS);
            $methodsSelect->reset($methodsSelect::ORDER);
            $methodsSelect->quantifier('DISTINCT')->columns(['method']);
            $methodsSelect->order(['method ASC']);

            $results = $this->zdb->execute($methodsSelect);

            foreach ($results as $result) {
                $mlabel = $result->method;
                if ($mlabel === '') {
                    $mlabel = _T('None');
                }
                $this->methods[] = $mlabel;
            }
        } catch (Throwable $e) {
            Analog::log(
                'Cannot list payment methods from history! | ' . $e->getMessage(),
                Analog::WARNING
            );
        }
    }

    /**
     * Gets Stripe Payment Method details
     *
     * @param string $id ID of the payment method to retrieve
     * @return array<string, mixed>
     */
    public function getStripePaymentMethod(string $id): array
    {
        $stripe = new Stripe($this->zdb, $this->preferences);
        $stripe_client = new StripeClient($stripe->getPrivKey());

        $paymentMethod = $stripe_client->paymentMethods->retrieve($id, []);
        $content = json_encode($paymentMethod);

        return json_decode($content, true);
    }

    /**
     * Gets Stripe Charge details
     *
     * @param string $id ID of the charge to retrieve
     * @return array<string, mixed>
     */
    public function getStripeCharge(string $id): array
    {
        $stripe = new Stripe($this->zdb, $this->preferences);
        $stripe_client = new StripeClient($stripe->getPrivKey());

        $charge = $stripe_client->charges->retrieve($id, []);
        $content = json_encode($charge);

        return json_decode($content, true);
    }

    /**
     * Builds the order clause
     *
     * @return array<int, string> SQL ORDER clause
     */
    protected function buildOrderClause(): array
    {
        /** @var StripeHistoryList $filters */
        $filters = $this->filters;
        $order = [];

        switch ($this->filters->orderby) {
            case StripeHistoryList::ORDERBY_DATE:
                $order[] = 'history_date ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_PAYMENT:
                $order[] = 'intent_id ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_PAYER:
                $order[] = 'payer_name ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_MEMBER:
                $order[] = 'member_id ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_REASON:
                $order[] = 'comments ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_AMOUNT:
                $order[] = 'amount ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_METHOD:
                $order[] = 'method ' . $filters->getDirection();
                break;
            case StripeHistoryList::ORDERBY_STATE:
                $order[] = 'state ' . $filters->getDirection();
                break;
        }

        return $order;
    }

    /**
     * Builds where clause, for filtering on simple list mode
     *
     * @param Select $select Original select
     */
    protected function buildWhereClause(Select $select): void
    {
        try {
            /** @var StripeHistoryList $filters */
            $filters = $this->filters;

            if ($filters->start_date_filter !== null) {
                $d = new DateTime($filters->raw_start_date_filter);
                $d->setTime(0, 0, 0);
                $select->where->greaterThanOrEqualTo(
                    'history_date',
                    $d->format('Y-m-d H:i:s')
                );
            }

            if ($filters->end_date_filter !== null) {
                $d = new DateTime($filters->raw_end_date_filter);
                $d->setTime(23, 59, 59);
                $select->where->lessThanOrEqualTo(
                    'history_date',
                    $d->format('Y-m-d H:i:s')
                );
            }

            if ($filters->payment_filter !== null) {
                $select->where->like(
                    'intent_id',
                    '%' . $filters->payment_filter . '%'
                );
            }

            if ($filters->payer_filter !== null) {
                $select->where->like(
                    'payer_name',
                    '%' . $filters->payer_filter . '%'
                );
            }

            if ($filters->reason_filter !== null && $filters->reason_filter != '0') {
                $select->where->equalTo(
                    'comments',
                    $filters->reason_filter
                );
            }

            if ($filters->method_filter !== null && $filters->method_filter != '0') {
                $select->where->equalTo(
                    'method',
                    $filters->method_filter
                );
            }
        } catch (Throwable $e) {
            Analog::log(
                __METHOD__ . ' | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Is payment already processed?
     *
     * @param array<string, mixed> $request Verify sign stripe parameter
     */
    public function isProcessed(array $request): bool
    {
        $select = $this->zdb->select($this->getTableName());
        $select->where(
            [
                'intent_id' => $request['data']['object']['id'],
                'state'     => [self::STATE_PROCESSED, self::STATE_PUBLIC]
            ]
        );
        $results = $this->zdb->execute($select);

        return (count($results) > 0);
    }

    /**
     * Set payment state
     *
     * @param int $state State, one of self::STATE_ constants
     */
    public function setState(int $state): bool
    {
        try {
            $update = $this->zdb->update($this->getTableName());
            $update
                ->set(['state' => $state])
                ->where([self::PK => $this->id]);
            $this->zdb->execute($update);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'An error occurred when updating state field | ' . $e->getMessage(),
                Analog::ERROR
            );
        }
        return false;
    }

    /**
     * Get payment reasons list
     *
     * @return array<int, string>
     */
    public function getPaymentReasonsList(): array
    {
        return $this->reasons;
    }

    /**
     * Get payment methods list
     *
     * @return array<int, string>
     */
    public function getPaymentMethodsList(): array
    {
        return $this->methods;
    }
}
