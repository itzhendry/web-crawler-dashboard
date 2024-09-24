# Veebikaapimise Dashboard

## Ülevaade

Veebikaapimise Dashboard on tööriist, mis võimaldab teil kaapida andmeid erinevatelt e-kaubanduse ja andmebaasi veebilehtedelt ning visualiseerida neid interaktiivsete graafikute ja tabelite kaudu. Rakendus toetab praegu veebisaite nagu **Books to Scrape**, **IMDB**, ja **Amazon**, kuid on laiendatav teistele saitidele.

## Tehnoloogiad

- **Backend:** PHP
- **Frontend:** HTML, CSS, JavaScript (Chart.js)
- **Andmete Salvestamine:** Tekstifail (`urls.txt`)

## Funktsionaalsus

- **Veebikaapimine:** Kaapige andmeid toodete hindade, kategooriate, hinnangute ja muu kohta mitmesugustelt veebilehtedelt.
- **Visualiseerimine:** Andmeid kuvatakse kategooriate ja hinnaklasside jaotuses interaktiivsete graafikute ja tabelitena.
- **URL Halduse API:** Lisage ja hallake kaapimise jaoks vajalikke URL-e lihtsa API kaudu.

### Example URLs for Testing
- http://books.toscrape.com/ (for book categories and prices)
- https://www.imdb.com/chart/top/ (for movie listings)
- https://www.amazon.com/s?k=electronics (for product prices)

## Kontakt

Kui teil on küsimusi või vajate abi, võtke ühendust hendryvalingg@gmnail.com.

## Paigaldamine

### Nõuded:

- **Server:** Apache, Nginx või muu veebiserver
- **PHP:** Versioon 7.0 või uuem
- **Failiõigused:** Veenduge, et serveril oleks kirjutusõigus `backend/urls.txt` failile

### Sammud:

1. **Projektifailide Paigutamine:**
   - Klooni või laadi alla projekt failid ja aseta serverisse.
   ```bash
   git clone <repository-url>
