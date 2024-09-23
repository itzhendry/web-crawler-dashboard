# Veebikaapimise Dashboard

## Ülevaade

Veebikaapimise Dashboard on tööriist, mis võimaldab teil kaapida andmeid e-kaubanduse veebilehtedelt ning visualiseerida neid interaktiivsete graafikute ja tabelite kaudu.

## Tehnoloogiad

- **Backend:** PHP
- **Frontend:** HTML, CSS, JavaScript (Chart.js)
- **Andmete Salvestamine:** Tekstifail (`urls.txt`)

## Paigaldamine

1. **Serveri Nõuded:**
   - PHP 7.0 või uuem
   - Veebiserver (nt Apache, Nginx)

2. **Projektifailide Paigutamine:**
   - Laadi alla või klooni projektirepositoorium.
   - Paigalda failid serveri dokumentide kausta.

3. **Konfiguratsioon:**
   - Avage `backend/config.php` ja asendage `API_KEY` oma tugevate API võtmega.
   - Veenduge, et `backend/urls.txt` sisaldab kaapimiseks vajalikke URL-e.

4. **Failiõigused:**
   - Tagage, et serveril oleks kirjutusõigus `backend/urls.txt` failile.

## Kasutamine

1. **Veebilehe Avamine:**
   - Avage brauser ja minge aadressile `http://localhost/web-crawler-dashboard/frontend/index.html`.

2. **Uue URL Lisamine:**
   - Sisestage e-poe URL (nt `http://books.toscrape.com/`) sisendvälja.
   - Klõpsake nuppu "Kaape".
   - Seadistab automaatselt kaapimise protsessi.

3. **Andmete Visualiseerimine:**
   - Pärast kaapimist näete kategooriate ja hinnaklasside jaotust graafikutel ning tabelis.

## Turvalisus

- **API Võti:** Hoidke `API_KEY` konfidentsiaalsena. Ärge avaldage seda avalikes repositooriumides.
- **Kasutuspiirangud:** Soovitatav on rakendada täiendavaid turvameetmeid nagu IP-põhine autentimine või kasutajate haldamine.

## Veaotsing

- **Logifailid:** Kontrollige serveri logisid (`error_log`), et tuvastada ja lahendada probleeme kaapimise või autentimisega.
- **Veebilehe Struktuur:** Veenduge, et sihtveebilehed ei ole muutunud, mis võib mõjutada kaapimise loogikat.

## Laiendamine

- **Uued Veebilehed:** Kohandage `crawler.php`, et toetada uusi veebilehtede struktuure.
- **Andmete Salvestamine:** Integreerige andmebaas nagu MySQL või MongoDB suuremahuliste andmete haldamiseks.

## Kontakt

Kui teil on küsimusi või vajate abi, võtke ühendust hendryvalingg@gmnail.com.

