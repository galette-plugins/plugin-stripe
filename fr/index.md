---
title: Galette Stripe
description: Plugin pour gérer les paiements de cotisations et de dons via Stripe
---

Ce plugin fournit :

* un formulaire de paiement,
* un historique des paiements,
* la création automatique de contributions dans Galette une fois les paiements
  validés.

> **Warning** — Ce plugin nécessite actuellement **la version nightly de
> Galette**, donc **il n'est pas recommandé de l'utiliser en production pour le
> moment**.

![Formulaire de paiement visible par les utilisateurs non
connectés](images/form_public.jpg)

> **Note** — Pour utiliser ce plugin, votre instance de Galette doit être
> accessible publiquement et servie en https.

## Installation

Tout d'abord, téléchargez le plugin :

[![Obtenir la dernière version du plugin Stripe
!](https://img.shields.io/badge/1.0.0-Stripe-ffb619?style=for-the-badge&logo=php&logoColor=white&label=1.0.0-beta1&color=ffb619)](https://github.com/galette-plugins/plugin-stripe/releases/tag/1.0.0-beta1)
[![Obtenir la nightly du plugin Stripe
!](https://img.shields.io/badge/Nightly-Stripe-ffb619?style=for-the-badge&logo=php&logoColor=white&label=Nightly&color=ffb619)](https://galette.eu/download/plugins/galette-plugin-stripe-dev.tar.bz2)

Décompressez l'archive téléchargée dans le répertoire `plugins` de Galette. Par
exemple, sous linux (en remplaçant *{url}* et *{version}* par les valeurs
correspondantes) :

```
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-stripe-{version}.tar.bz2
```

## Initialisation de la base de données

Pour fonctionner, ce plugin requiert des tables dans la base de données.
Référez-vous [à l'interface de gestion des plugins de
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Et c'est tout, le plugin *Stripe* est installé. :)

## Utilisation

Lorsque le plugin est installé, un groupe Stripe est ajouté au menu de Galette
lorsqu'un utilisateur est connecté, permettant aux administrateurs et membres du
bureau de définir les préférences du plugin et de voir l'historique des
paiements.

![Menu du plugin](images/galette_menu.jpg)

Le formulaire de paiement est accessible depuis les pages publiques de Galette.

Seuls les utilisateurs connectés à leur compte peuvent payer des contributions
avec extension d'adhésion (ou cotisations).

![Formulaire de paiement visible par les utilisateurs non
connectés](images/form.jpg)

Les visiteurs standards (les utilisateurs non connectés à leur compte) ne
peuvent payer que des contributions sans prolongation d'adhésion (ou dons). Dans
ce cas, aucune contribution n'est créée automatiquement dans Galette, le
paiement n'apparaît que dans l'historique de paiement du plugin avec la valeur
“Aucun” entrée dans la colonne “Adhérent”.

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
* **Types de contribution** : dans ce tableau vous pouvez désactiver les [types
  de contribution configurés dans
  Galette](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  que vous ne souhaitez pas voir proposés comme motif de paiement sur le
  formulaire de paiement en ligne.

  *Les types de contribution dont le montant est nul, ou dont le montant n'est
  pas configuré, ne seront pas proposés comme motifs de paiement sur le
  formulaire, même si ceux-ci ne sont pas marqués comme inactifs dans le
  tableau.*

### Note sur le mode bac à sable

![Mode bac à sable Stripe](images/stripe_menu_sandbox_mode.jpg)

Il est recommandé de tester les fonctionnalités du plugin en mode bac à sable.
Pour savoir comment mettre en place un tel environnement d'essai, veuillez vous
référer à la [documentation de Stripe](https://docs.stripe.com/sandboxes).

> **Warning** — Dans ce mode, n'utilisez pas de numéros de carte de crédit
> réels, mais seulement des cartes de test (voir la liste des cartes de test
> dans la [documentation de Stripe](https://docs.stripe.com/testing#cards))

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
