# Kapitola 5: CQRS

Tato ukázka demonstruje oddělení Command a Query stran, Messenger buses a DBAL read model.

## Spuštění

Otevři [http://localhost:8000/examples/cqrs](http://localhost:8000/examples/cqrs)

## Co se naučíš

- Command/Query separation — zapisovací a čtecí operace jdou různými cestami
- Symfony Messenger s více buses (command bus, query bus)
- DBAL read model — přímé SQL dotazy pro čtení, bez Doctrine ORM overhead
- Proč CQRS zjednodušuje škálování a optimalizaci výkonu

## Odkaz na příručku

[CQRS v Symfony](https://ddd-symfony.cz/cqrs)
