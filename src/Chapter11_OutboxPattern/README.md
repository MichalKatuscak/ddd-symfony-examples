# Kapitola 11: Outbox Pattern (Transactional Outbox + Idempotent Inbox)

Tato ukázka demonstruje spolehlivé publikování doménových událostí přes outbox tabulku
a deduplikaci na straně subscriberů přes inbox tabulku.

## Spuštění

Otevři [http://localhost:8000/examples/outbox](http://localhost:8000/examples/outbox)

Konzolový worker (relay):

```bash
php bin/console app:outbox:dispatch
```

## Co se naučíš

- Transactional Outbox — agregát i outbox řádek vznikají v jedné DB transakci, dual-write problem zmizí
- Polling relay (worker) — samostatný proces čte pending zprávy a publikuje je brokeru
- Idempotent Inbox — subscriber přes UNIQUE constraint odmítne duplicitní doručení
- At-least-once delivery — proč ho každý broker garantuje a proč je idempotence povinnost
- Kde je hranice transakce — agregát + outbox ano, broker ne

## Odkaz na příručku

[Outbox Pattern](https://ddd-v-symfony.katuscak.cz/outbox-pattern)
