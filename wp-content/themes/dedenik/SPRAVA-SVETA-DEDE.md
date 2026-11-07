# Správa Světa Dede, Podcastů a Divadla

Verze šablony 0.3.0. Tyto možnosti fungují po aktualizaci šablony na testovacím webu. Změny místní kopie se samy na hosting nepřenášejí.

## První nastavení

1. V **Příspěvky → Rubriky** založte rubriku **Svět Dede**. Pod ní můžete vytvořit **Podcasty** a **Divadlo**. Existující rubriku Dedivadlo můžete rovněž použít; není nutné přesouvat nebo přejmenovávat staré články. Ostatní rubriky Dedeníku zůstávají zachované.
2. Ve **Stránky → Vytvořit stránku** vytvořte stránku **Podcasty**. Vyplňte úvodní text a vyberte šablonu stránky **Svět Dede – rubrika** v nastavení stránky. Ve spodním panelu **Svět Dede – obsah rubriky** nastavte typ **Podcasty** a rubriku **Podcasty**. Uložte.
3. Stejně vytvořte stránku **Divadlo** s typem **Divadlo** a odpovídající rubrikou. Představení divadla, program a videa jsou obsahem této stránky. Program vložte jako běžný blok Tabulka nebo seznam. YouTube video vložíte blokem YouTube či vložením odkazu na samostatný řádek. Stránka se pak upravuje bez zásahu do PHP.
4. Volitelně vytvořte stránku **Svět Dede**, vyberte stejnou šablonu a typ **Svět Dede / jiná rubrika**. Jako zdroj vyberte nadřazenou rubriku Svět Dede. Zobrazí i příspěvky podřízených rubrik. Úvod, rozcestník a další texty upravujte v editoru.
5. V **Vzhled → Přizpůsobit → Dedeník – Svět Dede** vyberte vytvořené stránky pro karty Podcasty a Divadlo. Pro Knihy vložte vlastní odkaz **https://alzbetaadrak.cz/**. U každé karty lze změnit označení, titulek, popis, obrázek a text tlačítka. Vlastní odkaz má přednost před vybranou stránkou.
6. Zde lze také upravit nadpis sekce, vybrat stránku celého Světa Dede, přehodit pořadí tří karet nebo sekci vypnout. Změny potvrďte tlačítkem Publikovat v Přizpůsobení.
7. Odkazy na stránky můžete přidat přes **Vzhled → Menu**. Počet dalších rubrik a stránek není omezený na tyto tři karty. Homepage má tři připravené pozice; další rubriky lze zobrazit v navigaci nebo na stránce Světa Dede.

## Přidání podcastového dílu

- Otevřete **Příspěvky → Vytvořit příspěvek**. Vyplňte název dílu, text a případně náhledový obrázek. Zaškrtněte rubriku Podcasty a publikujte.
- Zvuk přidejte standardním blokem **Zvuk**: nahrajte MP3 do Médií nebo vložte přímou URL zvukového souboru. Podporován je také klasický `[audio]` shortcode a standardní zvukové enclosure metadata.
- Pro Spotify použijte blok pro vložení obsahu s URL epizody na `https://open.spotify.com/episode/...`. Stránka rozpozná audio či Spotify z nejnovějšího veřejného dílu a zobrazí přehrávač nahoře. Pokud díl zdroj zatím nemá, zobrazí se v seznamu bez přehrávače; žádný ukázkový přehrávač se nedoplňuje.
- Pod přehrávačem je seznam všech veřejných dílů, po 12 na stránku. Koncepty, soukromé a heslem chráněné příspěvky se do veřejného seznamu nedostanou.
- Odkaz na celý podcast ve Spotify a Apple Podcasts doplníte později v panelu stránky Podcasty. Nevyplněná tlačítka se nezobrazují. Tato tlačítka sama nepřidávají epizody ani nesynchronizují Spotify.
- Automatické publikování do podcastových katalogů, RSS import a stahování epizod ze Spotify nejsou součástí této změny.

## Divadlo

Program upravujte na stránce Divadlo. Pro novou inscenaci, reportáž nebo záznam vytvořte příspěvek v rubrice Divadlo. Na stránce se automaticky objeví jeho karta. YouTube videa lze vložit do stránky i jednotlivých příspěvků, odkaz na celý kanál je volitelné pole na stránce. Kalendář rezervací ani prodej vstupenek tato verze neobsahuje.

## Chatník a ostatní požadavky z prezentace

Chatník používá již existující rubriku a články s nativními komentáři. Dede přidá diskusní příspěvek stejným způsobem jako dosud, v rubrice Chatník. Článek i diskuse zůstávají v archivu. Automatické vynechávání běžného článku jednou týdně není zapnuté; řídí se redakčním plánem.

Kontextové AI hledání je samostatný další úkol. Nynější hledání je běžné WordPress vyhledávání a nebude označované jako AI.

## Nahrání aktualizace

Na testovací subdoméně aktualizujte pouze složku **wp-content/themes/dedenik** z ZIPu `dedenik-0.3.0.zip`. Můžete použít Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu a potvrdit nahrazení stejné šablony, pokud to hosting dovoluje, nebo soubory překopírovat FTP. Neměňte wp-config.php, neimportujte znovu DB a nemažte uploads. Původní produkční Dedeník není cílem této aktualizace.
