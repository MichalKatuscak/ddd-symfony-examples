# Kapitola 11: Autorizace v DDD

Čtyři vrstvy autorizace v praxi — a hlavně dvě místa, kde se to nejčastěji rozejde:
systémová identita a zámek dlouhotrvajícího procesu.

## Co se naučíš

- Vlastnictví je vztah, který zná agregát. Voter se na něj ptá, neopisuje ho.
- Ve workeru žádný token neexistuje, takže se rozhoduje podle identity v příkazu.
- Sága není člověk: dostane explicitní systémovou identitu s vlastní větví,
  ne podmínku „když aktér chybí, povol vše“.
- Zámek patří procesu, takže si ho proces sám uvolní.
- `isCancellable()` musí znát i zámek, jinak UI nabídne tlačítko vedoucí na jistou chybu.

## Spuštění testů

```bash
php bin/phpunit --filter Chapter10
```

## Odkaz na příručku

[Autorizace v DDD](https://ddd-v-symfony.katuscak.cz/autorizace-v-ddd)
