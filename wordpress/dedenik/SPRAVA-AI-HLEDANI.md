# Chytré hledání

Od verze 0.5.0 lze hledání napojit na databázový index celého veřejného archivu včetně autorů, rubrik a štítků. Přesné autorské dotazy mají úplný stránkovaný výpis bez volání Gemini. Vytvoření indexu, jeho strukturu a průběžnou synchronizaci popisuje [SPRAVA-INDEXU.md](SPRAVA-INDEXU.md). Níže uvedený výběr pomocí WP_Query se používá jako záloha, dokud index není připravený.

V administraci otevřete **Nastavení → Chytré hledání**. Výchozí stav je vypnuto, bez API klíče se návštěvníkům formulář nezobrazuje a Google se nekontaktuje.

1. V Google AI Studio vytvořte API klíč k projektu s aktivovaným placeným Gemini API: https://aistudio.google.com/api-keys
2. V Google Cloud nastavte rozpočet a upozornění a omezte API klíč na Generative Language API. Upozornění rozpočtu samo o sobě nezastavuje útratu.
3. Klíč vložte výhradně do nastavení WordPressu; případně jej správce může dodat konstantou `DEDENIK_GEMINI_API_KEY` v soukromém wp-config.php. Klíč se nezobrazuje návštěvníkům ani ve formuláři. Databázová hodnota není šifrovaná; k zálohám DB mají mít přístup jen správci.
4. Nastavte denní limit (výchozí 50 nových hledání za UTC den) a zaškrtněte zapnutí. Formulář se objeví u hledání na hlavní stránce a na stránce výsledků.

Modul používá `gemini-3.1-flash-lite`, pevný HTTPS endpoint a maximálně dvě volání na nové hledání. První převádí přirozený český dotaz na krátká klíčová slova. WordPress přes WP_Query vybere nejvýše 32 veřejných článků/stránek (publikované, bez hesla). Druhé volání pouze seřadí jejich ID. Zobrazí se nejvýše osm skutečných odkazů s úryvky z DB. Nejde o generování odpovědí ani o úplný sémantický index: relevantní článek se musí nejprve dostat do výběru podle slov. Výsledky mohou být neúplné.

Google dostává dotaz, ID, názvy, data a nejvýše 900 znaků textu na kandidáta. Komentáře, uživatelé, soukromé příspěvky a DB přihlašovací údaje se neposílají. Není zapnuté Google Search grounding ani přístup k internetu/nástrojům modelu. Publikované texty se mohou samy týkat lidí; zohledněte zpracování u Googlu v informacích o soukromí webu před veřejným spuštěním. Použijte placený tarif podle aktuálních podmínek Googlu.

Každé nové hledání atomicky rezervuje jednu položku denního limitu, i když API selže; položka dovoluje maximálně dvě volání s výstupem do 1024 tokenů na volání. Denní limit není finanční strop. Opakovaný dotaz se stejným klíčem využívá až 12 hodin mezipaměť ID; změna/smazání článku ji zneplatní. Viditelnost příspěvků se ověřuje znovu i před zobrazením. Jeden návštěvník smí odeslat tři nové dotazy za minutu; globální limit platí i při souběhu požadavků. Běžné hledání funguje bez Googlu a zůstává dostupné při chybě nebo vyčerpání limitu.

Testovací izolace zůstává zapnutá. Výjimka povoluje pouze právě probíhající volání tohoto modulu na pevný endpoint Googlu. Ostatní HTTP a e-mail z testovací kopie zůstávají blokované. Přihlašovací údaje původního webu se nemění.

Ověřeno podle https://ai.google.dev/gemini-api/docs/pricing dne 21. 10. 2026: standardní placené Gemini 3.1 Flash-Lite má cenu 0,25 USD / milion vstupních a 1,50 USD / milion výstupních tokenů včetně přemýšlení. Příklad celkem 8 000 vstupních a 400 výstupních tokenů vychází na 0,0026 USD za hledání. Skutečná spotřeba závisí na textech a dotazu; sledujte ji v Google AI Studio. Bez skutečného API klíče se testuje pomocí podvržených odpovědí, nikoli živého účtovaného API.
