# Kapitola 15: Outbox Pattern (Transactional Outbox a Idempotent Inbox)

Ukázka ke kapitole [Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern).
Objednávka a integrační událost vznikají v jednom zápisu, relay je publikuje
a subscriber si v inboxu eviduje, co už zpracoval.

## Spuštění

Stránka: [http://localhost:8000/examples/outbox](http://localhost:8000/examples/outbox)

Formulář nabízí dva scénáře navíc: výpadek brokera při prvním průchodu relaye
a pád relaye mezi publishem a `markSent`, po kterém přijde táž zpráva podruhé.

Testy: `./vendor/bin/phpunit tests/Chapter11`

## Co ukázka obsahuje

- `Order::placeWithItems()` začíná `self::place()`, takže události jdou v pořadí
  `OrderPlaced`, `OrderItemAdded` za každou položku, `OrderConfirmed`
  ([15.04](https://ddd-v-symfony.katuscak.cz/outbox-pattern#order-aggregate-heading)).
  Továrna objednávku rovnou zamkne pro ságu.
- `PlaceOrderHandler` pošle každou doménovou událost synchronně na `event.bus`
  posluchačům v kontextu Ordering a na hranici kontextu je přeloží na jedinou
  `OrderPlacedIntegrationEvent` s vlastním `eventId`, kterou uloží do outboxu.
  Neznámá událost končí `LogicException`. Payload serializuje
  `IntegrationEventSerializer`
  ([15.05](https://ddd-v-symfony.katuscak.cz/outbox-pattern#serializer-heading)).
- `OutboxMessage` má jedenáct vlastností podle jedenácti sloupců tabulky
  ([15.03](https://ddd-v-symfony.katuscak.cz/outbox-pattern#vyznam-sloupcu-heading)).
  `markFailed()` nechá řádek `pending` s exponenciálním odkladem a do `failed`
  ho pošle až po pátém pokusu.
- Relay rozlišuje dva druhy chyb
  ([Backpressure](https://ddd-v-symfony.katuscak.cz/outbox-pattern#backpressure-heading)).
  Výpadek brokera (`TransportException`) průchod přeruší, řádek nechá `pending`
  bez započteného pokusu a `app:outbox:dispatch` čeká s backoffem 1 s, 2 s … 30 s.
  Chybu konkrétní zprávy (neznámý typ, denormalizace) zapíše `markFailed()`.
- `OutboxMessageFactory` rekonstruuje zprávu jen z whitelistu typů.
- `OrderPlacedReadModelUpdater` se ptá inboxu, provede upsert a zapíše
  `(eventId, consumer)`. Inbox duplicitní zápis odmítne výjimkou, nikdy ho tiše
  nepřepíše.
- `DbalInboxRepository` je inbox z knihy nad DBAL. `event_id` i `id` předává
  s typem `UuidType::NAME`, takže je zapíše v podobě platformy (SQLite a MySQL
  binárně, PostgreSQL nativní UUID) stejně jako mapování `InboxMessage`.
  `markProcessed()` `UniqueConstraintViolationException` nechytá. Test nad SQLite
  s binárními sloupci ověřuje uložený tvar i to, že souběžný duplikát shodí
  transakci i s vedlejším efektem.

## Čím se ukázka liší od knihy a proč

**Úložiště v paměti.** Kniha mapuje `OutboxMessage` Doctrine atributy a zapisuje
v `$em->wrapInTransaction()`. Ukázka pouští celý cyklus včetně výpadku
a opakovaného doručení v jednom HTTP requestu, takže data drží v paměti.
Hranici transakce v `PlaceOrderHandler` proto vyznačuje jen komentář.
S tím souvisí přesnost času: v paměti se `occurredAt` porovnává
s mikrosekundami, sloupec typu `datetime_immutable` v DBAL 4 je ale neuloží
a relay nad tabulkou řadí jen na sekundy
([komentář v migraci](https://ddd-v-symfony.katuscak.cz/outbox-pattern#migration-heading)).

**Relay bez brokera.** Kniha posílá zprávu na `event.bus` do transportu
`async_events`. Ukázka broker nemá; místo `$bus->dispatch()` volá port
`MessagePublisher`, jehož implementace `InProcessPublisher` předá zprávu
subscriberovi přímo. Sdílenou sběrnici nepoužívá záměrně:
`OrderPlacedIntegrationEvent` odebírá i `OrderProcessManager` z ukázky
ke kapitole o ságách a relay by pak rozjel i ságu.

**Smyčka relaye ve službě.** V knize žije celá smyčka v `OutboxDispatchCommand`.
Ukázka jeden průchod vytáhla do `OutboxRelay`, aby ho mohl spustit i controller.
Při výpadku brokera proto `OutboxRelay` průchod ukončí a vrátí příznak
`brokerUnavailable`; backoff řídí příkaz (v knize `continue 2` přímo ve smyčce).
Stránka na backoff nečeká, broker v ní „naskočí“ hned.
Příkaz `php bin/console app:outbox:dispatch --time-limit=1` ukazuje tvar
trvale běžícího procesu; v novém procesu je ovšem outbox v paměti prázdný.

**Read model v paměti.** `InMemoryReadModelStore` nahrazuje upsert do tabulky
`reporting_orders`. Počítadlo `writes` v knize není; ukázka jím dokládá, že
duplicitní doručení zastavil inbox.

**Vlastní kopie `Order`.** Kanonický agregát je v ukázce ke kapitole o návrhu
agregátu. `placeWithItems()` je statická továrna na téže třídě, zvenku ji
přidat nejde, a proto má kapitola vlastní kopii.

## Odkaz na příručku

[Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern)
