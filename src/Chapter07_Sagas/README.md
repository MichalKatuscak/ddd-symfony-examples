# Kapitola 7: Ságy (Process Manager)

Tato ukázka demonstruje Process Manager, Messenger handlery a kompenzační transakce.

## Spuštění

Otevři [http://localhost:8000/examples/sagy](http://localhost:8000/examples/sagy)

## Co se naučíš

- Process Manager jako stavový orchestrátor dlouhotrvajícího procesu
- Symfony Messenger handlery reagující na domain events
- Kompenzační transakce — jak vrátit provedené kroky při selhání
- Rozdíl mezi Choreography (eventy) a Orchestration (process manager)

## Čím se ukázka liší od příručky

Orchestrátor tady volá kroky **synchronně za sebou**, aby byl celý průběh vidět
v jednom výpisu. Příručka staví `OrderProcessManager` jako posluchače událostí:
každý krok vydá událost a sága na ni reaguje samostatnou metodou. Kompenzace,
stavový model i pravidlo o terminálních stavech jsou stejné, liší se jen způsob,
jakým se kroky spouštějí.

## Odkaz na příručku

[Ságy v Symfony](https://ddd-v-symfony.katuscak.cz/sagy-a-process-managery)
