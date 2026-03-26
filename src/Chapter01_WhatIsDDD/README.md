# Kapitola 1: Co je DDD

**Článek v příručce:** https://ddd-symfony.cz/co-je-ddd

## Co tato ukázka ukazuje

- Doménový model bez závislosti na frameworku nebo DB
- Value Object (`Price`, `ProductId`) — neměnné objekty s vlastní logikou
- Entity (`Product`) — objekt s identitou
- Doménová logika ve třídě `Cart`

## Spuštění

```bash
symfony server:start
# http://localhost:8000/examples/co-je-ddd
```
