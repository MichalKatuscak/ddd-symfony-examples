# Kapitola 7: Návrh agregátu

Kanonický agregát `Order`, na který se odkazuje zbytek příručky. Ostatní kapitoly
ho zjednodušují nebo rozšiřují; tady je v úplné podobě.

## Co se naučíš

- Hranice konzistence: položky patří dovnitř agregátu, zákazník a produkt jen identitou
- Uzavřený stavový graf — cesty, které v něm nejsou, jsou zakázané, ne „neimplementované“
- Invariant „jedna položka na produkt“ vymáhaný uvnitř `addItem()`
- Asymetrická viditelnost `public private(set)` místo getterů
- Semantic lock: dokud nad objednávkou běží proces, uživatel do ní nesáhne

## Spuštění testů

```bash
php bin/phpunit --filter Chapter02
```

## Odkaz na příručku

[Návrh agregátu](https://ddd-v-symfony.katuscak.cz/navrh-agregatu)
