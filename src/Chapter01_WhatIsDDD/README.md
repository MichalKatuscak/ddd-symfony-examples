# Kapitola 1: Co je DDD

Ukázka ke kapitole [Co je Domain-Driven Design?](https://ddd-v-symfony.katuscak.cz/co-je-ddd).
Kapitola pojmy jen zavádí a kód skoro nemá. Ukázka proto pojmy předvádí na malém
e-shopu, bez databáze a bez závislosti na frameworku.

## Co ukázka obsahuje

- Entitu `Product` s identitou `ProductId` (UUID v7).
- Hodnotový objekt `Money` ze sdíleného `App\Shared\Domain`: částka v haléřích
  a měna jako enum `Currency`, stejně jako v celé příručce.
- Doménovou logiku košíku v `Cart`, bez frameworku a databáze.
- Bounded Context: tentýž produkt jako `CatalogProduct` (popis, sklad, hmotnost)
  a jako `OrderProduct` (cena, sazba DPH). Společná je jen identita.
- Anti-Corruption Layer `CatalogProductTranslator`, který z modelu katalogu přeloží
  jen to, co potřebuje kontext Objednávky.
- Shared Kernel: `ProductId` sdílejí oba kontexty, a tak se musí shodnout na jeho formátu.

## V čem se ukázka od knihy liší

- **Jiný příklad Bounded Contextu.** Kniha v sekci
  [Bounded Context: hranice platnosti modelu](https://ddd-v-symfony.katuscak.cz/co-je-ddd#bounded-context)
  ukazuje zákazníka v kontextech Ordering a Support. Ukázka volí produkt v katalogu
  a v objednávce, protože na něm jde zároveň předvést košík a překlad cen.
- **Anti-Corruption Layer ve složce `Domain/ContextMap`.** V reálném projektu patří
  překladač na hranici přijímajícího kontextu, typicky do jeho infrastruktury. Ukázka ho
  drží vedle obou modelů, aby byl celý překlad vidět na jednom místě.

## Spuštění

```bash
symfony server:start
# http://localhost:8000/examples/co-je-ddd
```

Testy:

```bash
./vendor/bin/phpunit tests/Chapter01
```
