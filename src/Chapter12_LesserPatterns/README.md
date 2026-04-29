# Kapitola 12: Méně známé taktické vzory

Tato ukázka demonstruje čtyři často přehlížené taktické vzory DDD: Specification Pattern, Domain Services, Factories a Modules.

## Spuštění

Otevři [http://localhost:8000/examples/mene-zname-vzory](http://localhost:8000/examples/mene-zname-vzory)

## Co se naučíš

- Specification Pattern — kompozice booleovských pravidel pomocí `and()`, `or()`, `not()` místo zamotaných `if`-ů
- Domain Service — kde umístit logiku, která nemá přirozeného vlastníka mezi entitami (klasický `MoneyTransferService` mezi dvěma účty)
- Factory — kdy stačí static method (named constructor `Order::place()`) a kdy je potřeba samostatná Factory class s DI závislostmi
- Modules — organizace kódu podle ubiquitous language (`Domain/`, `Application/`, `Infrastructure/`, `UI/`) místo technického `Entity/`, `Service/`, `Repository/`

## Odkaz na příručku

[Méně známé taktické vzory v Symfony](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory)
