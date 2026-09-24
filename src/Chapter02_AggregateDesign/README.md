# Kapitola 7: Návrh agregátu

Kanonický agregát `Order` z kapitoly
[Návrh agregátu](https://ddd-v-symfony.katuscak.cz/navrh-agregatu#references-by-id),
na který se odkazuje zbytek příručky. Ostatní kapitoly ho zjednodušují nebo rozšiřují;
zde je v úplné doménové podobě. Ukázka nemá webové rozhraní, běží jako sada unit testů.

## Co ukázka obsahuje

- Hranici konzistence: položky patří dovnitř agregátu, zákazník a produkt jen identitou.
  `ShipmentId` bydlí ve vlastním jmenném prostoru `Domain\Shipping`, protože patří cizímu kontextu.
- Dvě továrny: kanonické `place(OrderId, CustomerId)` a `placeWithFirstItem()`, které
  vymáhá invariant „alespoň jedna položka“ signaturou a objednávku rovnou potvrdí.
- Uzavřený stavový graf `Draft → Confirmed → Paid → Shipped` se stornem ze všech stavů
  kromě `Shipped` a `Delivered`. Cesty, které v grafu nejsou, jsou zakázané.
- Událost na každé hraně grafu: `OrderPlaced`, `OrderItemAdded`, `OrderConfirmed`,
  `OrderPaid`, `OrderShipped`, `OrderCancelled`. Každá nese identitu jako hodnotový
  objekt a čas `occurredAt`.
- Idempotentní větve pro opakované doručení: `markPaid()` na zaplacené, `ship()` na
  odeslané a `cancel()` na stornované objednávce nic neudělají a žádnou událost nevydají.
- Invariant „jedna položka na produkt“ vymáhaný v `addItem()`.
- Asymetrickou viditelnost `public private(set)` místo getterů.
- Semantic lock: dokud nad objednávkou běží proces, uživatel ji nestornuje
  (`OrderLockedBySagaException`).

## V čem se ukázka od knihy liší

- **Bez Doctrine mapování.** Kniha v sekci
  [Mapování v Symfony 8 a Doctrine ORM 3](https://ddd-v-symfony.katuscak.cz/navrh-agregatu#symfony-doctrine)
  drží položky v `Collection` a `OrderItem` dává náhradní `int` identitu a zpětnou
  referenci na `Order`, protože je Doctrine potřebuje pro `ManyToOne`. Ukázka testuje
  jen doménová pravidla, a tak položky drží v poli a `OrderItem` referenci na kořen nemá.
  Metody a pravidla jsou stejné jako v knize.
- **`items()`.** Kanonický výpis v kapitole 7 metodu nemá, převzatá je ze
  [Základních konceptů](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty#aggregates).
  Testy přes ni ověřují invariant „jedna položka na produkt“.
- **`isCancellable()` bez parametru.** Kapitola 7 tuto metodu nemá. Používá ji ukázka
  kapitoly [Autorizace v DDD](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd)
  (`Chapter10_Authorization`), která na tomto agregátu staví. Verze se storno lhůtou
  a parametrem `$now` patří do té kapitoly.
- **Výjimky** dědí ze sdíleného `App\Shared\Domain\Exception\DomainRuleViolation`,
  který sám dědí z `\DomainException`. Kniha je nechává dědit z `\DomainException`
  přímo; pro volajícího se nic nemění.
- **Jmenné prostory.** Kniha rozkládá třídy do `App\Ordering\Domain\…`,
  `App\Shipping\Domain\…` a `App\SharedKernel\Domain\…`. Ukázka je drží pod
  `App\Chapter02_AggregateDesign\Domain\…` a `App\Shared\Domain\…`, aby se kapitoly
  v jednom repozitáři nepletly.

## Spuštění testů

```bash
./vendor/bin/phpunit tests/Chapter02
```

## Odkaz na příručku

[Návrh agregátu](https://ddd-v-symfony.katuscak.cz/navrh-agregatu)
