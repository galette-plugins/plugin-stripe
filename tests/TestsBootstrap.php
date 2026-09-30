<?php

/**
 * This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
 * SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

/**
 * Bootstrap tests file for Galette Stripe plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

define('GALETTE_PLUGINS_PATH', __DIR__ . '/../../');
$basepath = __DIR__ . '/../../../'; // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- used from Core testBootstrap

include_once __DIR__ . '/../../../../tests/TestsBootstrap.php';
require_once __DIR__ . '/../_config.inc.php';
