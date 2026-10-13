---
title: Galette Stripe
description: Dodatak za plaćanje članarine i donacija putem Stripea
---

Dodatak omogućuje:

* obrazac za plaćanje,
* povijest plaćanja,
* automatsko dodavanje uplata u Galette nakon potvrde plaćanja.

![Obrazac za plaćanje vidljiv *neprijavljenim*
korisnicima](images/form_public.jpg)

> **Napomena** — Instalacija Galettea mora biti javno dostupna i imati valjan
> SSL certifikat.

## Instalacija

Najprije preuzmite dodatak:

* [Preuzmite najnoviji dodatak
  Stripe!](https://github.com/galette-plugins/plugin-stripe/releases/latest)
* [Preuzmite noćnu razvojnu verziju dodatka
  Stripe!](https://github.com/galette-plugins/plugin-stripe/releases/tag/nightly)

Raspakirajte preuzetu arhivu u Galetteovu mapu `plugins`. Primjerice, na Linuxu
(zamijenite *{url}* i *{version}* odgovarajućim vrijednostima):

```
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-stripe-{version}.tar.bz2
```

## Inicijalizacija baze podataka

Dodatak zahtijeva nekoliko tablica u bazi. Pogledajte [sučelje za upravljanje
dodacima
Galettea](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Gotovo; dodatak *Stripe* je instaliran. :)

## Uporaba dodatka

Nakon instalacije prijavljenom se korisniku u izborniku pojavljuje odjeljak
*Stripe*. Administratori i uprava mogu podesiti dodatak i pregledati povijest
plaćanja.

![Izbornik dodatka](images/galette_menu.jpg)

Obrazac za plaćanje dostupan je na Galetteovim javnim stranicama.

Samo *prijavljeni* korisnici mogu izvršiti uplate *koje produljuju članstvo*,
odnosno platiti članarinu.

![Obrazac za plaćanje vidljiv prijavljenim korisnicima](images/form.jpg)

Posjetitelji (*neprijavljeni* korisnici) mogu izvršiti samo uplate *koje ne
produljuju članstvo*, odnosno donacije. U tom se slučaju uplata ne dodaje
automatski u Galette, nego se plaćanje prikazuje samo u povijesti dodatka s
vrijednošću „Nema” u stupcu „Član”.

![Stranica povijesti plaćanja](images/history.jpg)

## Postavke

![Stranica postavki](images/settings.jpg)

* **URL krajnje točke Stripe webhooka**: adresa za izradu webhooka u Stripe
  računu udruge ([više u
  nastavku](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Događaj Stripe webhooka**: naziv događaja za izradu webhooka u Stripe računu
  udruge ([više u
  nastavku](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Javni ključ Stripea**: nalazi se u Stripe računu udruge ([više u
  nastavku](#get-the-api-keys)).
* **Tajni ključ Stripea**: nalazi se u Stripe računu udruge ([više u
  nastavku](#get-the-api-keys)).
* **Tajni ključ Stripe webhooka**: nalazi se u pojedinostima webhooka koji
  trebate izraditi u Stripe računu udruge ([više u
  nastavku](#create-a-webhook-and-get-the-corresponding-secret-key)).
* **Država Stripe računa**: odaberite državu prema postavkama Stripe računa
  ([više u
  nastavku](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Valuta plaćanja**: odaberite valutu prema postavkama Stripe računa ([više u
  nastavku](#get-the-country-and-currency-defined-in-your-account-settings)).
* **Vrste uplata**: u tablici možete isključiti [vrste uplata podešene u
  Galetteu](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  koje ne želite ponuditi kao svrhu plaćanja u obrascu.

  *Vrste uplata s iznosom nula ili bez zadanog iznosa neće se ponuditi kao svrha
  plaćanja, čak i ako nisu označene kao neaktivne u tablici.*

  > **Napomena** — Opis ispod svake svrhe plaćanja možete zadati u [postavkama
  > vrsta
  > uplata](https://doc.galette.eu/en/master/usermanual/contributions.html#contributions-types)
  > u Galetteu.

> **Napomena** — U Galetteovim postavkama možete odrediti tko pristupa obrascu
> za plaćanje. Odaberite željenu opciju u [postavkama vidljivosti javnih
> stranica](https://doc.galette.eu/en/master/usermanual/preferences.html#parameters).

### Napomena o testnom načinu

![Testni način Stripea](images/stripe_menu_sandbox_mode.jpg)

Preporučuje se provjeriti dodatak u testnom okruženju. Upute za njegovo
postavljanje potražite u [dokumentaciji
Stripea](https://docs.stripe.com/sandboxes).

> **Upozorenje** — U ovom načinu koristite isključivo testne kartice, nikada
> stvarne brojeve kartica. Popis testnih kartica nalazi se u [dokumentaciji
> Stripea](https://docs.stripe.com/testing#cards).

## Podešavanje Stripe računa

Upute za izradu računa potražite u [dokumentaciji
Stripea](https://docs.stripe.com/get-started/account).

### Pronalaženje države i valute u postavkama računa

Država i valuta obično se odabiru pri izradi računa. Podatke možete pronaći u
postavkama računa:

![Izbornik postavki Stripea](images/stripe_menu_settings.jpg)

* *Settings > Business > Account details* (Postavke > Poslovanje > Podaci
  računa)

![Država zadana u postavkama računa](images/stripe_settings_country.jpg)

* *Settings > Business > Bank accounts and currencies* (Postavke > Poslovanje >
  Bankovni računi i valute)

![Valuta zadana u postavkama računa](images/stripe_settings_currency.jpg)

### Izrada webhooka i pronalaženje njegova tajnog ključa

Webhook potreban za rad dodatka možete izraditi u izborniku *Developers*
(Razvojni alati), pri dnu lijevo na nadzornoj ploči:

![Webhookovi u izborniku razvojnih
alata](images/stripe_developers_menu_webhooks.jpg)

*Endpoint URL*, odnosno adresu krajnje točke webhooka, pronaći ćete u postavkama
dodatka (primjer: `https://YOUR_DOMAIN_NAME/plugins/stripe/webhook`).

U webhooku treba zadati samo jedan *Event* (događaj). Naveden je i u postavkama
dodatka: `payment_intent.succeeded`.

![Webhook izrađen u Stripe računu](images/stripe_webhook_config.jpg)

Nakon izrade pronađite *tajni ključ webhooka* i unesite ga u postavke dodatka. U
popisu webhookova kliknite onaj koji ste izradili:

![Popis webhookova u Stripe računu](images/stripe_webhooks_list.jpg)

*Tajni ključ webhooka* možete kopirati iz njegovih pojedinosti:

![Tajni ključ webhooka](images/stripe_webhook_secret.jpg)

### Pronalaženje API ključeva

*API ključevi* potrebni za rad dodatka dostupni su u izborniku *Developers*
(Razvojni alati), pri dnu lijevo na nadzornoj ploči:

![API ključevi u izborniku razvojnih
alata](images/stripe_developers_menu_api_keys.jpg)

> **Napomena** — Kako biste smanjili posljedice moguće kompromitacije, izradite
> *Restricted key* (ograničeni ključ). Možete ga izraditi bez prilagodbe
> ovlasti. Više informacija potražite u [dokumentaciji
> Stripea](https://docs.stripe.com/keys#create-restricted-api-secret-key).

![API ključevi izrađeni u Stripe računu](images/stripe_api_keys.jpg)

### Uključivanje potrebnih načina plaćanja

Stripe nudi mnoge načine plaćanja. U postavkama računa uključite samo one koje
želite koristiti.

* *Settings > Payments > Payment methods* (Postavke > Plaćanja > Načini
  plaćanja)

![Načini plaćanja zadani u postavkama
računa](images/stripe_settings_payment_methods.jpg)
