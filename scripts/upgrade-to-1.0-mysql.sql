--
-- This file is part of Galette Stripe plugin (https://galette-community.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE galette_stripe_types_cotisation_prices;
ALTER TABLE galette_stripe_history CHANGE COLUMN comment comments varchar(255);
ALTER TABLE galette_stripe_history CHANGE COLUMN metadata request text;
ALTER TABLE galette_stripe_history ADD COLUMN payer_name varchar(255);
UPDATE galette_stripe_history SET state = 3 WHERE state = 0;
