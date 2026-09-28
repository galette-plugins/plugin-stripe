--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Prices are the amounts of core contributions types; keep those already set
UPDATE galette_types_cotisation t
  INNER JOIN galette_stripe_types_cotisation_prices p ON p.id_type_cotis = t.id_type_cotis
  SET t.amount = p.amount
  WHERE t.amount IS NULL AND p.amount IS NOT NULL;
DROP TABLE IF EXISTS galette_stripe_types_cotisation_prices;

ALTER TABLE galette_stripe_history
  CHANGE COLUMN comment comments varchar(255),
  CHANGE COLUMN metadata request text;

ALTER TABLE galette_stripe_history
  ADD COLUMN payer_name varchar(255),
  ADD COLUMN member_id int(10) NOT NULL,
  ADD COLUMN method varchar(20) NOT NULL,
  ADD COLUMN receipt_url varchar(255),
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
-- CONVERT TO promotes text to mediumtext, and keeps an explicit charset on intent_id
ALTER TABLE galette_stripe_history
  MODIFY intent_id varchar(255),
  MODIFY request text;

-- Previous versions stored the serialized payment metadata only, with the member
-- as "adherent_id"; their states were 0 (public donation), 2 (done) and 3 (error)
UPDATE galette_stripe_history
SET
  state = CASE state
    WHEN 0 THEN 3
    WHEN 2 THEN 1
    WHEN 3 THEN 2
    ELSE state
  END,
  member_id = CASE
    WHEN request REGEXP '"adherent_id";s:[0-9]+:"[0-9]+"'
      THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(request, '"adherent_id";s:', -1), '"', 2), '"', -1) AS UNSIGNED)
    ELSE 0
  END;

ALTER TABLE galette_stripe_preferences
  ENGINE=InnoDB,
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
