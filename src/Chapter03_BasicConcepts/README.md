# Kapitola 6: Základní koncepty DDD

Ukázka ke kapitole [Základní koncepty DDD](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty).
Obsahuje taktické stavební bloky v podobě „bez perzistence“, jak je kapitola zavádí:
entitu, hodnotové objekty, agregát, repozitář, doménovou službu a doménové události.

## Co ukázka obsahuje

- **Entita** `User` s identitou `UserId`. Rovnost stojí jen na identitě: `equals()`
  vrátí `true` i poté, co jedna instance změní e-mail, zatímco `==` a `===` ne
  ([06.03](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty#entities)).
- **Identifikátory** `OrderId`, `CustomerId`, `ProductId` a `UserId` mají stejný tvar:
  UUID v7 z `symfony/uid`, validace v konstruktoru, `generate()`, `fromString()`,
  `equals()` a `__toString()`.
- **Hodnotové objekty.** `Email` v konstruktoru jen validuje, vstup z formuláře normalizuje
  `Email::fromUserInput()`. `Money` a `Currency` jsou sdílené třídy z `App\Shared\Domain`
  ([06.04](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty#money)).
- **Agregát** `Order` s privátním konstruktorem a továrnou `Order::place(OrderId, CustomerId)`.
  Položky přidává a odebírá jen v `Draft`, potvrzení prázdné objednávky odmítne
  `EmptyOrderException`, nepovolený přechod `InvalidOrderStateTransitionException`.
  Storno `cancel(string $reason, \DateTimeImmutable $when)` odmítne jen odeslanou
  a doručenou objednávku. Součet `totalAmount()` přebírá měnu z první položky a prázdnou
  objednávku odmítne, místo aby vrátil tichou nulu.
- **Repozitář** `OrderRepository` je úzký: `save()` a `get()`. Chybějící objednávka
  skončí `OrderNotFoundException`, ne `null`. Implementace `InMemoryOrderRepository`
  slouží testům i webové ukázce.
- **Doménová služba** `ShippingFeeService` spojuje data dvou agregátů (`Order`, `Customer`):
  doprava zdarma pro VIP zákazníky a objednávky s pěti a více položkami. Nedrží stav
  a nezávisí na repozitáři.
- **Doménové události** `OrderPlaced`, `OrderItemAdded` a `OrderConfirmed`. Agregát je
  zaznamená v továrně a doménových metodách přes `record()` ze sdíleného `AggregateRoot`,
  nikdy v konstruktoru. Controller objednávku nejdřív uloží do repozitáře a teprve pak
  vyzvedne události přes `releaseEvents()`
  ([06.09](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty#aggregate-root-lifecycle)).

## V čem se ukázka od knihy liší

- **Bez event busu a transakce.** Kniha v sekci 06.09 ukazuje handler, který po `flush()`
  pošle události na `event.bus`. Ukázka nemá databázi ani Messenger, a tak události po
  uložení do repozitáře v paměti jen vypíše. Plné zapojení ukazuje ukázka kapitoly
  Implementace DDD v Symfony 8 (`Chapter04_Implementation`).
- **`cancel()` nevydává událost.** Stejně jako v knize: podoba ze Základních konceptů
  důvod a čas storna jen přijímá. Událost `OrderCancelled` a další přechody
  (`markPaid()`, `ship()`) má plná verze agregátu v `Chapter02_AggregateDesign`.
- **`Payment::forOrder()`** ze sekce o zneužití doménové služby je v knize jen výřez
  a ukázka ho nepřebírá.
- **Jmenné prostory.** Kniha třídy rozkládá do `App\UserManagement\…`,
  `App\Ordering\…` a `App\SharedKernel\…`. Ukázka je drží pod
  `App\Chapter03_BasicConcepts\…` a `App\Shared\Domain\…`, aby se kapitoly v jednom
  repozitáři nepletly.

## Spuštění

Otevřete [http://localhost:8000/examples/zakladni-koncepty](http://localhost:8000/examples/zakladni-koncepty).

Testy:

```bash
./vendor/bin/phpunit tests/Chapter03
```
