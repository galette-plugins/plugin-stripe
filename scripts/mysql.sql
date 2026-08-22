--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP TABLE IF EXISTS galette_stripe_history;
CREATE TABLE galette_stripe_history (
  id_stripe int(11) NOT NULL auto_increment,
  history_date datetime NOT NULL,
  intent_id varchar(255),
  amount double NOT NULL,
  comments varchar(255),
  request text,
  state tinyint(4) NOT NULL DEFAULT 0,
  payer_name varchar(255),
  member_id int(10) NOT NULL,
  method varchar(20) NOT NULL,
  receipt_url varchar(255),
  PRIMARY KEY (`id_stripe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Table structure for table `galette_stripe_preferences`
--
DROP TABLE IF EXISTS galette_stripe_preferences;
CREATE TABLE galette_stripe_preferences (
  id_pref int(10) unsigned NOT NULL auto_increment,
  nom_pref varchar(100) NOT NULL default '',
  val_pref varchar(200) NOT NULL default '',
  PRIMARY KEY (id_pref),
  UNIQUE KEY (nom_pref)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_pubkey', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_privkey', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_webhook_secret', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_inactives', '4,6,7');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_country', 'FR');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_currency', 'eur');
