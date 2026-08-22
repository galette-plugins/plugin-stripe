--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE galette_stripe_types_cotisation_prices;

ALTER TABLE galette_stripe_history
  RENAME COLUMN metadata TO request,
  ADD COLUMN payer_name character varying(255),
  ADD COLUMN member_id integer,
  ADD COLUMN method character varying(20),
  ADD COLUMN receipt_url character varying(255);

UPDATE galette_stripe_history
SET
  state = CASE
    WHEN state = 0 THEN 3
    ELSE state
  END,
  member_id = COALESCE(
    (request #>> '{data,object,metadata,member_id}')::int,
    0
  ),
  method = request #>> '{data,object,payment_method_types,0}',
  receipt_url = request #>> '{receipt_url}';

ALTER TABLE galette_stripe_history
  ALTER COLUMN member_id SET NOT NULL,
  ALTER COLUMN method SET NOT NULL;
