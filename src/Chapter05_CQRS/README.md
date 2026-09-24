# Kapitola 12: CQRS

Ukázka ke kapitole [CQRS](https://ddd-v-symfony.katuscak.cz/cqrs). Zápis a čtení
jdou oddělenými cestami: příkaz `PlaceOrder` mění write model, projektor z události
plní denormalizovanou tabulku a dotaz `ListOrders` čte jen z ní.

## Spuštění

Stránka: [http://localhost:8000/examples/cqrs](http://localhost:8000/examples/cqrs)
(tabulku `ch05_order_dashboard` založí `make install`, respektive
`doctrine:migrations:migrate`).

Testy: `./vendor/bin/phpunit tests/Chapter05`

## Co ukázka obsahuje

- **Command `PlaceOrder`** s primitivy a validačními atributy (kanonická podoba
  z [Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern)). Handler je
  vázaný jen na command bus a vrací `OrderId`, který si kontroler vyzvedne
  z `HandledStamp`.
- **Write model** jako výřez kanonického `Order` s továrnou `placeWithItems()`:
  události jdou v pořadí `OrderPlaced`, `OrderItemAdded` za každou položku,
  `OrderConfirmed`.
- **Překlad na integrační událost.** Handler z `OrderPlaced` sestaví
  `OrderPlacedIntegrationEvent` s položkami a součtem. Dílčí události zůstávají
  v kontextu, neznámá událost končí `LogicException`.
- **`OrderDashboardProjector`** na `event.bus` s prioritou 10
  ([12.11](https://ddd-v-symfony.katuscak.cz/cqrs#denorm-projekce-heading)).
  Upsert přepíše řádek jen tehdy, když je událost novější než zapsaný stav
  ([Idempotence projektorů](https://ddd-v-symfony.katuscak.cz/cqrs#idempotence-heading)).
  Testy pokrývají opakované doručení, opožděné doručení staré události
  i dvě události v téže vteřině.
- **Dotaz `ListOrders`** s filtrem, řazením a stránkováním
  ([12.07](https://ddd-v-symfony.katuscak.cz/cqrs#query-slozitejsi-heading)).
  Handler čte přes DBAL a vrací `OrderSummaryViewModel`; řadit smí jen podle
  sloupců z whitelistu.
- **`QueryBus` s `HandleTrait`**
  ([12.10](https://ddd-v-symfony.katuscak.cz/cqrs#query-bus-handle-trait-heading)):
  chybějící handler skončí srozumitelnou výjimkou.
- **Post-Redirect-Get** v kontroleru
  ([12.12](https://ddd-v-symfony.katuscak.cz/cqrs#ec-priklad-heading)).

`RegisterUser` a `GetUserProfile`, které kapitola také používá, jsou tytéž třídy
jako v kapitole Implementace v Symfony; spustitelné jsou v `Chapter04_Implementation`.

## V čem se ukázka od knihy liší

- **Synchronní projekce.** Kniha integrační událost ukládá do outboxu a projektor
  běží ve workeru, takže mezi zápisem a čtením je okno eventual consistency.
  Ukázka ji po uložení pošle na `event.bus` v témže procesu, což kniha popisuje
  jako nejjednodušší cestu (sekce „Kdo doménové události odešle“). Outbox ukazuje
  `Chapter11_OutboxPattern`.
- **Write model v paměti.** Kapitola je o čtecí straně. Repozitář objednávek drží
  data jen po dobu požadavku; trvalý je read model v SQLite. Doctrine mapování
  agregátu patří kapitole Návrh agregátu, repozitář nad EntityManagerem ukazuje
  `Chapter04_Implementation`. `placeWithItems()` z téhož důvodu vynechává
  `lockForSaga()`: ukázka ságu nemá.
- **Sběrnice bez middlewaru.** Kniha má `command.bus` a `query.bus` s middlewarem
  `validation` (command bus i s `doctrine_transaction`). Sdílená konfigurace ukázek
  má `messenger.bus.command` a `messenger.bus.query` bez middlewaru, proto validaci
  commandu volá kontroler sám a neplatný vstup vrací jako 422.
- **Zákazník z formuláře.** Kniha bere `customerId` z přihlášeného uživatele
  (`#[CurrentUser]`); ukázka přihlášení nemá.
- **`OrderShipped` a `OrderCancelled`** výřez agregátu nevydává. Třídy tu jsou
  kvůli projektoru a jeho testům.
- **SQLite a prefix `ch05_`.** Tabulka se jmenuje `ch05_order_dashboard` (kniha:
  `order_dashboard`), protože ukázky sdílejí jednu databázi. SQLite drží čas jako
  text; formát `Y-m-d H:i:s.u` zachová mikrosekundy i pořadí, takže podmínka
  `updated_at < :updatedAt` funguje stejně jako nad `TIMESTAMP(6)` v PostgreSQL.
- **Jmenné prostory.** Kniha používá `App\Ordering\…` a `App\SharedKernel\…`,
  ukázka `App\Chapter05_CQRS\…` a `App\Shared\…`.

## Odkaz na příručku

[CQRS](https://ddd-v-symfony.katuscak.cz/cqrs)
