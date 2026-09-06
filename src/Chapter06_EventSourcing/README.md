# Kapitola 6: Event Sourcing

Tato ukázka demonstruje Event Store, rekonstrukci agregátu z eventů, projekce a optimistické zamykání.

## Spuštění

Otevři [http://localhost:8000/examples/event-sourcing](http://localhost:8000/examples/event-sourcing)

## Co se naučíš

- Event Store — stav agregátu uložený jako sekvence eventů místo aktuálního stavu
- Rekonstrukce agregátu — `BankAccount::reconstituteFrom($events)` přehraje historii
- Projekce — odvozené read modely aktualizované z event streamu
- Optimistické zamykání — ochrana před souběžnými zápisy pomocí verze streamu

## Proč se agregát liší

Event-sourcovaný agregát nedrží stav, ale odvozuje ho přehráním událostí. Proto
má jinou stavbu než kanonická verze z
[kapitoly o návrhu agregátu](../Chapter02_AggregateDesign): stav je vedlejší produkt
přehrání, ne to, co se ukládá. Rozdíl je vlastnost vzoru, ne zjednodušení ukázky.

## Odkaz na příručku

[Event Sourcing v Symfony](https://ddd-v-symfony.katuscak.cz/event-sourcing)
