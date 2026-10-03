---
title: Galette Strip
description: Vtičnik za upravljanje članarin in plačil donacij s Stripe
---

Ta vtičnik zagotavlja:

* obrazec za plačilo,
* zgodovino plačil,
* samodejno ustvarjanje prispevkov v Galette, ko so plačila potrjena.

> **Opozorilo** — Ta vtičnik trenutno zahteva **nočno različico Galette**, zato
> **trenutno ni priporočljivo, da bi ga uporabljali v proizvodnji**.

![Plačilni obrazec viden neprijavljenim uporabnikom](images/form_public.jpg)

> **Opomba** — Če želite uporabljati ta vtičnik, mora biti vaš primerek Galette
> javno dostopen in postrežen na https.

## Namestitev

Najprej prenesite vtičnik:

[![Pridobite najnovejši vtičnik
Stripe!](https://img.shields.io/badge/1.0.0-Stripe-ffb619?style=for-the-badge&logo=php&logoColor=white&label=1.0.0-beta1&color=ffb619)](https://github.com/galette-plugins/plugin-stripe/releases/tag/1.0.0-beta1)
[![Pridobite nočno gradnjo vtičnika
Stripe!](https://img.shields.io/badge/Nightly-Stripe-ffb619?style=for-the-badge&logo=php&logoColor=white&label=Nightly&color=ffb619)](https://galette.eu/download/plugins/galette-plugin-stripe-dev.tar.bz2)

Ekstrahirajte preneseni arhiv v imenik Galette `plugins`. Na primer v linuxu
(zamenjava *{url}* in *{version}* z ustreznima vrednostma):

```
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-stripe-{version}.tar.bz2
```

## Inicializacija baze podatkov

Za delovanje ta vtičnik potrebuje več tabel v bazi podatkov. Oglejte si [vmesnik
za upravljanje vtičnikov
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

In to je to, vtičnik *Stripe* je nameščen. :)

## Uporaba vtičnika

Ko je vtičnik nameščen, je skupina Stripe dodana v meni Galette, ko je uporabnik
prijavljen, kar omogoča skrbnikom in članom osebja, da določijo nastavitve
vtičnika in si ogledajo zgodovino plačil.

![Meni vtičnika](images/galette_menu.jpg)

Obrazec za plačilo je dostopen na javnih straneh Galette.

Prispevke s podaljšanjem članstva (oz. članarino) lahko plačujejo le uporabniki,
ki so prijavljeni v svoj račun.

![Plačilni obrazec viden prijavljenim uporabnikom](images/form.jpg)

Redni obiskovalci (uporabniki, ki niso prijavljeni v svoj račun) lahko plačajo
samo prispevke brez podaljšanja članstva (ali donacije). V tem primeru se
prispevek v Galette ne ustvari samodejno, plačilo se pojavi samo v zgodovini
plačil vtičnika z vrednostjo “Brez“, vneseno v stolpcu “Član“.

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
* **Vrste prispevkov**: v tej tabeli lahko onemogočite [vrste prispevkov,
  konfigurirane v
  Galette](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types),
  za katere ne želite, da so ponujene kot razlog za plačilo na obrazcu za
  spletno plačilo.

  *Vrste prispevkov z ničelnim zneskom ali katerih znesek ni konfiguriran, ne
  bodo ponujeni kot razlogi za plačilo na obrazcu, tudi če v tabeli niso
  označeni kot neaktivni.*

### Opomba o načinu peskovnika

![Način črtastega peskovnika](images/stripe_menu_sandbox_mode.jpg)

Priporočamo, da preizkusite delovanje vtičnika v načinu peskovnika. Če želite
izvedeti, kako nastaviti takšno testno okolje, si oglejte [dokumentacijo
Stripe](https://docs.stripe.com/sandboxes).

> **Opozorilo** — V tem načinu ne uporabljajte pravih številk kreditnih kartic,
> ampak samo testne kartice (glejte seznam testnih kartic v [dokumentaciji
> Stripe](https://docs.stripe.com/testing#cards))

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

> **Opomba** — Če želite zmanjšati potencialni vpliv kompromisa, ustvarite
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
