# Přehled komentářů podle přezdívky (0.6.2)

Jméno autora schváleného komentáře v diskusi vede na jeho veřejný přehled, například `/?diskutujici=YGA`. Výpis ukazuje počet komentářů, datum, název článku, úryvek a odkaz přímo na komentář. Řadí se od nejnovějších a stránkuje po 20. Další stránka používá `&disk_strana=2`. Odkaz lze sdílet a funguje bez JavaScriptu.

Využívá již hotový index diskusí. Není potřeba vytvářet stránky v administraci, obnovovat permalinky, měnit databázové schéma ani znovu indexovat komentáře. Výpis nepotřebuje AI klíč a nečerpá API limit. Při vypnutém chytrém hledání funguje dál, pokud je index diskusí připravený.

Komentáře se seskupují podle veřejné přezdívky stejně jako v indexu diskusí (bez rozlišení velikosti a diakritiky). Nejde o ověřený osobní profil: shodnou přezdívku může používat více lidí. E-maily, IP adresy ani ID účtů se pro spojování nepoužívají a nezobrazují se.

Zobrazují se pouze schválené komentáře u aktuálně veřejných článků/stránek bez hesla. Moderace a změny viditelnosti se ověřují i při otevření výpisu. Neznámé přezdívky a neexistující stránky mají HTTP 404. Není-li index připravený, vrací přehled HTTP 503 a jména v diskusích se nemění na nové odkazy. Administrace a RSS odkazy se nemění.

Lokální ověření: `php -c local/php.ini mockup/wordpress/tests/discussants-integration.php C:/Projekty/Dedenik/local/site/wp-load.php`. Vyžaduje plně naplněný index v izolované lokální DB, testuje veřejnost, moderaci, stránkování, parametry URL i přezdívky s diakritikou a speciálními znaky. Nevolá Google.
