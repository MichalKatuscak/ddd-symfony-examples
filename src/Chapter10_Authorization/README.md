# Kapitola 11: Autorizace v DDD

Tři vnitřní vrstvy autorizace z kapitoly jako spustitelný kód: Voter (use case),
handler s identitou aktéra v příkazu (asynchronní kontext) a agregát, který sám
vymáhá stav a storno lhůtu. K tomu ABAC politika a její tabulkový test.

## Spuštění testů

```bash
./vendor/bin/phpunit tests/Chapter10 --testdox
```

## Co ukázka obsahuje

- **Agregát** `Domain\Order\Order` – kanonický `Order` s verzí `cancel()`
  a `isCancellable($now)` ze sekce
  [Aggregate-level](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd#aggregate-level).
  Storno odmítne jen odeslanou nebo doručenou objednávku
  (`InvalidOrderStateTransitionException`) a objednávku potvrzenou před víc než 24 h
  (`CancellationWindowExpiredException`). Zaplacenou objednávku stornovat jde, jinak by
  neprošla kompenzace ságy. Opakované storno nic nedělá, zámek ságy storno blokuje.
- **Voter** `OrderVoter` – skutečný Symfony `Voter` s atributy `order.view`,
  `order.cancel` a `order.refund`. Vlastnictví se ptá agregátu (`isOwnedBy()`), role
  ověřuje přes `AccessDecisionManagerInterface::decide()`. Při zamítnutí storna zapíše
  důvod do `Vote::$reasons`.
- **Handler** `CancelOrderHandler` – asynchronní varianta z kapitoly: autorizuje proti
  `actorId` v `CancelOrderCommand`, systémová identita (`SystemActor`) má vlastní větev
  a sama uvolní zámek ságy. Po uložení vyzvedne události a pošle je na event bus.
- **ABAC** `CancelOrderPolicy` nad ExpressionLanguage (`PolicyEvaluator`) s pravidly
  vlastník, stav `"confirmed"` a lhůta 24 h a tabulkový test podle sekce
  [Test pyramida pro autorizaci](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd#testing).
- **Testovací pomocníci** `OrderFactory` a `SecurityUserFixture` ve stejné podobě jako v knize.

## V čem se ukázka od knihy liší

- **Vlastní třída `Order`.** Kniha výřezem v 11.06 nahrazuje v kanonickém agregátu dvě
  metody. Kanonický `Order` z ukázky `Chapter02_AggregateDesign` má stav zapisovatelný jen
  zevnitř (`private(set)`), podtřída by ho přepnout nemohla. Ukázka proto nese kopii
  s nahrazenými `cancel()` a `isCancellable()`; hodnotové objekty, události i výjimky
  importuje z `Chapter02`, nekopíruje je.
- **Bez Doctrine a HTTP vrstvy.** `SecurityUser` není Doctrine entita, repozitář je
  v testech in-memory a `EntityManagerInterface` handleru je stub. Firewall, `access_control`
  s `form_login` a `enable_csrf`, `#[IsGranted]` na controlleru, `OrderValueResolver`,
  `DomainExceptionListener`, read modely (11.07), multi-tenancy (11.09) a end-to-end test
  ukázka nemá – potřebovaly by přihlašování, databázi a routy, které repozitář ukázek nenabízí.
- **Handler bez `#[AsMessageHandler(bus: 'command.bus')]`.** Command bus se v tomto
  projektu jmenuje `messenger.bus.command` a repozitář objednávek existuje jen v testech
  (in-memory). Handler proto volá jen test, se sběrnou atrapou event busu; v kontejneru
  by dostal `event.bus` přes `#[Target('event.bus')]` jako v knize. Synchronní varianta handleru
  s `AuthorizationCheckerInterface` v ukázce není: kniha sama říká, že do projektu jde
  jen asynchronní.
- **Důvody zamítnutí ve Voteru.** Kniha je ukazuje ve výřezu v 11.08, který navíc volá
  `isCancellable()`. Ukázka důvod zapisuje jen u vlastnictví a stav agregátu ve Voteru
  nekontroluje – tak to chce 11.04.
- **Jmenné prostory.** Kniha rozkládá třídy do `App\Ordering\…`, `App\Identity\…`
  a `App\SharedKernel\…` (`SystemActor`, `Policy`, `Rule`, `PolicyContext`,
  `PolicyEvaluator`). Ukázka je drží pod `App\Chapter10_Authorization\…`.
- **Výjimky** dědí ze sdíleného `App\Shared\Domain\Exception\DomainRuleViolation`
  (potomek `\DomainException`); kniha dědí z `\DomainException` přímo.

## Odkaz na příručku

[Autorizace v DDD](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd)
