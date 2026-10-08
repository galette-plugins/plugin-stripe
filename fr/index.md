---
title: Galette Stripe
description: Plugin pour gérer les paiements de cotisations et de dons via Stripe
---

Ce plugin fournit :

* un formulaire de paiement,
* un historique des paiements,
* la création automatique de contributions dans Galette une fois les paiements
  validés.

![Payment form visible by users *not logged* into their
account](images/form_public.jpg)

> **Note** — This plugin requires your Galette instance to be publicly reachable
> and served with a valid SSL certificate.

## Installation

Tout d'abord, téléchargez le plugin :

* [Get latest Stripe
  plugin!](https://github.com/galette-plugins/plugin-stripe/releases/latest)
* [Get Stripe plugin nightly
  build!](https://github.com/galette-plugins/plugin-stripe/releases/tag/nightly)

Extract the downloaded archive into Galette `plugins` directory. For example, on
Linux (replacing *{url}* and *{version}* with the corresponding values):

```
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-stripe-{version}.tar.bz2
```

## Initialisation de la base de données

Pour fonctionner, ce plugin requiert des tables dans la base de données.
Référez-vous [à l'interface de gestion des plugins de
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

And that’s it; the *Stripe* plugin is installed. :)

## Utilisation

Once the plugin is installed, a *Stripe* group is added to the Galette menu when
a user is logged-in, allowing administrators and staff members to define the
settings of the plugin and view the payments history.

![Menu du plugin](images/galette_menu.jpg)

The payment form is available from Galette's public pages.

Only *logged-in* users can pay contributions *with membership extension* (or
membership fees).

![Payment form visible by logged-in users](images/form.jpg)

Visitors (users not *logged* into their account) can only pay contributions
*without a membership extension* (or donations). In this case, no contribution
is automatically created in Galette, the payment only appears in the plugin's
payment history with the value "None" in the "Member" column.

![Écran de l'historique des paiements](images/history.jpg)

## Préférences

![Écran des préférences](images/settings.jpg)

* **URL du point de terminaison du webhook Stripe** : URL à utiliser pour créer
  un « Webhook » dans le compte de votre association sur Stripe ([lire plus
  bas](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Évènement webhook Stripe** : nom de l'évènement à utiliser pour créer un «
  Webhook » sur le compte Stripe de votre association ([lire plus
  bas](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Clé publique Stripe** : vous trouverez cette information dans le compte
  Stripe de votre association ([lire plus bas](#get-the-api-keys)).
* **Clé secrète Stripe** : vous trouverez cette information dans le compte
  Stripe de votre association ([lire plus bas](#get-the-api-keys)).
* **Clé secrète du webhook Stripe** : vous trouverez cette informations dans les
  détails du « Webhook » que vous devez créer depuis le compte de votre
  association sur Stripe ([lire plus
  bas](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Pays de votre compte Stripe** : choisissez un pays selon les paramètres de
  votre compte Stripe ([lire plus
  bas](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Devise du paiement** : choisissez une devise selon les paramètres de votre
  compte Stripe ([lire plus
  bas](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Contribution types**: in this table, you can disable the [contribution types
  configured in
  Galette](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  that you do not want to be proposed as a payment reason on the payment form.

  *Les types de contribution dont le montant est nul, ou dont le montant n'est
  pas configuré, ne seront pas proposés comme motifs de paiement sur le
  formulaire, même si ceux-ci ne sont pas marqués comme inactifs dans le
  tableau.*

  > **Note** — A description, displayed below each payment reason proposed on
  > the payment form, can be defined from the [configuration of the
  > contributions
  > types](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  > of Galette.

> **Note** — It is possible to decide who can access the payment form in
> Galette's settings. Choose the desired option in the [public pages visibility
> parameters](https://doc.galette.eu/en/master/usermanual/preferences.html#parameters).

### Note sur le mode bac à sable

![Mode bac à sable Stripe](images/stripe_menu_sandbox_mode.jpg)

Il est recommandé de tester les fonctionnalités du plugin en mode bac à sable.
Pour savoir comment mettre en place un tel environnement d'essai, veuillez vous
référer à la [documentation de Stripe](https://docs.stripe.com/sandboxes).

> **Warning** — In this mode, never use real credit card numbers, but only test
> cards (see the list of test cards from the [Stripe
> documentation](https://docs.stripe.com/testing#cards))

## Configurez votre compte Stripe

Pour savoir comment créer un compte, veuillez vous référer à la [documentation
Stripe] (https://docs.stripe.com/get-started/account).

### Obtenez le pays et la devise définis dans les paramètres de votre compte

Le choix d'un pays et d'une devise est généralement demandé lors de la création
de votre compte. Vous pouvez trouver ces informations dans les paramètres de
votre compte :

![Menu des paramètres de Stripe](images/stripe_menu_settings.jpg)

* *Paramètres > Entreprise > Informations du compte*

![Pays défini dans les paramètres du compte](images/stripe_settings_country.jpg)

* *Paramètres > Entreprise > Comptes bancaires et devises*

![Devise définie dans les paramètres du
compte](images/stripe_settings_currency.jpg)

### Créer un Webhook et obtenir la clé secrète correspondante

Le *Webhook* requis pour le bon fonctionnement du plugin peut être créé à partir
du menu *Développeurs* (situé en bas à gauche de votre tableau de bord) :

![Webhooks dans le meu développeurs](images/stripe_developers_menu_webhooks.jpg)

L'*URL du point de terminaison* à définir dans votre webhook est indiquée dans
les préférences du plugin (exemple :
`https://YOUR_DOMAIN_NAME/plugins/stripe/webhook`).

Un seul *Évènement* doit être défini dans votre webhook. Il est également
indiqué dans les préférences du plugin ; c'est `payment_intent.succeed`.

![Webhook créé dans le compte Stripe](images/stripe_webhook_config.jpg)

Une fois créé, vous devez obtenir la *clé secrète du webhook* pour la définir
dans les paramètres du plugin. Dans la liste des webhooks, cliquez sur celui que
vous avez créé :

![Liste des Webhooks dans le compte Stripe](images/stripe_webhooks_list.jpg)

La *clé secrète du webhook* peut être copiée depuis ses détails :

![Secret Webhook](images/stripe_webhook_secret.jpg)

### Obtenir les clés d'API

Les *Clés d'API* requises pour le bon fonctionnement du plugin peuvent être
créées à partir du menu *Développeurs* (situé en bas à gauche de votre tableau
de bord) :

![Clés d'API dans le menu
développeurs](images/stripe_developers_menu_api_keys.jpg)

> **Note** — Pour réduire l'impact potentiel d'une compromission, créez une clé
> *restreinte*. Cette clé peut être créée sans personnaliser les permissions.
> Veuillez consulter la [documentation
> Stripe](https://docs.stripe.com/keys#create-restricted-api-secret-key) pour
> plus d'informations sur les clés restreintes.

![Clés d'API créés dans le compte Stripe](images/stripe_api_keys.jpg)

### Activer les moyens de paiement nécessaires

Stripe offre de nombreux moyens de paiement. Dans les paramètres de votre
compte, vous devez activer uniquement les moyens que vous souhaitez utiliser.

* *Paramètres > Paiements > Moyens de paiement*

![Les moyens de paiement définis dans les paramètres du
compte](images/stripe_settings_payment_methods.jpg)
