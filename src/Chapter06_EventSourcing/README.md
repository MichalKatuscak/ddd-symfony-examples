# Kapitola 13: Event Sourcing

Ukázka ke kapitole [Event Sourcing](https://ddd-v-symfony.katuscak.cz/event-sourcing).
Objednávka se neukládá jako řádek, ale jako stream událostí. Repozitář stream
načte a agregát z něj stav přehraje.

## Spuštění

Stránka: [http://localhost:8000/examples/event-sourcing](http://localhost:8000/examples/event-sourcing)
(tabulky zakládá `make install`, případně `php bin/console doctrine:migrations:migrate`).

Na stránce jde objednávku založit, přidat položku, potvrdit a odeslat. Tlačítko
„Dva souběžné zápisy do téže verze“ načte agregát dvakrát a oba zapíše; druhý
zápis skončí `ConcurrencyException`.

Testy: `./vendor/bin/phpunit tests/Chapter06`

## Co ukázka obsahuje

- `DomainEvent` s `eventId` a `occurredAt` jako public readonly vlastnostmi.
  Událost vzniká pojmenovaným konstruktorem `create()`, z Event Store se skládá
  přes `fromPayload()` a identitu ani čas přitom negeneruje znovu
  ([13.04](https://ddd-v-symfony.katuscak.cz/event-sourcing#domain-event-php-heading)).
- `eventType()` ve formátu `<bounded_context>.<podstatné_jméno>_<sloveso>`,
  například `ordering.order_placed`.
- `EventSourcedAggregate` s `recordEvent()`, `reconstituteFromEvents()`,
  `recordedEvents()` a `releaseEvents()`. Metody `apply*()` v `Order` jsou
  `protected`, protože je bázová třída volá dynamicky
  ([13.06](https://ddd-v-symfony.katuscak.cz/event-sourcing#es-aggregate-base-heading)).
- `EventStore` s metodami `append()`, `loadStream()` a `loadAll()`;
  `DoctrineEventStore` nad DBAL překládá porušení unikátního indexu
  `(aggregate_id, version)` na `ConcurrencyException`
  ([13.05](https://ddd-v-symfony.katuscak.cz/event-sourcing#event-store-php-heading)).
- `EventSourcedOrderRepository` vyjme události z agregátu až po úspěšném zápisu,
  takže při konfliktu verzí nezmizí.
- `OrderSummaryProjector` s metodami `handle*()` registrovanými na `event.bus`.
- `RequestEventMetadataProvider` zapisuje do metadat correlation, causation a user ID.
  Bez navázaného kontextu (konzole, test) zůstává `correlationId` `null`: fallback
  na `eventId` by dal každé události vlastní korelaci a řetěz příčin by se rozpadl.

## Čím se ukázka liší od knihy a proč

**SQLite a prefix tabulek.** Kniha píše DDL pro MySQL 8 a tabulky jmenuje
`event_store` a `order_summary`. Ukázky sdílejí jednu SQLite databázi, a proto
tabulky nesou prefix `ch06_` a vznikají migrací `Version20260924130600`
v dialektu SQLite. Sloupce a indexy odpovídají knize.

**Mapa typů v atributu.** Kniha registruje `typeMap` serializeru
v `services.yaml`. Ten soubor sdílejí všechny kapitoly ukázek, takže mapa žije
v atributu `#[Autowire]` přímo u konstruktoru `EventSerializer`.

**Synchronní projekce.** V knize čte nové řádky z `event_store` relay a posílá
je na asynchronní transport `async_events` s frontou `events` – stejné jméno
jako v kanonické konfiguraci z kapitoly CQRS ([13.08](https://ddd-v-symfony.katuscak.cz/event-sourcing#es-outbox-heading)).
Ukázka asynchronní transport nemá. Controller proto po úspěšném zápisu pošle
události na `event.bus` sám a projekce se aktualizuje ve stejném requestu –
kompromis, který kniha popisuje v sekci
[Synchronní projekce](https://ddd-v-symfony.katuscak.cz/event-sourcing#ec-note-heading).

**Chybějící stream.** `EventSourcedOrderRepository::load()` v knize hází holou
`\DomainException`. Ukázka používá pojmenovanou `OrderNotFoundException`,
jak to kniha jinak předepisuje pro doménová pravidla.

**Co ukázka vynechává.** Upcasting (`UpcasterChain`), snapshoty, idempotentní
projektor s tabulkou `projection_checkpoint` a příkaz pro rebuild projekcí.
Všechny události jsou ve verzi schématu 1 a stream objednávky má jednotky
událostí, takže by tyto části neměly co ukázat. `loadAll()` pro rebuild
v Event Store zůstává.

## Proč se agregát liší od kanonického

Event-sourcovaný agregát nedrží stav, ale odvozuje ho přehráním událostí. Proto
má jinou stavbu než kanonický `Order` z ukázky ke kapitole o návrhu agregátu:
vlastní namespace, primitivní identifikátory a gettery místo `public private(set)`.
Rozdíl je vlastnost vzoru a kniha ho vysvětluje stejně.

## Odkaz na příručku

[Event Sourcing](https://ddd-v-symfony.katuscak.cz/event-sourcing)
