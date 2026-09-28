<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteStripe\Controllers\tests\units;

use Analog\Analog;
use Galette\Entity\Contribution;
use Galette\Entity\ContributionsTypes;
use Galette\Tests\GaletteRoutingTestCase;
use GaletteStripe\Stripe;
use GaletteStripe\StripeHistory;
use Psr\Http\Message\ResponseInterface;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

/**
 * Stripe controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class StripeController extends GaletteRoutingTestCase
{
    protected int $seed = 20260927223512;
    protected bool $load_plugins = true;

    /**
     * Requests sent to the (fake) Stripe API
     *
     * @var array<int, array<string, mixed>>
     */
    private array $api_calls = [];

    /**
     * Paths of the (fake) Stripe API that fail
     *
     * @var array<int, string>
     */
    private array $api_down = [];

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->api_calls = [];
        $this->api_down = [];
        //never reach Stripe: answer as the API would
        ApiRequestor::setHttpClient(
            new class (
                fn(array $call) => $this->api_calls[] = $call,
                fn(string $path): bool => array_any($this->api_down, fn(string $down) => str_starts_with($path, $down))
            ) implements ClientInterface {
                /**
                 * @param \Closure(array<string, mixed>): mixed $record  Records calls
                 * @param \Closure(string): bool               $is_down Is API path failing
                 */
                public function __construct(
                    private readonly \Closure $record,
                    private readonly \Closure $is_down
                ) {
                }

                /**
                 * @param mixed $method            HTTP method
                 * @param mixed $absUrl            URL
                 * @param mixed $headers           Headers
                 * @param mixed $params            Parameters
                 * @param mixed $hasFile           Has file
                 * @param mixed $apiMode           API mode
                 * @param mixed $maxNetworkRetries Retries
                 *
                 * @return array{0: string, 1: int, 2: array<string, string>}
                 */
                public function request(
                    mixed $method,
                    mixed $absUrl,
                    mixed $headers,
                    mixed $params,
                    mixed $hasFile,
                    mixed $apiMode = 'v1',
                    mixed $maxNetworkRetries = null
                ): array {
                    ($this->record)(['method' => $method, 'url' => $absUrl, 'params' => $params]);
                    $path = (string)parse_url($absUrl, PHP_URL_PATH);
                    if (($this->is_down)($path)) {
                        return [json_encode(['error' => ['message' => 'Service unavailable']]), 503, []];
                    }
                    $body = match (true) {
                        str_starts_with($path, '/v1/payment_methods/') => [
                            'id' => 'pm_test',
                            'object' => 'payment_method',
                            'type' => 'card',
                            'billing_details' => ['name' => 'Jane Doe']
                        ],
                        str_starts_with($path, '/v1/charges/') => [
                            'id' => 'ch_test',
                            'object' => 'charge',
                            'receipt_url' => 'https://pay.stripe.com/receipts/test'
                        ],
                        str_starts_with($path, '/v1/country_specs/') => [
                            'id' => 'FR',
                            'object' => 'country_spec',
                            'supported_payment_currencies' => ['eur', 'usd']
                        ],
                        $path === '/v1/checkout/sessions' => [
                            'id' => 'cs_test',
                            'object' => 'checkout.session',
                            'url' => 'https://checkout.stripe.com/c/pay/cs_test'
                        ],
                        default => null
                    };
                    if ($body === null) {
                        return [json_encode(['error' => ['message' => 'Unexpected ' . $path]]), 404, []];
                    }
                    return [json_encode($body), 200, []];
                }
            }
        );
    }

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Set a plugin preference
     *
     * @param string $name  Preference name
     * @param string $value Preference value
     */
    private function setStripePref(string $name, string $value): void
    {
        $update = $this->zdb->update(STRIPE_PREFIX . Stripe::TABLE);
        $update->set(['val_pref' => $value])->where(['nom_pref' => $name]);
        $this->zdb->execute($update);
    }

    /**
     * Log in given member
     *
     * @param array<string,mixed> $mdata Member data
     */
    private function logMember(array $mdata): void
    {
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
    }

    /**
     * Build a payment_intent.succeeded event
     *
     * @param int $id_adh  Member ID
     * @param int $id_type Contribution type ID
     * @param int $amount  Amount, in cents
     *
     * @return array<string, mixed>
     */
    private function getSucceededEvent(int $id_adh, int $id_type, int $amount): array
    {
        return [
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test',
                    'object' => 'payment_intent',
                    'amount' => $amount,
                    'payment_method' => 'pm_test',
                    'latest_charge' => 'ch_test',
                    'metadata' => [
                        'member_id' => (string)$id_adh,
                        'item_id' => (string)$id_type,
                        'item_name' => 'donation in money'
                    ]
                ]
            ]
        ];
    }

    /**
     * Post an event to the webhook, signed with given secret
     *
     * @param array<string, mixed> $event  Event
     * @param string               $secret Secret used to sign
     */
    private function postWebhook(array $event, string $secret): ResponseInterface
    {
        return $this->postRawWebhook(json_encode($event, JSON_THROW_ON_ERROR), $secret);
    }

    /**
     * Post a raw payload to the webhook
     *
     * @param string $payload Payload
     * @param string $secret  Secret used to sign
     */
    private function postRawWebhook(string $payload, string $secret): ResponseInterface
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $sfactory = new \Slim\Psr7\Factory\StreamFactory();
        $request = $this->createRequest('stripe_webhook', [], 'POST', 'application/json')
            ->withHeader('Stripe-Signature', 't=' . $timestamp . ',v1=' . $signature)
            ->withBody($sfactory->createStream($payload));
        return $this->app->handle($request);
    }

    /**
     * Count contributions of a member
     *
     * @param int $id_adh Member ID
     */
    private function countContributions(int $id_adh): int
    {
        $select = $this->zdb->select(Contribution::TABLE);
        $select->where([\Galette\Entity\Adherent::PK => $id_adh]);
        return $this->zdb->execute($select)->count();
    }

    /**
     * Count Stripe history entries
     */
    private function countHistory(): int
    {
        return $this->zdb->execute($this->zdb->select(STRIPE_PREFIX . StripeHistory::TABLE))->count();
    }

    /**
     * Set the amount of a contribution type
     *
     * @param int   $id_type Contribution type ID
     * @param float $amount  Amount
     */
    private function setTypeAmount(int $id_type, float $amount): void
    {
        $update = $this->zdb->update(ContributionsTypes::TABLE);
        $update->set(['amount' => $amount])->where([ContributionsTypes::PK => $id_type]);
        $this->zdb->execute($update);
    }

    /**
     * Post the payment form
     *
     * @param array<string, mixed> $data Posted data
     */
    private function postCheckout(array $data): ResponseInterface
    {
        $request = $this->createRequest('stripe_formCheckout', [], 'POST')->withParsedBody($data);
        return $this->app->handle($request);
    }

    /**
     * Assert payment form has been refused with given message
     *
     * @param ResponseInterface $test_response Response
     * @param string            $message       Expected error message
     */
    private function expectCheckoutRefused(ResponseInterface $test_response, string $message): void
    {
        $this->assertSame(301, $test_response->getStatusCode());
        $this->assertSame(
            [$this->routeparser->urlFor('stripe_form')],
            $test_response->getHeader('Location')
        );
        $this->expectFlashData(['error_detected' => [$message]]);
        $this->assertSame([], $this->api_calls);
    }

    /**
     * Only payment reasons proposed to the current user can be paid
     */
    public function testCheckoutRefusesUnproposedReason(): void
    {
        $this->setStripePref('stripe_privkey', 'sk_test_fake');
        //type 1 (annual fee) is proposed, type 7 is inactive by default
        $this->setTypeAmount(1, 20);
        $this->setTypeAmount(7, 20);
        $this->getMemberOne();
        $this->logMember($this->dataAdherentOne());

        $this->expectCheckoutRefused(
            $this->postCheckout(['item_id' => '7', 'amount' => '1']),
            _T("You have to select an option.", "stripe")
        );
        $this->expectCheckoutRefused(
            $this->postCheckout(['item_id' => '9999', 'amount' => '1']),
            _T("You have to select an option.", "stripe")
        );
        $this->expectCheckoutRefused(
            $this->postCheckout(['amount' => '20']),
            _T("You have to select an option.", "stripe")
        );

        //membership fees are not proposed to visitors
        $this->login->logout();
        $this->expectCheckoutRefused(
            $this->postCheckout(['item_id' => '1', 'amount' => '20']),
            _T("You have to select an option.", "stripe")
        );
        $this->expectNoLogEntry();
    }

    /**
     * Amount must be a number, at least the one of the payment reason
     */
    public function testCheckoutChecksAmount(): void
    {
        $this->setStripePref('stripe_privkey', 'sk_test_fake');
        $this->setTypeAmount(5, 10);
        $member = $this->getMemberOne();
        $this->logMember($this->dataAdherentOne());

        foreach (['', 'abc', '12abc', ['12']] as $amount) {
            $this->expectCheckoutRefused(
                $this->postCheckout(['item_id' => '5', 'amount' => $amount]),
                _T("Please enter an amount.", "stripe")
            );
        }
        foreach (['9.99', '-20', '0'] as $amount) {
            $this->expectCheckoutRefused(
                $this->postCheckout(['item_id' => '5', 'amount' => $amount]),
                _T(
                    "The amount you've entered is lower than the minimum amount for the selected option. Please choose another option or change the amount.",
                    "stripe"
                )
            );
        }

        //decimal comma is accepted
        $test_response = $this->postCheckout(['item_id' => '5', 'amount' => '12,50']);
        $this->assertSame(301, $test_response->getStatusCode());
        $this->assertSame(['https://checkout.stripe.com/c/pay/cs_test'], $test_response->getHeader('Location'));
        $this->expectNoLogEntry();

        $this->assertCount(1, $this->api_calls);
        $params = $this->api_calls[0]['params'];
        $this->assertSame(1250, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(
            ['member_id' => $member->id, 'item_id' => 5, 'item_name' => 'donation in money'],
            $params['payment_intent_data']['metadata']
        );

        //amount is rounded to the cent, not truncated
        $this->api_calls = [];
        $test_response = $this->postCheckout(['item_id' => '5', 'amount' => '19.99']);
        $this->assertSame(301, $test_response->getStatusCode());
        $this->assertSame(1999, $this->api_calls[0]['params']['line_items'][0]['price_data']['unit_amount']);
    }

    /**
     * Get a plugin preference, as stored
     *
     * @param string $name Preference name
     */
    private function getStripePref(string $name): string
    {
        $select = $this->zdb->select(STRIPE_PREFIX . Stripe::TABLE);
        $select->where(['nom_pref' => $name]);
        return $this->zdb->execute($select)->current()->val_pref;
    }

    /**
     * Post preferences
     *
     * @param array<string, string> $data Posted data
     */
    private function postPreferences(array $data): void
    {
        $request = $this->createRequest('store_stripe_preferences', [], 'POST')->withParsedBody(
            $data + ['stripe_country' => 'FR', 'stripe_currency' => 'eur']
        );
        $test_response = $this->app->handle($request);
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => [_T('Stripe settings have been saved.', 'stripe')]]);
    }

    /**
     * Secrets are never sent back to the browser, and kept when left empty
     */
    public function testPreferencesSecrets(): void
    {
        $this->setStripePref('stripe_pubkey', 'pk_test_public');
        $this->setStripePref('stripe_privkey', 'sk_test_secret');
        $this->setStripePref('stripe_webhook_secret', 'whsec_secret');
        $this->logSuperAdmin();

        $test_response = $this->app->handle($this->createRequest('stripe_preferences'));
        $this->assertSame(200, $test_response->getStatusCode());
        $body = (string)$test_response->getBody();
        $this->assertStringContainsString('pk_test_public', $body);
        $this->assertStringNotContainsString('sk_test_secret', $body);
        $this->assertStringNotContainsString('whsec_secret', $body);
        $this->assertMatchesRegularExpression('/<input\s+type="password"\s+name="stripe_privkey"/', $body);
        $this->assertMatchesRegularExpression('/<input\s+type="password"\s+name="stripe_webhook_secret"/', $body);
        $this->expectNoLogEntry();

        //empty secrets keep the stored ones
        $this->postPreferences([
            'stripe_pubkey' => 'pk_test_public',
            'stripe_privkey' => '',
            'stripe_webhook_secret' => ' '
        ]);
        $this->assertSame('sk_test_secret', $this->getStripePref('stripe_privkey'));
        $this->assertSame('whsec_secret', $this->getStripePref('stripe_webhook_secret'));

        $this->postPreferences([
            'stripe_pubkey' => 'pk_test_public',
            'stripe_privkey' => 'sk_test_new',
            'stripe_webhook_secret' => 'whsec_new'
        ]);
        $this->assertSame('sk_test_new', $this->getStripePref('stripe_privkey'));
        $this->assertSame('whsec_new', $this->getStripePref('stripe_webhook_secret'));
        $this->expectNoLogEntry();
    }

    /**
     * Payment is stored even without its details
     */
    public function testWebhookWithoutPaymentDetails(): void
    {
        $member = $this->getMemberOne();
        $this->setStripePref('stripe_webhook_secret', 'whsec_test');
        $this->setStripePref('stripe_privkey', 'sk_test_fake');

        $event = $this->getSucceededEvent($member->id, 5, 1250);
        $event['data']['object']['payment_method'] = null;
        $event['data']['object']['latest_charge'] = null;
        $event['data']['object']['payment_method_types'] = ['sepa_debit'];
        $test_response = $this->postWebhook($event, 'whsec_test');

        $this->assertSame(200, $test_response->getStatusCode());
        $this->expectNoLogEntry();
        $this->assertSame([], $this->api_calls);
        $this->assertSame(1, $this->countContributions($member->id));
        $history = $this->zdb->execute($this->zdb->select(STRIPE_PREFIX . StripeHistory::TABLE))->current();
        $this->assertNull($history->payer_name);
        $this->assertNull($history->receipt_url);
        $this->assertSame('sepa_debit', $history->method);

        //details cannot be retrieved
        $this->api_down = ['/v1/payment_methods/'];
        $event = $this->getSucceededEvent($member->id, 5, 1250);
        $event['data']['object']['id'] = 'pi_other';
        $test_response = $this->postWebhook($event, 'whsec_test');

        $this->assertSame(200, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::WARNING, 'Unable to get details of Stripe payment pi_other');
        $this->expectNoLogEntry();
        $this->assertSame(2, $this->countContributions($member->id));
    }

    /**
     * No contribution is stored when the payment cannot be added to history
     */
    public function testWebhookHistoryFailure(): void
    {
        $member = $this->getMemberOne();
        $this->setStripePref('stripe_webhook_secret', 'whsec_test');
        $this->setStripePref('stripe_privkey', 'sk_test_fake');

        //too long for its column
        $event = $this->getSucceededEvent($member->id, 5, 1250);
        $event['data']['object']['id'] = str_repeat('pi_', 100);
        //on PostgreSQL, an error aborts the whole test transaction
        $savepoint = $this->zdb->isPostgres();
        if ($savepoint) {
            $this->zdb->db->query('SAVEPOINT history_failure', \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        }
        $test_response = $this->postWebhook($event, 'whsec_test');
        if ($savepoint) {
            $this->zdb->db->query('ROLLBACK TO SAVEPOINT history_failure', \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        }

        $this->assertSame(500, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::ERROR, 'Query error');
        $this->expectLogEntry(Analog::ERROR, 'An error occurred trying to add log entry.');
        $this->expectNoLogEntry();
        $this->assertSame(0, $this->countHistory());
        $this->assertSame(0, $this->countContributions($member->id));
    }

    /**
     * Webhook refuses notifications while no secret is configured
     */
    public function testWebhookRefusedWithoutSecret(): void
    {
        $member = $this->getMemberOne();
        $this->setStripePref('stripe_webhook_secret', '');
        $this->setStripePref('stripe_privkey', 'sk_test_fake');

        $test_response = $this->postWebhook(
            $this->getSucceededEvent($member->id, 5, 1000),
            ''
        );

        $this->assertSame(400, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::ERROR, 'No webhook secret value was provided');
        $this->expectNoLogEntry();
        $this->assertSame(0, $this->countHistory());
        $this->assertSame(0, $this->countContributions($member->id));
        $this->assertSame([], $this->api_calls);
    }

    /**
     * Webhook stores the contribution of a notification signed with the configured secret
     */
    public function testWebhookSignedWithSecret(): void
    {
        $member = $this->getMemberOne();
        $this->setStripePref('stripe_webhook_secret', 'whsec_test');
        $this->setStripePref('stripe_privkey', 'sk_test_fake');

        //type 5 is the default "donation in money"
        $test_response = $this->postWebhook($this->getSucceededEvent($member->id, 5, 1250), 'whsec_test');

        $this->assertSame(200, $test_response->getStatusCode());
        $this->expectNoLogEntry();
        $this->assertSame(1, $this->countHistory());
        $this->assertSame(1, $this->countContributions($member->id));

        $history = $this->zdb->execute($this->zdb->select(STRIPE_PREFIX . StripeHistory::TABLE))->current();
        $this->assertSame(StripeHistory::STATE_PROCESSED, (int)$history->state);
        $this->assertSame('Jane Doe', $history->payer_name);

        //Stripe sends notifications again until it gets an answer: store only once
        $test_response = $this->postWebhook($this->getSucceededEvent($member->id, 5, 1250), 'whsec_test');
        $this->assertSame(200, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::WARNING, 'has already been processed');
        $this->expectNoLogEntry();
        $this->assertSame(2, $this->countHistory());
        $this->assertSame(1, $this->countContributions($member->id));
        $select = $this->zdb->select(STRIPE_PREFIX . StripeHistory::TABLE);
        $select->order(StripeHistory::PK . ' DESC');
        $this->assertSame(StripeHistory::STATE_ALREADYDONE, (int)$this->zdb->execute($select)->current()->state);

        //a notification signed with another secret is refused
        $test_response = $this->postWebhook($this->getSucceededEvent($member->id, 5, 1250), 'whsec_other');
        $this->assertSame(400, $test_response->getStatusCode());
        $this->expectLogEntry(
            Analog::ERROR,
            'No signatures found matching the expected signature for payload'
        );
        $this->expectNoLogEntry();
        $this->assertSame(1, $this->countContributions($member->id));
    }

    /**
     * Webhook refuses unsigned requests and invalid payloads
     */
    public function testWebhookRefusesInvalidRequests(): void
    {
        $member = $this->getMemberOne();
        $this->setStripePref('stripe_webhook_secret', 'whsec_test');
        $this->setStripePref('stripe_privkey', 'sk_test_fake');

        $sfactory = new \Slim\Psr7\Factory\StreamFactory();
        $request = $this->createRequest('stripe_webhook', [], 'POST', 'application/json')
            ->withBody($sfactory->createStream(
                json_encode($this->getSucceededEvent($member->id, 5, 1250), JSON_THROW_ON_ERROR)
            ));
        $test_response = $this->app->handle($request);
        $this->assertSame(400, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::ERROR, 'Request to the webhook is not signed!');
        $this->expectNoLogEntry();

        //correctly signed, but not JSON
        $test_response = $this->postRawWebhook('not a json payload', 'whsec_test');
        $this->assertSame(400, $test_response->getStatusCode());
        $this->expectLogEntry(Analog::ERROR, 'Invalid webhook payload');
        $this->expectNoLogEntry();

        $this->assertSame(0, $this->countHistory());
        $this->assertSame(0, $this->countContributions($member->id));
        $this->assertSame([], $this->api_calls);
    }
}
