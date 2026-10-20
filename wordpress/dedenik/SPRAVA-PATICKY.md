# Patička šablony Dedeník 0.3.1

Patička obsahuje logo, tři sloupce odkazů a spodní řádek. Nad ní je pruh s krajinou z dodaného mockupu. Citát je již součástí obrázku, proto se přes něj nepřidává další text. Na mobilu se citát zobrazuje jako čitelný text.

## Odkazy

Ve **Vzhled → Menu** vytvořte menu pro každý sloupec a přiřaďte ho do odpovídající pozice:

- **Patička – Rychlé odkazy:** Články, Témata, Autoři, Archiv. Původní přiřazení do pozice `footer` zůstává zachované.
- **Patička – Komunita:** Chatník, Jak se přidat, Pravidla, Kontakt.
- **Patička – Svět Dede:** Podcasty, Divadlo, Knihy, Další projekty.
- **Patička – Spodní odkazy:** například stránka Cookies. RSS se přidává automaticky, stejně jako odkaz na stránku ochrany osobních údajů vybranou v Nastavení → Soukromí.

Vybírejte skutečné stránky, rubriky nebo vlastní odkazy. Pro knihy lze použít `https://alzbetaadrak.cz/`. Dokud menu nejsou přiřazená, šablona nabízí základní funkční odkazy a existující veřejné stránky. Nevytváří neexistující stránky Pravidla nebo Kontakt ani falešné odkazy na podcastové platformy.

## Texty a logo

Ve **Vzhled → Přizpůsobit → Dedeník – patička** lze změnit nadpisy tří sloupců, text pod logem, počáteční rok copyrightu a vypnout pruh s krajinou. Logo se přebírá z identity webu; bez nastavení se použije logo z mockupu.

## Původní widgety

Widgety nejsou smazané. Oblast `widgets_footer` se zobrazuje pod sloupci, oddělená od spodního řádku. Doplňková oblast `widgets_body`, která bývala až pod patičkou, se přesunula nad pruh s krajinou, aby obsah nepokračoval pod závěrečným řádkem. Případný nadbytečný obsah lze upravit ve Vzhled → Widgety. Levý a pravý historický sloupec se touto změnou nemaže ani nevypíná.

## Aktualizace

Nahrajte pouze šablonu z `dedenik-0.3.1.zip` do testovací instalace Světa Dede. Balíček obsahuje také předchozí změny správy Podcastů a Divadla z verze 0.3.0. Neimportujte znovu databázi a neměňte wp-config.php ani uploads.
