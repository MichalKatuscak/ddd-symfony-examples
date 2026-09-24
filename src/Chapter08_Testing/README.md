# Kapitola 17: Testování DDD kódu v Symfony

Unit testy doménové vrstvy tak, jak je píše kapitola: bez kernelu, bez databáze,
jen PHPUnit a doménové třídy. Testy míří na kanonické modely knihy – `Order`
z kapitoly Návrh agregátu (ukázka `Chapter02_AggregateDesign`) a `User` z kapitoly
Implementace v Symfony (ukázka `Chapter04_Implementation`). Vlastní doménový kód
tato ukázka nemá, jen testy a testovací pomocníky.

## Spuštění

```bash
./vendor/bin/phpunit tests/Chapter08 --testdox
```

Přehled testů je i na [http://localhost:8000/examples/testovani](http://localhost:8000/examples/testovani).

## Co ukázka obsahuje

- Test hodnotového objektu `Email` s data providerem zapsaným atributem
  `#[DataProvider]` (PHPUnit 12+ doc-komentáře nečte).
- Test entity `User`: registrace nahraje právě jednu `UserRegistered` s primitivy,
  `rename()` a `changeEmail()` se stejnou hodnotou nic nemění a nic nenahrávají.
- Test agregátu `Order`: události v pořadí `OrderPlaced` → `OrderItemAdded` →
  `OrderConfirmed`, prázdnou objednávku nejde potvrdit, potvrzenou podruhé taky ne.
- Trait `DomainEventAssertions` a jeho použití v `OrderEventsTest`.
- Fake `InMemoryUserRepository` a test handleru registrace nad ním.
- Test Data Builder `OrderBuilder` s `anOrder()` a krátkým testem, který ho používá.

## V čem se ukázka od knihy liší

- **Jen unit vrstva pyramidy.** Integrační testy s Doctrine (17.05), funkční testy přes
  `WebTestCase` (17.06), testy Messengeru (17.07) a architektonické testy (17.08) ukázka
  nemá. Potřebovaly by testovací databázi, fixture a přihlašování, které sdílený
  repozitář ukázek pro testy nepřipravuje; unit testy běží bez čehokoli z toho. Chybí i given-when-then test event-sourcovaného agregátu (17.03): stojí na bázové
  třídě z kapitoly Event Sourcing, a ta patří do ukázky `Chapter06_EventSourcing`.
- **Handler registrace se volá přímo.** Test sestaví `RegisterUserHandler` z ukázky
  `Chapter04_Implementation` s fake repozitářem a stuby `EntityManagerInterface`
  a `MessageBusInterface` – přesně jako v knize. Sběrnice ani databáze se neúčastní.
- **Jmenné prostory.** Kniha používá `App\UserManagement\…`, `App\Ordering\…`
  a `App\SharedKernel\…`, testy importují `App\Chapter04_Implementation\UserManagement\…`,
  `App\Chapter02_AggregateDesign\…` a `App\Shared\…`. Testy samy leží
  v `App\Tests\Chapter08\…`.

## Odkaz na příručku

[Testování DDD kódu v Symfony](https://ddd-v-symfony.katuscak.cz/testovani-ddd)
