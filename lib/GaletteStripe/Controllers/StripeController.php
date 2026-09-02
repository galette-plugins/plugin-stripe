<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\Controllers;

use Analog\Analog;
use DI\Attribute\Inject;
use Galette\Controllers\AbstractPluginController;
use Galette\Entity\Adherent;
use Galette\Entity\Contribution;
use Galette\Entity\ContributionsTypes;
use Galette\Entity\PaymentType;
use GaletteStripe\Stripe;
use GaletteStripe\StripeHistory;
use GaletteStripe\Filters\StripeHistoryList;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Stripe\StripeClient;
use Throwable;

/**
 * Galette Stripe plugin controller
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Mathieu PELLEGRIN <dev@pingveno.net>
 * @author manuelh78 <manuelh78dev@ik.me>
 * @author Guillaume AGNIERAY <dev@agnieray.net>
 */

class StripeController extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette Stripe")]
    protected array $module_info;

    /**
     * Main form
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function form(Request $request, Response $response): Response
    {
        $stripe = new Stripe($this->zdb, $this->preferences);

        $current_url = $this->preferences->getURL();

        $params = [
            'stripe'        => $stripe,
            'amounts'       => $stripe->getAmounts($this->login),
            'page_title'    => _T('Online payment', 'stripe'),
            'message'       => null,
            'current_url'   => rtrim($current_url, '/'),
        ];

        if (!$stripe->isLoaded()) {
            $this->flash->addMessageNow(
                'error',
                _T("<strong>Payment could not work</strong>: An error occurred (that has been logged) while loading Stripe settings from the database.<br/>Please report the issue to the staff.", "stripe")
                . '<br/>' . _T("Our apologies for the annoyance.", "stripe")
            );
        }

        if ($stripe->getPubKey() == null || $stripe->getPrivKey() == null) {
            $this->flash->addMessageNow(
                'error',
                _T("Stripe keys have not been defined. Please ask an administrator to add them in the plugin's settings.", "stripe")
            );
        }

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('stripe_form'),
            $params
        );
        return $response;
    }

    /**
     * Checkout form
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function formCheckout(Request $request, Response $response): Response
    {
        $stripe_request = $request->getParsedBody();
        $stripe = new Stripe($this->zdb, $this->preferences);
        $adherent = new Adherent($this->zdb);

        // Check the amount
        $item_id = $stripe_request['item_id'];
        $stripe_amounts = $stripe->getAmounts($this->login);
        $amount = $stripe_request['amount'];
        $amount_check = $stripe->isZeroDecimal($stripe->getCurrency()) ? round((float)$stripe_amounts[$item_id]['amount']) : $stripe_amounts[$item_id]['amount'];

        if ($amount < $amount_check) {
            $this->flash->addMessage(
                'error_detected',
                _T("The amount you've entered is lower than the minimum amount for the selected option. Please choose another option or change the amount.", "stripe")
            );

            return $response
                ->withStatus(301)
                ->withHeader('Location', $this->routeparser->urlFor('stripe_form'));
        } else {
            $metadata = [];

            if ($this->login->isLogged() && !$this->login->isSuperAdmin()) {
                $adherent->load($this->login->id);
                $metadata['member_id'] = $this->login->id;
            }

            $metadata['item_id'] = $item_id;
            $metadata['item_name'] = $stripe_amounts[$item_id]['name'];

            $checkout = $stripe->checkout($metadata, $amount, $stripe->getCurrency());

            if (!$checkout) {
                $this->flash->addMessage(
                    'error_detected',
                    _T('An error occurred loading the checkout form.', 'stripe')
                );

                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $this->routeparser->urlFor('stripe_form'));
            } else {
                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $checkout['url']);
            }
        }
    }

    /**
     * Logs page
     *
     * @param Request         $request  PSR Request
     * @param Response        $response PSR Response
     * @param string|null     $option   Either order, reset or page
     * @param string|int|null $value    Option value
     */
    public function history(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        $stripe_history = new StripeHistory($this->zdb, $this->login, $this->preferences);

        $filters = [];
        if (isset($this->session->filter_stripe_history)) {
            $filters = $this->session->filter_stripe_history;
        } else {
            $filters = new StripeHistoryList();
        }

        if (isset($request->getQueryParams()['nbshow'])) {
            $filters->show = $request->getQueryParams()['nbshow'];
        }

        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $filters->current_page = (int)$value;
                    break;
                case 'order':
                    $filters->orderby = $value;
                    break;
                default:
                    break;
            }
        }

        $this->session->filter_stripe_history = $filters;

        $stripe_history->setFilters($filters);
        $logs = $stripe_history->getStripeHistory();
        $logs_count = $stripe_history->getCount();

        //assign pagination variables to the template and add pagination links
        $filters->setViewPagination($this->routeparser, $this->view);

        $params = [
            'page_title'        => _T("Stripe History", "stripe"),
            'stripe_history'    => $stripe_history,
            'logs'              => $logs,
            'nb'                => $logs_count,
            'module_id'         => $this->getModuleId()
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('stripe_history'),
            $params
        );
        return $response;
    }

    /**
     * Filter
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function filter(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        $filters = $this->session->filter_stripe_history ?? new StripeHistoryList();

        if (isset($post['clear_filter'])) {
            $filters->reinit();
        } else {
            if (isset($post['nbshow']) && is_numeric($post['nbshow'])) {
                $filters->show = (int)$post['nbshow'];
            }

            if (isset($post['end_date_filter']) || isset($post['start_date_filter'])) {
                if (isset($post['start_date_filter'])) {
                    $filters->start_date_filter = $post['start_date_filter'];
                }
                if (isset($post['end_date_filter'])) {
                    $filters->end_date_filter = $post['end_date_filter'];
                }
            }

            if (isset($post['payment_filter'])) {
                $filters->payment_filter = $post['payment_filter'];
            }

            if (isset($post['payer_filter']) && $post['payer_filter'] !== '') {
                $filters->payer_filter = $post['payer_filter'];
            }

            if (isset($post['reason_filter'])) {
                $filters->reason_filter = $post['reason_filter'];
            }

            if (isset($post['method_filter'])) {
                $filters->method_filter = $post['method_filter'];
            }
        }

        $this->session->filter_stripe_history = $filters;

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('stripe_history'));
    }

    /**
     * Preferences
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function preferences(Request $request, Response $response): Response
    {
        if ($this->session->stripe !== null) {
            $stripe = $this->session->stripe;
            $this->session->stripe = null;
        } else {
            $stripe = new Stripe($this->zdb, $this->preferences);
        }

        $current_country = $stripe->getCountry();
        $amounts = $stripe->getAllAmounts();
        $countries = $stripe->getAllCountries();
        $currencies = $stripe->getAllCurrencies($current_country);

        $params = [
            'page_title'    => _T('Stripe Settings', 'stripe'),
            'stripe'        => $stripe,
            'webhook_url'   => $this->preferences->getURL() . $this->routeparser->urlFor('stripe_webhook'),
            'amounts'       => $amounts,
            'countries'     => $countries,
            'currencies'    => $currencies,
            'documentation' => 'https://galette-plugins.github.io/plugin-stripe/documentation.html#settings'
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('stripe_preferences'),
            $params
        );
        return $response;
    }

    /**
     * Store Preferences
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function storePreferences(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $stripe = new Stripe($this->zdb, $this->preferences);

        if (isset($post['stripe_pubkey']) && $this->login->isAdmin()) {
            $stripe->setPubKey($post['stripe_pubkey']);
        }
        if (isset($post['stripe_privkey']) && $this->login->isAdmin()) {
            $stripe->setPrivKey($post['stripe_privkey']);
        }
        if (isset($post['stripe_webhook_secret']) && $this->login->isAdmin()) {
            $stripe->setWebhookSecret($post['stripe_webhook_secret']);
        }
        if (isset($post['stripe_country']) && $this->login->isAdmin()) {
            $stripe->setCountry($post['stripe_country']);
        }
        if (isset($post['stripe_currency']) && $this->login->isAdmin()) {
            $stripe->setCurrency($post['stripe_currency']);
        }
        if (isset($post['inactives'])) {
            $stripe->setInactives($post['inactives']);
        } else {
            $stripe->unsetInactives();
        }

        if ($stripe->getCurrency() === '') {
            $this->flash->addMessage(
                'error_detected',
                _T('You have to select a currency.', 'stripe')
            );
        } else {
            if ($stripe->store()) {
                $this->flash->addMessage(
                    'success_detected',
                    _T('Stripe settings have been saved.', 'stripe')
                );
            } else {
                $this->session->stripe = $stripe;
                $this->flash->addMessage(
                    'error_detected',
                    _T('An error occurred saving stripe settings.', 'stripe')
                );
            }
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('stripe_preferences'));
    }

    /**
     * Ajax currencies list refresh
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function refreshCurrencies(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $stripe = new Stripe($this->zdb, $this->preferences);
        $returnedCurrencies = [];
        try {
            $allCurrencies = $stripe->getAllCurrencies($post['country']);
            foreach ($allCurrencies as $key => $value) {
                $returnedCurrencies[] = [
                    'value' => $key,
                    'name' => $value
                ];
            }
        } catch (Throwable $e) {
            Analog::log(
                'An error occurred while retrieving updated currencies list: ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
        return $this->withJson($response, $returnedCurrencies); //@phpstan-ignore-line
    }

    /**
     * Webhook
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function webhook(Request $request, Response $response): Response
    {
        $body = $request->getBody();
        $post = json_decode($body->getContents(), true);
        $stripe = new Stripe($this->zdb, $this->preferences);

        // Check webhook signature
        $stripe_signatures = $request->getHeader('HTTP_STRIPE_SIGNATURE');
        if (!empty($stripe_signatures)) {
            try {
                \Stripe\Webhook::constructEvent((string)$body, $stripe_signatures[0], $stripe->getWebhookSecret());
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Analog::log(
                    'Error verifying webhook signature: ' . $e->getMessage(),
                    Analog::ERROR
                );
                return $response->withStatus(403);
            }
        } else {
            Analog::log(
                'Request to the webhook is not signed!',
                Analog::ERROR
            );
            return $response->withStatus(403);
        }

        Analog::log(
            "Stripe webhook request: " . var_export($post, true),
            Analog::DEBUG
        );

        if (
            isset($post['type'])
            && $post['type'] == 'payment_intent.succeeded'
            && $post['data']['object']['metadata']['item_id']
        ) {
            $sh = new StripeHistory($this->zdb, $this->login, $this->preferences);
            $sh->add($post);

            // are we working on a real contribution?
            $real_contrib = false;
            if (array_key_exists('member_id', $post['data']['object']['metadata'])) {
                $real_contrib = true;
            }

            if ($sh->isProcessed($post)) {
                Analog::log(
                    'A Stripe payment intent has been received, but it has already been processed!',
                    Analog::WARNING
                );
                $sh->setState(StripeHistory::STATE_ALREADYDONE);
            } else {
                /**
                 * Let's add the relevant contribution.
                 * We will use the following parameters:
                 * - $post['data']['object']['amount']: the amount
                 * - $post['data']['object']['metadata']['member_id']: member id
                 * - $post['data']['object']['metadata']['item_id']: contribution type id
                 *
                 * If no member id is provided, we only send to post contribution
                 * script, Galette does not handle anonymous contributions
                 */
                $amount = $post['data']['object']['amount'];
                $member_id = array_key_exists('member_id', $post['data']['object']['metadata']) ? $post['data']['object']['metadata']['member_id'] : '';
                $contrib_args = [
                    'type'          => $post['data']['object']['metadata']['item_id'],
                    'adh'           => $member_id,
                    'payment_type'  => PaymentType::STRIPE
                ];
                $check_contrib_args = [
                    ContributionsTypes::PK  => $post['data']['object']['metadata']['item_id'],
                    Adherent::PK            => $member_id,
                    'type_paiement_cotis'   => PaymentType::STRIPE,
                    'montant_cotis'         => $stripe->isZeroDecimal($stripe->getCurrency()) ? $amount : $amount / 100,
                ];
                if ($this->preferences->pref_membership_ext != '') { //@phpstan-ignore-line
                    $contrib_args['ext'] = $this->preferences->pref_membership_ext;
                }
                $contrib = new Contribution($this->zdb, $this->login, $contrib_args);

                // all goes well, we can proceed
                if ($real_contrib) {
                    // Check contribution to set $contrib->errors to [] and handle contribution overlap
                    $valid = $contrib->setNoCheckLogin()->check($check_contrib_args, [], []);
                    if ($valid !== true) {
                        Analog::log(
                            'Checking values before storing a new contribution from a Stripe payment failed:'
                            . implode("\n   ", $valid),
                            Analog::ERROR
                        );
                        $sh->setState(StripeHistory::STATE_ERROR);
                        return $response->withStatus(500, 'Internal error');
                    }

                    if ($contrib->store()) {
                        // contribution has been stored :)
                        Analog::log(
                            'A Stripe payment has been successfully stored as a contribution',
                            Analog::DEBUG
                        );
                        $sh->setState(StripeHistory::STATE_PROCESSED);
                    } else {
                        // something went wrong :'(
                        Analog::log(
                            'An error occurred while storing a new contribution from a Stripe payment',
                            Analog::ERROR
                        );
                        $sh->setState(StripeHistory::STATE_ERROR);
                        return $response->withStatus(500, 'Internal error');
                    }
                    return $response->withStatus(200);
                } else {
                    Analog::log(
                        'A Stripe payment has been successfully stored as a public donation',
                        Analog::DEBUG
                    );
                    $sh->setState(StripeHistory::STATE_PUBLIC);
                }
            }
            return $response->withStatus(200);
        } else {
            // Ignore all other stripe events.
            Analog::log(
                'Stripe event ignored. Only succeeded payments events are processed.',
                Analog::DEBUG
            );
            return $response->withStatus(200);
        }
    }

    /**
     * Success URL
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function successUrl(Request $request, Response $response): Response
    {
        $query_params = $request->getQueryParams();
        $session_id = $query_params['session_id'] ?? null;
        $details = [];

        if (!$session_id) {
            throw new HttpNotFoundException($request);
        }

        try {
            $stripe = new Stripe($this->zdb, $this->preferences);
            $stripe_client = new StripeClient($stripe->getPrivKey());
            $checkout_session = $stripe_client->checkout->sessions->retrieve($session_id, []);
            $payment_intent = $stripe_client->paymentIntents->retrieve($checkout_session->payment_intent, []);
            $metadata = $payment_intent->metadata->toArray();

            $details = [
                'amount' => $stripe->isZeroDecimal($stripe->getCurrency()) ? $payment_intent->amount_received : $payment_intent->amount_received / 100,
                'date' => $payment_intent->created,
                'method' => $payment_intent->payment_method_types[0],
                'reason' => $metadata['item_name']
            ];

            $params = [
                'page_title' => _T('Payment done', 'stripe'),
                'stripe' => $stripe,
                'details' => $details
            ];

            // display page
            $this->view->render(
                $response,
                $this->getTemplate('stripe_success'),
                $params
            );
            return $response;
        } catch (Throwable $e) {
            Analog::log(
                'Stripe payment details could not be retrieved and displayed on the confirmation page: '
                . $e->getMessage(),
                Analog::WARNING
            );
            throw new HttpForbiddenException($request);
        }
    }

    /**
     * Cancel URL
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     */
    public function cancelUrl(Request $request, Response $response): Response
    {
        $this->flash->addMessage(
            'warning_detected',
            _T('Your payment has been aborted!', 'stripe')
        );
        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor('stripe_form'));
    }
}
