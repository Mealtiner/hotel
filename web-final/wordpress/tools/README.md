# Generátor Divi JSON layoutů

1. `php render.php` — vyrenderuje HTML všech sekcí (stub WP funkcí, fallback texty ze shortcodes.php) do `out/`
2. `python3 assemble.py` — složí `out/*.html` do `../divi-json/*.json` (1 designová sekce = 1 Divi sekce s Text modulem)

Po změně textů ve `shortcodes.php` (fallbacky) spusť oba kroky.
Pozn.: texty lze měnit i přímo v JSONu / ve Visual Builderu — generátor je pro hromadnou regeneraci.
