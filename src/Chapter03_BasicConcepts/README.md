# Kapitola 3: Základní koncepty DDD

Tato ukázka demonstruje Entity, Value Objects, Agregát, Domain Events a Domain Service v praxi.

## Spuštění

Otevři [http://localhost:8000/examples/zakladni-koncepty](http://localhost:8000/examples/zakladni-koncepty)

## Co se naučíš

- Entity s identitou (`Order`, `OrderId`) a jejich doménová pravidla
- Value Objects (`Money`, `Email`) — immutabilní, validované v konstruktoru
- Agregát jako ochránce invariantů — `Order.confirm()` zamítne prázdnou objednávku
- Domain Events — agregát zaznamenává co se stalo, volající si eventy vyzvedne přes `pullEvents()`
- Domain Service — `OrderConfirmationService` koordinuje agregát a repozitář

## Odkaz na příručku

[Základní koncepty DDD](https://ddd-symfony.cz/zakladni-koncepty)
