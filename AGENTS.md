# Práce na Dedeníku

Repozitář `PN99/dedenik-mock` má dvě větve s odlišným uspořádáním souborů. Před úpravou si vyber cílový web.

| Cíl změny | Větev | Kde upravovat |
| --- | --- | --- |
| Vývoj a test na [Světě Dede](https://svetdede.dedenik.cz/) | `main` (výchozí) | Šablona `wordpress/dedenik/` |
| Veřejný náhled [Dedeník /new](https://dedenik.cz/new/) | `production` | Šablona `wp-content/themes/dedenik/`, pravidla náhledu ve `wp-content/mu-plugins/` |

## První změna

Přihlas se na GitHub vlastním účtem. Pro odesílání větví přímo do repozitáře potřebuješ oprávnění **Write**, které ti může přidělit správce. Těmito příkazy si stáhneš kód náhledu a založíš pracovní větev.

```sh
git clone --branch production https://github.com/PN99/dedenik-mock.git dedenik-new
cd dedenik-new
git switch -c uprava/nazev-zmeny
```

Po úpravě zkontroluj rozdíly pomocí `git diff`, přidej jednotlivé změněné soubory a vytvoř commit. Odešli pracovní větev příkazem `git push -u origin HEAD`. Na GitHubu otevři pull request s cílovou větví `production`. Při práci na Světě Dede začni z aktuální větve `main` a jako cíl pull requestu vyber `main`.

## Nasazení a ověření

- Po sloučení změny šablony do `main` ji GitHub Actions po úspěšných kontrolách nasadí na Svět Dede. Až ji tam vyzkoušíš, založ pro převzetí změny větev z aktuální `production`. Přenes konkrétní úpravu z `wordpress/dedenik/` do odpovídajícího souboru pod `wp-content/themes/dedenik/`. Celou větev `main` do `production` neslučuj, kořeny větví obsahují jiné soubory.
- Push do `production` spouští automatické nasazení změněných souborů, které šablona používá na webu, a MU pluginu do `/public_html/www/new/`. V GitHub Actions otevři běh **Dedeník /new** pro příslušný commit a ověř výsledek kontroly přenosu nanečisto i samotného nahrání. Potom přes HTTPS navštiv dotčenou stránku pod `/new/` a vyzkoušej změněné chování.
- Náhled čte stávající produkční databázi. Přihlašovací údaje zůstávají na hostingu a v GitHub Secrets. Do Gitu nepatří `wp-config.php`, hesla, SQL zálohy ani média z `wp-content/uploads/`. Přepnutí hlavní domény se provádí samostatně po testech.

Pokud kontrola ohlásí, že se soubor na hostingu liší od obou verzí v Gitu, porovnej tento soubor s větví `production` a nejdřív vyřeš rozdíl. Po přerušení přenosu otevři původní běh v Actions a použij **Re-run failed jobs**. Další push pak převezme změny od posledního dokončeného přenosu. Jádro WordPressu a běžné pluginy jsou také ve větvi `production`; jejich nasazení na hosting naplánuj zvlášť.

**Zadání pro agenta**

> Přečti AGENTS.md. Pro náhled /new vytvoř pracovní větev z aktuální origin/production. Uprav [popis změny], ověř [očekávané chování] a připrav pull request do production. Připoj konkrétní adresu a kroky, kterými změnu po nasazení ověřím.
