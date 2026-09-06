# Kapitola 4: Implementace v Symfony

Tato ukázka demonstruje Doctrine persistence, Domain Events, Application Layer a Messenger.

## Spuštění

Otevři [http://localhost:8000/examples/implementace](http://localhost:8000/examples/implementace)

## Co se naučíš

- Doctrine XML mapping — jak persistovat agregát bez anotací v doméně
- Domain Events propagované přes Symfony Messenger
- Application Layer — Command a CommandHandler oddělené od HTTP vrstvy
- Jak doménová vrstva zůstává čistá bez závislosti na frameworku

## Proč je tu identita jako řetězec

Agregát drží `id` jako `string` a hodnotový objekt sestaví až getter. Je to
záměrné zjednodušení, aby ukázka nepotřebovala vlastní Doctrine typ. Kanonický
agregát v [kapitole o návrhu agregátu](../Chapter02_AggregateDesign) má
`public readonly OrderId $id` a mapuje ho custom typem — příručka popisuje obojí.

## Odkaz na příručku

[Implementace DDD v Symfony](https://ddd-v-symfony.katuscak.cz/implementace-v-symfony)
