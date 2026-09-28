# Kapitola 10: Implementace v Symfony 8

Ukázka ke kapitole [Implementace v Symfony 8](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony).
Registrace uživatele prochází celou cestou z knihy: formulář, command, command bus,
handler, agregát, Doctrine a zpět přes query bus k profilu.

## Spuštění

Stránka: [http://localhost:8000/examples/implementace](http://localhost:8000/examples/implementace)
(tabulku `ch04_users` založí `make install`, respektive `doctrine:migrations:migrate`).

Testy: `./vendor/bin/phpunit tests/Chapter04`

## Co ukázka obsahuje

- **Kontext `UserManagement` ve feature složkách** podle stromu v
  [10.02](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#project-structure):
  `Domain/`, `Infrastructure/`, `Registration/`, `Profile/`.
- **Agregát `User`** s privátním konstruktorem a továrnou `User::register()`
  ([10.03](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#entity-example-heading)).
  Událost `UserRegistered` nahrává továrna, ne konstruktor. Mapovací atributy
  Doctrine leží přímo na doménové třídě, `#[ORM\Version]` hlídá souběžné změny.
- **Hodnotové objekty** `Email` (konstruktor jen validuje, normalizaci dělá
  `fromUserInput()`), `UserName`, `HashedPassword` s privátním konstruktorem
  a `UserId` generovaný přes `Uuid::v7()`.
- **Custom typy Doctrine** pro `UserId` a `Email`; `UserName` a `HashedPassword`
  jsou embeddable
  ([10.07](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#doctrine-custom-types)).
  `UserIdType` na neočekávaný vstup hlasitě selže.
- **`UserRegistered` s primitivy.** Na rozdíl od událostí objednávky nese řetězce,
  protože ji odebírá i kontext Identity
  ([10.11](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#domain-event-example-heading)).
- **`RegisterUserHandler`** navázaný na jednu sběrnici. Repozitář jen volá `persist()`,
  handler flushuje uvnitř `try`, aby porušení unikátního indexu přeložil na
  `DuplicateEmailException`
  ([Race condition v naivní variantě](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#register-race-heading)).
  Události odesílá až po flushi na `event.bus`.
- **`GetUserProfileHandler`** ve verzi nad agregátem: čte přes repozitář a ven
  pouští DTO `UserProfile`.
- **Enum `OrderStatus`** s `allowedTransitions()` a výřez `Order::transitionTo()`
  z [10.08](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#php-enums)
  v kontextu `Ordering` (bez persistence, pokrývají ho testy). Kniha ho uvádí
  jako alternativu; kanonický `Order` s `markPaid()`, `ship()` a `cancel()` ukazuje
  `Chapter02_AggregateDesign`.

Test registrace běží nad skutečným EntityManagerem a SQLite v paměti. Schéma
zakládá tatáž migrace jako aplikace, takže unikátní index ověřuje reálná databáze,
ne mock.

## V čem se ukázka od knihy liší

- **Sběrnice bez middlewaru.** Kniha má `command.bus` s `validation`
  a `doctrine_transaction`. Sdílená konfigurace ukázek má `messenger.bus.command`
  a `messenger.bus.query` bez middlewaru. Handlery na ně míří stejně explicitně
  jako v knize (`#[AsMessageHandler(bus: …)]`). Dva důsledky: `flush()` v handleru
  zde rovnou i commitne (s middlewarem by zapsal SQL a commit by proběhl až po
  návratu handleru) a validaci commandu `RegisterUser` volá kontroler sám,
  než ho odešle.
- **Prefix `ch04_`.** Tabulka se jmenuje `ch04_users`, custom typy `ch04_user_id`
  a `ch04_email` (kniha: `users`, `user_id`, `email_vo`). Všechny ukázky sdílejí
  jednu databázi a jeden registr DBAL typů. Registrace typů a mapování je
  v `config/packages/chapter04_implementation.yaml`.
- **SQLite místo PostgreSQL** – kvůli spuštění bez dalších služeb.
- **Posluchač `RegisteredUserRecorder`** patří jen stránce ukázky: z události si
  vezme `userId` a kontroler podle něj přesměruje na profil. Kniha má na tomto
  místě posluchače z kontextu Identity, který zakládá přihlašovací záznam.
- **Jmenné prostory.** Kniha používá `App\UserManagement\…`, `App\Ordering\…`
  a `App\SharedKernel\…`, ukázka `App\Chapter04_Implementation\…` a `App\Shared\…`,
  aby se kapitoly v jednom repozitáři nepletly.

## Odkaz na příručku

[Implementace v Symfony 8](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony)
