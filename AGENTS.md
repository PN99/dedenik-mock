# Práce s agentem na Dedeníku

Výchozí větev `main` slouží pro vývoj a testování na [Světě Dede](https://svetdede.dedenik.cz/). Šablona je v `wordpress/dedenik/`. Pracovní větev založ z aktuální `origin/main`. Po sloučení změny šablony do `main` ji testovací workflow nasadí na Svět Dede.

Pro změny na [dedenik.cz/new](https://dedenik.cz/new/) založ pracovní větev z aktuální `origin/production`. V této větvi najdeš šablonu pod `wp-content/themes/dedenik/`. Pro nasazení otevři pull request s cílem `production`. Po sloučení vyčkej na běh **Dedeník /new** a ověř dotčenou stránku přes HTTPS. Podrobné pokyny včetně prvního klonu a příkladu zadání jsou v [návodu ve větvi production](https://github.com/PN99/dedenik-mock/blob/production/AGENTS.md).
