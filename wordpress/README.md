# WordPress šablona Dedeník

Zdroj šablony je v adresáři [dedenik](dedenik/README.md). Kořenový HTML/CSS/JS mockup zůstává samostatnou ukázkou; WordPress jej ani content.js nenačítá.

Vytvoření instalačního ZIP (standardní Python 3, bez dalších knihoven):

```powershell
python wordpress/package-theme.py
```

Výsledek: `dist/dedenik-0.1.0.zip` v kořeni repozitáře. ZIP obsahuje jen šablonu, nikoli databázi, WordPress jádro nebo testovací nástroje.

Ověření (vlastní dostupné PHP):

```powershell
php -n wordpress/tests/theme-behavior.php
Get-ChildItem wordpress/dedenik -Recurse -Filter *.php | ForEach-Object { php -n -l $_.FullName }
```

Offline kontroly nenahrazují integrační test na izolované kopii existujícího WordPressu.
