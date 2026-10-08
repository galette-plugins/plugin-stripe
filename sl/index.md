---
title: Galette Strip
description: Vtičnik za upravljanje članarin in plačil donacij s Stripe
---

Ta vtičnik zagotavlja:

* obrazec za plačilo,
* zgodovino plačil,
* samodejno ustvarjanje prispevkov v Galette, ko so plačila potrjena.

![Payment form visible by users *not logged* into their
account](images/form_public.jpg)

> **Note** — This plugin requires your Galette instance to be publicly reachable
> and served with a valid SSL certificate.

## Namestitev

Najprej prenesite vtičnik:

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

## Inicializacija baze podatkov

Za delovanje ta vtičnik potrebuje več tabel v bazi podatkov. Oglejte si [vmesnik
za upravljanje vtičnikov
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

And that’s it; the *Stripe* plugin is installed. :)

## Uporaba vtičnika

Once the plugin is installed, a *Stripe* group is added to the Galette menu when
a user is logged-in, allowing administrators and staff members to define the
settings of the plugin and view the payments history.

![Meni vtičnika](images/galette_menu.jpg)

The payment form is available from Galette's public pages.

Only *logged-in* users can pay contributions *with membership extension* (or
membership fees).

![Payment form visible by logged-in users](images/form.jpg)

Visitors (users not *logged* into their account) can only pay contributions
*without a membership extension* (or donations). In this case, no contribution
is automatically created in Galette, the payment only appears in the plugin's
payment history with the value "None" in the "Member" column.

![Zaslon zgodovine plačil](images/history.jpg)

## Nastavitve

![Zaslon z nastavitvami](images/settings.jpg)

* **URL končne točke webhooka Stripe**: URL za ustvarjanje “Webhooka“ v računu
  vašega združenja na Stripe ([preberite več
  spodaj](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Dogodek webhook Stripe**: ime dogodka za ustvarjanje “Webhooka“ v računu
  vašega združenja na Stripe ([preberite več
  spodaj](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Javni ključ Stripe**: te informacije boste našli v računu svojega združenja
  na Stripe ([preberite več spodaj](#get-the-api-keys)).
* **Skrivni ključ Stripe**: te informacije boste našli v računu svojega
  združenja na Stripe ([preberite več spodaj](#get-the-api-keys)).
* **Skrivni ključ webhooka Stripe**: te informacije boste našli v podrobnostih o
  "Webhooku", ki ga morate ustvariti v računu svojega združenja na Stripe
  ([preberite več
  spodaj](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Država vašega računa Stripe**: izberite državo glede na nastavitve vašega
  računa Stripe ([preberite več
  spodaj](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Valuta za plačila**: izberite valuto glede na nastavitve računa Stripe
  ([preberite več
  spodaj](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Contribution types**: in this table, you can disable the [contribution types
  configured in
  Galette](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  that you do not want to be proposed as a payment reason on the payment form.

  *Vrste prispevkov z ničelnim zneskom ali katerih znesek ni konfiguriran, ne
  bodo ponujeni kot razlogi za plačilo na obrazcu, tudi če v tabeli niso
  označeni kot neaktivni.*

  > **Note** — A description, displayed below each payment reason proposed on
  > the payment form, can be defined from the [configuration of the
  > contributions
  > types](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  > of Galette.

> **Note** — It is possible to decide who can access the payment form in
> Galette's settings. Choose the desired option in the [public pages visibility
> parameters](https://doc.galette.eu/en/master/usermanual/preferences.html#parameters).

### Opomba o načinu peskovnika

![Način črtastega peskovnika](images/stripe_menu_sandbox_mode.jpg)

Priporočamo, da preizkusite delovanje vtičnika v načinu peskovnika. Če želite
izvedeti, kako nastaviti takšno testno okolje, si oglejte [dokumentacijo
Stripe](https://docs.stripe.com/sandboxes).

> **Warning** — In this mode, never use real credit card numbers, but only test
> cards (see the list of test cards from the [Stripe
> documentation](https://docs.stripe.com/testing#cards))

## Konfigurirajte svoj račun Stripe

Če želite izvedeti, kako ustvariti račun, si oglejte [dokumentacijo
Stripe](https://docs.stripe.com/get-started/account).

### V nastavitvah računa določite državo in valuto

Izbira države in valute se običajno zahteva pri ustvarjanju vašega računa. Te
informacije najdete v nastavitvah računa:

![Meni z nastavitvami Stripe](images/stripe_menu_settings.jpg)

* *Nastavitve > Podjetje > Podrobnosti računa*

![država določena v nastavitvah računa](images/stripe_settings_country.jpg)

* *Nastavitve > Podjetje > Bančni računi in valute*

![Valuta, določena v nastavitvah računa](images/stripe_settings_currency.jpg)

### Ustvarite Webhook in pridobite ustrezen skrivni ključ

*Webhook*, ki je potreben za pravilno delovanje vtičnika, lahko ustvarite v
meniju *Razvijalci* (nahaja se na spodnji levi strani vaše nadzorne plošče):

![Webhooks v meniju za razvijalce](images/stripe_developers_menu_webhooks.jpg)

*URL končne točke*, ki ga želite določiti v svojem webhooku, je naveden v
nastavitvah vtičnika (primer:
`https://YOUR_DOMAIN_NAME/plugins/stripe/webhook`).

Samo en *Event* mora biti definiran v vašem webhooku. Navedeno je tudi v
nastavitvah vtičnika; je `payment_intent.succeeded`.

![Webhook ustvarjen v računu Stripe](images/stripe_webhook_config.jpg)

Ko je ustvarjen, morate dobiti *skrivni ključ webhook*, ki ga želite določiti v
nastavitvah vtičnika. Na seznamu webhookov kliknite tistega, ki ste ga
ustvarili:

![Seznam spletnih povezav v računu Stripe](images/stripe_webhooks_list.jpg)

*Skrivni ključ webhooka* je mogoče kopirati iz njegovih podrobnosti:

![Skrivnost spletnega trnka](images/stripe_webhook_secret.jpg)

### Pridobite ključe API

*API ključe*, ki so potrebni za pravilno delovanje vtičnika, lahko dobite v
meniju *Razvijalci* (nahaja se v spodnjem levem kotu nadzorne plošče):

![Ključi API-ja v meniju za
razvijalce](images/stripe_developers_menu_api_keys.jpg)

> **Note** — Če želite zmanjšati potencialni vpliv kompromisa, ustvarite
> *Omejeni ključ*. Ta ključ je mogoče ustvariti brez prilagajanja dovoljenj. Za
> več informacij o omejenih ključih si oglejte [dokumentacijo
> Stripe](https://docs.stripe.com/keys#create-restricted-api-secret-key).

![Ključi API-ja, ustvarjeni v računu Stripe](images/stripe_api_keys.jpg)

### Omogočite potrebne načine plačila

Stripe ponuja veliko načinov plačila. V nastavitvah računa morate omogočiti samo
metode, ki jih želite uporabiti.

* *Nastavitve > Plačila > Plačilna sredstva*

![Načini plačila, določeni v nastavitvah
računa](images/stripe_settings_payment_methods.jpg)
