# ddd-symfony-examples

Spustitelné ukázky Domain-Driven Design v Symfony 8.

Součást příručky **[DDD v Symfony](https://ddd-v-symfony.katuscak.cz/)**.

## Požadavky

- PHP 8.4+
- Composer
- [Symfony CLI](https://symfony.com/download)

## Spuštění

```bash
git clone https://github.com/MichalKatuscak/ddd-symfony-examples
cd ddd-symfony-examples
make install
symfony server:start
```

Přehled ukázek je na **http://localhost:8000/examples**.

## Obsah

Čísla kapitol odpovídají současnému číslování příručky. Adresáře ukázek mají
historická jména, která s ním nesouhlasí; rozhoduje sloupec Kapitola.

| Kapitola | Ukázka | Adresář | URL |
|---|---|---|---|
| 01 | [Co je Domain-Driven Design](https://ddd-v-symfony.katuscak.cz/co-je-ddd) – čistý doménový model, Bounded Context, ACL | `src/Chapter01_WhatIsDDD` | `/examples/co-je-ddd` |
| 06 | [Základní koncepty DDD](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty) – entita, hodnotové objekty, agregát, repozitář, doménová služba a události | `src/Chapter03_BasicConcepts` | `/examples/zakladni-koncepty` |
| 07 | [Návrh agregátu](https://ddd-v-symfony.katuscak.cz/navrh-agregatu) – kanonický agregát `Order` | `src/Chapter02_AggregateDesign` | jen testy |
| 08 | [Doplňující taktické vzory](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory) – Specification, Domain Service, Factory, Module | `src/Chapter12_LesserPatterns` | `/examples/mene-zname-vzory` |
| 10 | [Implementace v Symfony 8](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony) – Doctrine, doménové události | `src/Chapter04_Implementation` | `/examples/implementace` |
| 11 | [Autorizace v DDD](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd) – Voter a invariant agregátu | `src/Chapter10_Authorization` | jen testy |
| 12 | [CQRS](https://ddd-v-symfony.katuscak.cz/cqrs) – commandy, dotazy, Messenger | `src/Chapter05_CQRS` | `/examples/cqrs` |
| 13 | [Event Sourcing](https://ddd-v-symfony.katuscak.cz/event-sourcing) – event store, projekce | `src/Chapter06_EventSourcing` | `/examples/event-sourcing` |
| 14 | [Ságy a Process Managery](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery) – orchestrace, kompenzace | `src/Chapter07_Sagas` | `/examples/sagy` |
| 15 | [Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern) – spolehlivé publikování událostí | `src/Chapter11_OutboxPattern` | `/examples/outbox` |
| 17 | [Testování DDD](https://ddd-v-symfony.katuscak.cz/testovani-ddd) – unit testy domény | `src/Chapter08_Testing` | `/examples/testovani` |
| 18 | [Migrace z CRUD na DDD](https://ddd-v-symfony.katuscak.cz/migrace-z-crud) – invarianty místo setterů | `src/Chapter09_Migration` | `/examples/migrace-z-crud` |

Ostatní kapitoly ukázku nemají záměrně. Strategické a procesní kapitoly stojí na
rozhodnutích a diagramech, ne na kódu. Srovnávací a průřezové kapitoly ukazují buď vědomé
anti-vzory, nebo varianty modelu, který už spustitelně předvádí některá ukázka výše.

## Testy

```bash
make test
```

Testy jedné kapitoly:

```bash
./vendor/bin/phpunit tests/Chapter03
```

Adresáře testů kopírují jména adresářů ukázek (`tests/Chapter03` patří k `src/Chapter03_BasicConcepts`).

## Vztah k příručce

Ukázky sledují kanonické konvence příručky:

- `AggregateRoot` s `record()` a `releaseEvents()`; události se zaznamenávají v továrnách
  a doménových metodách, nikdy v konstruktoru.
- Události v minulém čase bez přípony „Event“ (`OrderPlaced`), identita jako hodnotový
  objekt, čas vzniku ve vlastnosti `occurredAt`.
- Identifikátory jako hodnotové objekty nad `Uuid::v7()` z `symfony/uid`.
- `Money` s `amountInCents` a měnou `Currency` jako string-backed enum.
- Doménová pravidla jako pojmenované výjimky s továrnami
  (`InvalidOrderStateTransitionException::cannotTransition()`).
- Agregáty se odkazují jen přes identitu.

Sdílené třídy leží v `src/Shared/Domain`, v knize v `App\SharedKernel\Domain`.
`AggregateRoot`, `Money` a `Currency` přebírají definice ze
[Základních konceptů](https://ddd-v-symfony.katuscak.cz/zakladni-koncepty#money).
Dvě sdílené třídy kniha nemá:

- `DomainEvent` je rozhraní s metodou `occurredAt()`. Používají ho ukázky, jejichž event
  store, projekce nebo outbox potřebují čas události přečíst bez znalosti konkrétní třídy.
  Kanonické události knihy předka nemají a čas nesou ve veřejné vlastnosti `$occurredAt`.
- `Exception\DomainRuleViolation` je společný předek doménových výjimek. Dědí
  z `\DomainException`, takže pro volajícího platí totéž co v knize.

Kde se ukázka od knihy liší jinak (běh v paměti nebo nad SQLite místo PostgreSQL,
synchronní zpracování místo workeru, vynechané Doctrine mapování), říká to README
příslušné kapitoly, vždy s důvodem.
