--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE galette_stripe_types_cotisation_prices;

ALTER TABLE galette_stripe_history
  CHANGE COLUMN comment comments varchar(255),
  CHANGE COLUMN metadata request text;

ALTER TABLE galette_stripe_history
  MODIFY intent_id VARCHAR(255) COLLATE utf8mb4_unicode_520_ci,
  MODIFY comments VARCHAR(255) COLLATE utf8mb4_unicode_520_ci,
  MODIFY request TEXT COLLATE utf8mb4_unicode_520_ci,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

ALTER TABLE galette_stripe_history
  ADD COLUMN payer_name VARCHAR(255),
  ADD COLUMN member_id INT(10) NOT NULL,
  ADD COLUMN method VARCHAR(20) NOT NULL,
  ADD COLUMN receipt_url VARCHAR(255);

UPDATE galette_stripe_history
SET
  state = CASE
    WHEN state = 0 THEN 3
    ELSE state
  END,
  member_id = COALESCE(
    CAST(
      JSON_UNQUOTE(JSON_EXTRACT(request, '$.data.object.metadata.member_id'))
      AS UNSIGNED
    ),
    0
  ),
  method = JSON_UNQUOTE(JSON_EXTRACT(request, '$.data.object.payment_method_types[0]')),
  receipt_url = JSON_UNQUOTE(JSON_EXTRACT(request, '$.receipt_url'));

ALTER TABLE galette_stripe_preferences
  MODIFY nom_pref VARCHAR(100) NOT NULL DEFAULT '',
  MODIFY val_pref VARCHAR(200) NOT NULL DEFAULT '',
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
