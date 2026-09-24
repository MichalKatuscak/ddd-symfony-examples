# Kapitola 14: Ságy a Process Managery

Ukázka ke kapitole [Ságy a Process Managery](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery).
Objednávka projde platbou, rezervací skladu a expedicí. Když krok selže, sága
vrátí hotové kroky kompenzacemi.

Pojmy sedí s [konvencí knihy](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery#terminologicka-konvence),
která vychází z Richardsona: sága je dlouhotrvající proces z lokálních transakcí
s kompenzacemi, Process Manager je orchestrační komponenta s perzistentním stavem,
která ho řídí. Ukázka předvádí orchestraci; choreografii kniha rozebírá bez
spustitelného protějšku.

## Spuštění

Stránka: [http://localhost:8000/examples/sagy](http://localhost:8000/examples/sagy)

Formulář nechá selhat platbu, nebo rezervaci skladu. Stránka pak ukáže stav
ságy, hotové kroky a stav objednávky.

Testy: `./vendor/bin/phpunit tests/Chapter07`

## Co ukázka obsahuje

- `OrderProcessManager` na `event.bus` podle
  [14.05](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery#process-manager-heading).
  Sám stav objednávky nemění; posílá `MarkOrderPaid`, `ShipOrder`
  a `CancelOrderCommand`.
- Handlery kroků v kontextech Payment, Warehouse a Shipping. Výsledek hlásí
  událostí (`PaymentSucceeded`, `StockReserved`, `ShipmentCreated` a jejich
  protějšky), ne výjimkou. Každá kroková událost nese vlastní `eventId`.
- Idempotentní přechody `applyPaymentSucceeded()`, `applyStockReserved()`
  a `applyShipmentCreated()`: opakovaně doručená událost příkazy neodešle
  podruhé ([14.06](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery#idempotent-saga-transitions-heading)).
  Druhou polovinu obrany nese agregát: `markPaid()`, `ship()` i `cancel()`
  při opakování tiše skončí.
- Kompenzace: selhání skladu vrací platbu přes `RefundCustomer`, sága zůstává
  v `compensating` a do `failed` přejde až po `RefundSucceeded`. Storno zvenčí
  vrací hotové kroky v opačném pořadí (`CancelShipment`, `ReleaseStock`,
  `RefundCustomer`).
- Semantic lock: `placeWithItems()` objednávku zamkne, sága zámek uvolní
  `ReleaseOrderLock` nebo stornem pod `SystemActor`
  ([Izolace ság](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery#izolace-sag)).
- Timeouty: `CheckSagaTimeout` s `DelayStamp` pro stavy `awaiting_payment`
  a `awaiting_stock_reservation`
  ([14.08](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery#timeouty)).

## Čím se ukázka liší od knihy a proč

**Synchronní sběrnice.** Kniha routuje události a příkazy do transportů
`async_events` a `async_commands`. Ukázka transporty nemá, takže celý proces
doběhne v jednom HTTP requestu a jeho výsledek je vidět hned. Důsledek pro
timeouty: synchronní zpracování `DelayStamp` ignoruje, hlídač se zpracuje
hned po krocích vyvolaných před ním, zjistí, že sága stav opustila,
a nic neudělá. Skutečné vypršení ověřuje `CheckSagaTimeoutHandlerTest`.

**Jména sběrnic.** Příkazová sběrnice se v ukázkách jmenuje
`messenger.bus.command`, ne `command.bus` jako v knize. Konfiguraci
Messengeru sdílejí všechny kapitoly a jméno z ní přebírá i tahle.

**Stav ságy v paměti.** Kniha ukládá `OrderSaga` jako Doctrine entitu
s `#[ORM\Version]`. Proces tady doběhne synchronně, po restartu workeru není
co obnovovat, a tak stav drží `InMemoryOrderSagaRepository`. Unikátní index
`(saga_type, correlation_id)` napodobuje: druhou ságu pro tutéž objednávku
odmítne stejnou `UniqueConstraintViolationException`, jakou by vyhodila
databáze.

**Přepínače z formuláře.** Kniha selhání platby a skladu zapíná proměnnými
`PAYMENT_FAILS` a `STOCK_FAILS`. Ukázka je nastavuje z formuláře, aby šly
všechny větve vyzkoušet bez restartu serveru. Adaptéry v paměti proto nejsou
`readonly`.

**Objednávka z ukázky ke kapitole 15.** Sága odebírá `OrderPlacedIntegrationEvent`
a volá `markPaid()` a `ship()` na objednávce z `placeWithItems()`. Obojí kniha
definuje v kapitole [Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern)
a ukázka to přebírá z `Chapter11_OutboxPattern`. Roli relaye hraje controller:
řádek z outboxu pošle na `event.bus`.

**Co ukázka vynechává.** Choreografii (14.03), paralelní kroky (14.10)
a příkaz pro detekci zaseklých ság (14.11). Repozitář `findStale()` má,
příkaz nad ním ne.

## Odkaz na příručku

[Ságy a Process Managery](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery)
