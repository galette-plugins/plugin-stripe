--
-- This file is part of Galette Stripe plugin (https://galette-plugins.github.io/plugin-stripe).
-- SPDX-FileCopyrightText: Copyright © 2021-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP SEQUENCE IF EXISTS galette_stripe_history_id_seq;
CREATE SEQUENCE galette_stripe_history_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 2147483647
  MINVALUE 1
  CACHE 1;

DROP TABLE IF EXISTS galette_stripe_history;
CREATE TABLE galette_stripe_history (
  id_stripe integer DEFAULT nextval('galette_stripe_history_id_seq'::text) NOT NULL,
  history_date date NOT NULL,
  intent_id character varying(255),
  amount real NOT NULL,
  comments character varying(255),
  request text,
  state smallint DEFAULT 0 NOT NULL,
  payer_name character varying(255),
  member_id integer NOT NULL,
  method character varying(20) NOT NULL,
  receipt_url character varying(255),
  PRIMARY KEY (id_stripe)
);

--
-- Table structure for table `galette_stripe_preferences`
--
DROP SEQUENCE IF EXISTS galette_stripe_preferences_id_seq;
CREATE SEQUENCE galette_stripe_preferences_id_seq
  START 1
  INCREMENT 1
  MAXVALUE 2147483647
  MINVALUE 1
  CACHE 1;

DROP TABLE IF EXISTS galette_stripe_preferences;
CREATE TABLE galette_stripe_preferences (
  id_pref integer DEFAULT nextval('galette_stripe_preferences_id_seq'::text) NOT NULL,
  nom_pref character varying(100) NOT NULL default '',
  val_pref character varying(200) NOT NULL default '',
  PRIMARY KEY (id_pref)
);

CREATE UNIQUE INDEX galette_stripe_preferences_unique_idx ON galette_stripe_preferences (nom_pref);

INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_pubkey', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_privkey', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_webhook_secret', '');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_inactives', '4,6,7');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_country', 'FR');
INSERT INTO galette_stripe_preferences (nom_pref, val_pref) VALUES ('stripe_currency', 'eur');
