# ddd-symfony-examples

Živé, spustitelné ukázky Domain-Driven Design v Symfony 8.

Součást příručky **[DDD v Symfony](https://ddd-symfony.cz)**.

## Požadavky

- PHP 8.4+
- Composer
- [Symfony CLI](https://symfony.com/download)

## Spuštění

```bash
git clone https://github.com/MichalKatuscak/ddd-symfony-examples
cd ddd-symfony-examples
make install
symfony server:start
```

Otevři **http://localhost:8000/examples**

## Obsah

| Kapitola | Ukázka | URL |
|---|---|---|
| 1 | Co je DDD — čistý doménový model | `/examples/co-je-ddd` |
| 3 | Základní koncepty — Entity, VO, Agregát | `/examples/zakladni-koncepty` |
| 4 | Implementace — Doctrine, Domain Events | `/examples/implementace` |
| 5 | CQRS — Commands, Queries, Messenger | `/examples/cqrs` |
| 6 | Event Sourcing — EventStore | `/examples/event-sourcing` |
| 7 | Ságy — orchestrace, kompenzace | `/examples/sagy` |
| 8 | Testování — unit testy domény | `/examples/testovani` |
| 9 | Migrace z CRUD | `/examples/migrace-z-crud` |

## Testy

```bash
make test
```
