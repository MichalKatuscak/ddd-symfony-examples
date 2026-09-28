# Kapitola 18: Migrace z CRUD na DDD

Týž `User` před migrací a po ní. Vlevo anemická CRUD entita se settery, vpravo cílový
stav z kapitoly: agregát s aktivací účtu, hodnotový objekt `Email`, command
`RegisterUser` s handlerem a Anti-Corruption Layer nad legacy řádkem.

## Spuštění

Otevřete [http://localhost:8000/examples/migrace-z-crud](http://localhost:8000/examples/migrace-z-crud).

```bash
./vendor/bin/phpunit tests/Chapter09 --testdox
```

## Co ukázka obsahuje

- `CrudVersion\User` – výchozí stav: gettery a settery, stav jako řetězec, žádné pravidlo.
- `UserManagement\Domain\Model\User` – rozšíření kanonického `User` z kapitoly
  [Implementace v Symfony 8](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony#entity-example-heading).
  Vlastnosti `createdAt` a `hashedPassword` i getter `hashedPassword()` zůstávají,
  přibývá aktivační model: `UserStatus`, `VerificationToken`, `activate()` a událost
  `UserActivated`. Druhá aktivace skončí `UserAlreadyActivatedException::forUser()`,
  cizí token `InvalidVerificationTokenException::forUser()`.
- `User::register()` nahrává `UserRegistered`, `User::reconstitute()` nenahrává nic
  a přebírá datum registrace i token.
- `Email` validuje v konstruktoru; normalizace a zakázané domény (`mailinator.com`,
  `guerrillamail.com`) patří do `Email::fromUserInput()`, aby šly načíst legacy řádky.
- `LegacyUserTranslator` s vyjmenovaným mapováním stavů; neznámý stav hlasitě selže
  (`UnmappableLegacyStatusException`).
- `RegisterUser` se stejným FQCN a poli jako v kapitole Implementace v Symfony 8
  (`UserManagement\Registration\Command`). Handler generuje identitu přes
  `UserId::generate()` (UUID v7) a duplicitu e-mailu nehlídá dotazem, ale unique
  constraintem přeloženým na `DuplicateEmailException`.

## V čem se ukázka od knihy liší

- **Bez Doctrine.** Kniha mapuje `User` atributy na tabulku `um_users` a registruje
  typ `VerificationTokenType`. Ukázka nic neukládá, a tak mapování, Doctrine typ,
  `DoctrineUserRepository` ani backfill nemá. Testy handleru pracují s fake
  repozitářem a stubem `EntityManagerInterface`, stejně jako kapitola
  [Testování DDD](https://ddd-v-symfony.katuscak.cz/testovani-ddd#test-doubles).
- **Handler bez `#[AsMessageHandler(bus: 'command.bus')]`.** Sběrnice se v tomto
  projektu jmenují `messenger.bus.*` a Doctrine repozitář uživatelů chybí; handler proto
  volá jen test.
- **Charakterizační testy** (18.09) potřebují běžící legacy endpoint `/users/register`,
  který ukázka nemá. Porovnání „před a po“ proto nese `CrudComparisonTest`.
- **`ForbiddenEmailDomainException`.** Kniha výjimku v `Email::fromUserInput()` používá,
  ale nevypisuje. Ukázka ji definuje se stejnou stavbou jako ostatní výjimky kontextu:
  pojmenovaná továrna `forDomain()`.
- **Výjimky** dědí ze sdíleného `App\Shared\Domain\Exception\DomainRuleViolation`
  (potomek `\DomainException`); kniha dědí z `\DomainException` přímo.
- **Jmenné prostory.** Kniha používá `App\UserManagement\…` a `App\Entity\User`,
  ukázka `App\Chapter09_Migration\UserManagement\…` a `App\Chapter09_Migration\CrudVersion\User`.
  V knize jde o tytéž třídy jako u kanonického `User`, jen rozšířené. Ukázka proto
  hodnotové objekty `UserId`, `UserName` a `HashedPassword` importuje z ukázky
  `Chapter04_Implementation` a vlastní verzi má jen tam, kde rozšíření něco mění:
  `Email` (s `domain()` a zakázanými doménami) a na něj typované `UserRegistered`
  a `DuplicateEmailException`.

## Odkaz na příručku

[Migrace z CRUD na DDD](https://ddd-v-symfony.katuscak.cz/migrace-z-crud)
