--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Keep time of payments, and do not store amounts as floating point numbers
ALTER TABLE galette_stripe_history
  ALTER COLUMN history_date TYPE timestamp,
  ALTER COLUMN amount TYPE numeric(15,2);
