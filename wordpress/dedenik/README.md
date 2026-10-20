# Šablona Dedeník 0.3.1

Aktualizace patičky: logo a tři sloupce podle reference, samostatná menu a spodní řádek, pruh s krajinou bez duplicitního textu a responzivní rozložení. Správu popisuje [SPRAVA-PATICKY.md](SPRAVA-PATICKY.md). Balíček `dedenik-0.3.1.zip` zahrnuje i správu Světa Dede z verze 0.3.0.

Aktualizace 20. října 2026: správa Světa Dede, Podcastů a Divadla přes nativní stránky a rubriky. Nová šablona stránky podporuje výpis příspěvků a nejnovější podcastový přehrávač. Karty homepage mají upravitelné texty, obrázky, pořadí a cíle včetně externího odkazu na knihy. Postup pro redakci je v [SPRAVA-SVETA-DEDE.md](SPRAVA-SVETA-DEDE.md). Balíček je `dedenik-0.3.0.zip`; konfiguraci ani DB při této aktualizaci znovu nenahrávejte.

Aktualizace 13. října 2026: šablona je ověřena na lokální kopii skutečného WordPressu a dubnového dumpu. Fungují homepage, archivy, hledání a detail s 34 komentáři. Původní pluginy zůstávají vypnuté a vyžadují další integrační ověření. Nový balíček je `dedenik-0.2.0.zip`; starší údaje o offline ověření níže jsou doplněny zprávou `analysis/LOKALNI-KOPIE.md` v kořeni pracovního projektu.

Vlastní klasická PHP šablona vycházející z mockupu. Obsah načítá přes WordPress API ze stávající databáze. Neobsahuje dump, skutečné články, přístupové údaje ani kopii WordPress jádra. Nic neimportuje, nemění prefix tabulek, permalinky, role ani nastavení komentářů. Nenahrazuje funkce pluginů.

## Co obsahuje

- Responzivní hlavičku, mobilní menu, přihlášení přes stávající WordPress a patičku.
- Úvodní stránku s nejnovějším článkem, seznamem článků a skutečným stránkováním; existující hlavní dotaz WordPressu zůstává zachovaný.
- Chatník podle existující rubriky (automaticky slug `chatnik` / název `Chatník`, nebo výběrem v administraci).
- Kategorie, autorské a datumové archivy, štítky a vyhledávání přes nativní `s`. Obsluhuje je společný `archive.php`; speciální soubor pro každý archiv není potřeba.
- Skutečné detaily článků a stránek, multipage obsah, původní shortcodes přes `the_content()`, nativní komentáře a odpovědi.
- Podporu klasického i blokového editoru, theme.json a lokální fonty bez externího stahování.
- Ochranu perexů, úvodních obrázků a diskuse heslem chráněných příspěvků. Soukromé, konceptové a naplánované příspěvky se neobjevují ve vedlejších homepage dotazech.
- Neutrální kartu pro článek bez featured image; nepotřebuje hromadnou regeneraci obrázků.
- Přečtení původního přiřazení menu z `theme_mods_frontier`, dokud není přiřazené menu nové šablony. Nové přiřazení má přednost. Původní nastavení se nepřepisuje.
- Stejné identifikátory 12 pozic widgetů jako Frontier. Levý a pravý sloupec jsou v novém layoutu společně vedle článku nebo pod seznamem; obsah widgetů řídí stávající pluginy a administrace. Chování jejich přesunu při aktivaci ověřit na kopii.

## Kompatibilita a instalace na testovací kopii

Minimum deklarované šablonou: WordPress 6.2, PHP 7.4. Pro provoz použít podporovanou verzi PHP po ověření všech pluginů. Historický PHP 5.6 handler v původním .htaccess se k nové šabloně nehodí. Přesnou verzi běžícího produkčního WP zatím nemáme potvrzenou; db_version 60421 je pouze indicie.

1. Dokončit kopii WordPressu a připravit lokální/testovací databázi oddělenou od produkce. Před prvním spuštěním zablokovat e-maily, cron a externí integrační akce. Upravit adresy serializaci respektujícím nástrojem. Produkční wp-config nepoužít.
2. V testovacím WordPressu otevřít Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu a vybrat `dedenik-0.1.0.zip`, nebo zkopírovat tento adresář do `wp-content/themes/dedenik`.
3. Aktivovat jen na testovací kopii. Ponechat původní Frontier pro návrat.
4. Vzhled → Přizpůsobit → Dedeník – úvodní stránka: vybrat rubriku Chatníku, případně náhradní obrázek hero a existující veřejné stránky Podcast / Dedivadlo / Knihy. Nevybrané karty se nezobrazují.
5. Zkontrolovat Vzhled → Menu a Widgety. Na úvodní stránce je podle stávající DB nastaveno „Nejnovější příspěvky“. Pokud zvolíte statickou stránku, šablona respektuje její skutečný obsah místo vnucení homepage layoutu.
6. Ponechat existující permalink strukturu, slugs a nastavení komentářů. Logo lze změnit v identitě webu; výchozí logo pochází z mockupu.

Nevznikají ukázkové články, rubriky ani účty. Nastavení vzhledu se zapisuje pouze standardní administrací na základě změn správce, ne při načtení šablony.

## Ověření před produkcí

- Staré URL článků, stránek, kategorií, štítků, autorů, datumových archivů a médií.
- První i další stránka homepage/archivu, hledání, prázdné výsledky a 404.
- Přihlášení/odhlášení/reset hesla, práva všech rolí, koncepty a privátní články.
- Přidání, odpověď, moderace a stránkování komentářů; pluginové ochrany formuláře, Akismet a diskuse s velkým počtem odpovědí.
- Heslem chráněné články bez i po zadání hesla.
- Galerie, caption, video/audio, slickr-flickr a latex ve skutečném obsahu. Závislé pluginy musí zůstat dostupné.
- Původní vlastní CSS, head/footer skripty a nastavení Frontier se nekopírují automaticky. Zkontrolovat zvlášť obsah těchto nastavení, analytiku a potřebné hooky/pluginové závislosti. Šablona neposkytuje interní funkce `frontier_*`.
- Naplánované publikování testovat odděleně a zapnout ho až ve vhodném prostředí; šablona cron nemění.
- Původní statistiky, e-shop a jiné pluginové tabulky se zachovávají; šablona je nepřepisuje.

## Aktuální rozsah ověření

PHP syntaxe všech 24 PHP souborů je ověřena přenosným PHP 8.5.11. Prošlo 18 offline behaviorálních kontrol. Offline testy kontrolují menu fallback a jeho prioritu, výběr kategorie, escapování, ochranu obrázků/komentářů, chybějící média a nezobrazení konceptových nebo chráněných promo stránek. Jde o kontrolované testovací náhrady WordPress funkcí, nikoli integrační test reálného WordPressu.

Vizuální fixture používá skutečné CSS/JS šablony a syntetický obsah. Prošly kontroly homepage i článku na šířkách 320, 390, 768 a 1440 px bez horizontálního přetékání a JS chyb, otevření/zavření menu a dostupnosti navigace bez JS. Nenahrazuje ověření na kopii živé databáze. Neověřeno: spuštění v plném WordPressu, pluginy, odeslání komentářů a shoda se všemi historickými články. Šablona je připravená pro první lokální test, nikoli ověřená k okamžité aktivaci na produkci.

Záměrně zatím není přidána AI služba ani ukládání oblíbených do účtů: mockup tyto backendové funkce nemá. Nativní vyhledávání prohledává WordPress obsah, nikoli automaticky všechna pole autorů a taxonomií.

Oficiální dokumentace: [Komentářové šablony](https://developer.wordpress.org/themes/classic-themes/templates/partial-and-miscellaneous-template-files/comment-template/), [theme.json](https://developer.wordpress.org/themes/global-settings-and-styles/introduction-to-theme-json/).
