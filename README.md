
# Veebikaapimise Dashboard

## Ülevaade

**Veebikaapimise Dashboard** on täisstacki veebirakendus, mis on loodud praktika raames näitamaks võimekust e-kaubanduse ja informatiivsete veebisaitide andmekaeveks. Rakendus võimaldab kasutajatel sisestada sihtveebisaitide URL-e, käivitada kaapimisprotsessi ja visualiseerida kogutud andmeid interaktiivsete graafikute ja tabelite abil. See tööriist on kasulik toodete kategooriate, hinnaklasside ja muude oluliste mõõdikute analüüsimiseks erinevatel platvormidel.

## Testimiseks Näidis URL-id

- [http://books.toscrape.com/](http://books.toscrape.com/) (raamatute kategooriate ja hindade jaoks)
- [https://www.ebay.com/b/Nike-Air-Force-1-Sneakers-for-Men/15709/bn_7115514424](https://www.ebay.com/b/Nike-Air-Force-1-Sneakers-for-Men/15709/bn_7115514424)
- [https://arvutitark.ee/arvutid-ja-lisad/monitorid/1](https://arvutitark.ee/arvutid-ja-lisad/monitorid/1)
- Kõik `/arvutitark.ee/*` kataloogid
- Proovige ka teistel saitidel!

## Funktsioonid

- **Andmekaeve:** Ekstraheerib infot, nagu toodete pealkirjad, hinnad, pildid ja kategooriad määratud veebisaitidelt.
- **API Halduse:** Pakub RESTful API-d uute URL-ide lisamiseks ja kaapimisprotsessi käivitamiseks.
- **Interaktiivne Dashboard:** Kuvab kaapitud andmeid graafikute (Pie ja Bar) ja tabelite kaudu.
- **URL Halduse:** Kasutajatel on võimalik hallata kaapimiseks vajalikke URL-e.
- **Reaalajas Oleku Uuendused:** Kuvab kaapimisoperatsioonide hetkeolekut reaalajas.

## Tehnoloogiad

- **Back-end:**
  - PHP
  - RESTful API
- **Front-end:**
  - HTML5
  - CSS3
  - JavaScript (ES6)
  - Chart.js
- **Andmete Salvestamine:**
  - Tekstifail (`urls.txt`)
- **Versioonihaldus:**
  - Git

## Projekti Struktuur

```
project-root/
├── backend/
│   ├── api.php
│   ├── crawler.php
│   ├── config.php
│   └── urls.txt
├── frontend/
│   ├── index.html
│   ├── styles.css
│   └── script.js
├── .gitignore
└── README.md
```

### Back-end

- **api.php:** Käsitleb API päringuid uute URL-ide lisamiseks ja kaapimisprotsessi käivitamiseks. Tagastab JSON-andmeid ja teostab autentimist.
- **crawler.php:** Sisaldab loogikat veebisaitide kaapimiseks ja vajalike andmete ekstraheerimiseks. Toetab erinevate veebistruktuuride töötlemist.
- **config.php:** Salvestab konfiguratsioonimuutujad, sealhulgas API võtme.
- **urls.txt:** Hoiab kaapimiseks lisatud URL-ide loetelu.

### Front-end

- **index.html:** Peamine HTML-fail, mis määrab dashboardi liidese ülesehituse.
- **styles.css:** Stiilileht, mis kujundab dashboardi välimust.
- **script.js:** Käsitleb front-endi loogikat, sealhulgas API-ga suhtlemist ja andmete visualiseerimist.

## Paigaldamine

### Eeldused

- **Veebiserver:** Apache, Nginx või muu sobiv veebiserver.
- **PHP:** Versioon 7.0 või uuem.
- **Composer:** Valikuline PHP sõltuvuste haldamiseks.
- **Internetiühendus:** Välistest raamatukogudest, nagu Chart.js ja Google Fonts, laadimiseks.

### Paigaldamise Sammud:

1. **Projektifailide Paigutamine:**
   - Klooni või laadi projekt alla ja paiguta veebiserverisse:
   ```bash
   git clone <repository-url>
   ```
   - Veendu, et veebiserveril oleks kirjutamisõigus `backend/urls.txt` failile:
   ```bash
   chmod 664 backend/urls.txt
   ```

## Kontakt

**Hendry Valingg**

- Email: hendryvalingg@gmail.com
- GitHub: [itzhendry](https://github.com/itzhendry)
