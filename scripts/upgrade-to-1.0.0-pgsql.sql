--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Prices are the amounts of core contributions types; keep those already set
UPDATE galette_types_cotisation t
  SET amount = p.amount
  FROM galette_stripe_types_cotisation_prices p
  WHERE p.id_type_cotis = t.id_type_cotis AND t.amount IS NULL AND p.amount IS NOT NULL;
DROP TABLE IF EXISTS galette_stripe_types_cotisation_prices;

ALTER TABLE galette_stripe_history RENAME COLUMN metadata TO request;

ALTER TABLE galette_stripe_history
  ADD COLUMN payer_name character varying(255),
  ADD COLUMN member_id integer DEFAULT 0 NOT NULL,
  ADD COLUMN method character varying(20) DEFAULT '' NOT NULL,
  ADD COLUMN receipt_url character varying(255);
ALTER TABLE galette_stripe_history
  ALTER COLUMN member_id DROP DEFAULT,
  ALTER COLUMN method DROP DEFAULT;

-- Keep time of payments, and do not store amounts as floating point numbers
ALTER TABLE galette_stripe_history
  ALTER COLUMN history_date TYPE timestamp,
  ALTER COLUMN amount TYPE numeric(15,2);

-- Previous versions stored the serialized payment metadata only, with the member
-- as "adherent_id", and card was the only payment method; their states were
-- 0 (public donation), 2 (done) and 3 (error)
UPDATE galette_stripe_history
SET
  state = CASE state
    WHEN 0 THEN 3
    WHEN 2 THEN 1
    WHEN 3 THEN 2
    ELSE state
  END,
  member_id = COALESCE(substring(request from '"adherent_id";s:[0-9]+:"([0-9]+)"')::integer, 0),
  method = 'card';

UPDATE galette_stripe_preferences SET val_pref = UPPER(val_pref) WHERE nom_pref = 'stripe_country';
