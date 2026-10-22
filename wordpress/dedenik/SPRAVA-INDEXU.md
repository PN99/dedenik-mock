# Databázový index hledání (0.5.0)

Index používá vlastní tabulky s aktuálním prefixem WordPressu. Původní články ani jejich autorství nepřepisuje.

| Tabulka | Obsah a indexy |
| --- | --- |
| `dedenik_search_documents` | Jeden řádek na veřejný článek/stránku: ID, autor WP, název, celý vyčištěný text, veřejná autorská jména, rubriky/štítky, datum publikace a indexace. Primární ID, index autora a data, FULLTEXT přes název, text, autory a taxonomie. |
| `dedenik_search_credits` | Vazby článků na veřejná autorská jména a přezdívky. Index normalizovaného jména; zdroj `wordpress`, `title` nebo `editor`. |
| `dedenik_search_terms` | Vazby na rubriky a štítky: ID taxonomie, typ, název a slug. Index ID taxonomie a typu. |

Tyto tabulky obsahují články, nikoli komentáře. Od verze 0.6.0 mají veřejné schválené komentáře [vlastní index diskusí](SPRAVA-DISKUSI-HLEDANI.md). Neindexují se soukromá pole uživatelů, hesla, neveřejný obsah ani přílohy. Před vrácením výsledků se znovu ověřuje současná viditelnost příspěvku ve WordPressu. Úryvky se vždy čtou z aktuálního zdroje, nikoli ze starého indexu.

## Autoři

Vedle veřejného zobrazovaného jména autora WP se odvodí kredit z titulku ve tvaru `RUBRIKA – Jméno: Název`. Je to pomocná atribuce pro hledání, nikoli změna vlastnictví článku. Automatický odhad může vyžadovat redakční kontrolu. Ve formuláři článku je panel **Autoři pro hledání**, kde lze přidat další jména/přezdívky, každé na nový řádek. Programové změny tohoto pole se také synchronizují.

Dotazy `814151`, `vše od 814151`, `něco od uživatele 814151` nebo `od autora Tora` používají přesnou shodu normalizovaného jména (bez rozdílů velikosti a diakritiky). Vypíší všechny odpovídající články po 12 se stránkováním, bez volání Gemini a bez čerpání jeho denního limitu. Filtr autora je samostatný; kombinování autora s dalším tématem či rokem není zatím samostatný parser.

## Tematické hledání

Gemini připraví slova, databáze prohledá FULLTEXT index celého veřejného archivu včetně autorů, rubrik a štítků. Nejvýše 32 kandidátů s aktuálními úryvky, veřejnými autorskými jmény a tématy předá Gemini k seřazení. FULLTEXT používá prefixy slov. Nejde o embeddingy ani plnohodnotné sémantické hledání; české skloňování pomáhá řešit rozšíření dotazu modelem, ale výsledky mohou být neúplné. Neznámý autor bez přesné shody přejde na tematické hledání.

## První vytvoření a obnova

Tabulky nevznikají při každém načtení webu. Po nasazení kódu spusťte GitHub Actions **Build staging search index**. Cíl je pevně omezený na testovací web `svetdede.dedenik.cz`. Workflow nahraje dočasný PHP worker chráněný náhodným tokenem v HTTP hlavičce s platností 30 minut, vytvoří schéma přes WordPress `dbDelta` a zpracuje obsah po dávkách 75 ID. Worker před operací kontroluje cílový web a odmítne známý název původní produkční DB. Přístupy k DB používá pouze stávající serverový WordPress; GitHub je nedostává.

Po dokončení porovná počet řádků se všemi veřejnými články a stránkami, ověří chybějící/neveřejné záznamy, fulltext a autora 814151. Dočasný worker se v `finally` odstraní i při běžné chybě; při násilném ukončení běhu jeho token po 30 minutách přestane platit. Indexování nevolá Gemini. Při opakování se index bezpečně obnoví bez mazání zdrojového obsahu. Do dokončení obnovy se používá původní vyhledávací výběr.

Úpravy, publikování, změny soukromí/hesla, smazání, změny veřejného jména uživatele, rubrik, štítků a redakčních autorských přezdívek se průběžně promítají do indexu. Přímé SQL importy obcházející WordPress hooky vyžadují nové spuštění workflow. Přepnutí šablony zastaví její synchronizační hooky; před návratem index znovu obnovte. Stav a počet záznamů jsou v **Nastavení → Chytré hledání**.

Lokální integrační test: `php -c local/php.ini mockup/wordpress/tests/search-index-integration.php C:/Projekty/Dedenik/local/site/wp-load.php`. Odmítá jiné prostředí než izolovanou lokální DB, nevolá Google a vytvořené testovací články odstraní.

Schéma vychází z [WordPress dbDelta](https://developer.wordpress.org/reference/functions/dbDelta/); vyhledávání z [MySQL FULLTEXT Boolean mode](https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html).
