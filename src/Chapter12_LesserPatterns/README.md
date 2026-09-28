# Kapitola 8: Doplňující taktické vzory

Ukázka ke kapitole [Doplňující taktické vzory](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory):
Specification, Domain Service, Factory a Module.

## Spuštění

Stránka: [http://localhost:8000/examples/mene-zname-vzory](http://localhost:8000/examples/mene-zname-vzory)

Testy: `./vendor/bin/phpunit tests/Chapter12`

## Co ukázka obsahuje

- **Specification** ([08.02](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory#specification)):
  rozhraní `Specification` a kompozit s `and()`, `or()`, `not()` v `SharedKernel`,
  doménová pravidla `EligibleForFreeShipping`, `InEUCountry` a `NotInBlacklist`
  (blacklist zákazníků, ne zemí) a jejich kompozice ve `FreeShippingPolicy`
  ([Kompozice v aplikační vrstvě](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory#spec-compose)).
  Politika vrací `bool` a agregát nemění.
- **Domain Service** ([Příklad: MoneyTransferService](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory#ds-priklad)):
  převod mezi dvěma účty kontextu `Banking` se signaturou z knihy (`TransferReference`,
  čas převodu). Služba nic neukládá; oba účty uloží volající. Druhou doménovou
  službou je `PricingService`, která cenu položky počítá z ceníku a cenové skupiny
  zákazníka.
- **Factory** ([08.04](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory#factories)):
  pojmenované továrny `Order::placePhysical()` a polymorfní `Order::placeDigital()`,
  `Order::reconstitute()` bez události a třída `OrderFromCartFactory`, které container
  dodá repozitář košíků, `PricingService` a hodiny. Invariant „aspoň jedna položka“
  zůstává v agregátu.
- **Module** ([Modul jako Bounded Context](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory#mod-bc)):
  nejvyšší úroveň adresářů tvoří kontexty `Ordering` a `Banking` a sdílené
  `SharedKernel`; technické složky jsou až uvnitř.

## V čem se ukázka od knihy liší

- **`placePhysical()` má čtvrtý parametr `ShippingAddress`.** Specifikace v knize čtou
  `$order->shippingAddress`, ale továrna adresu nepřebírá. Ukázka ji předává
  a `OrderFromCartFactory` ji bere z košíku. Digitální objednávka adresu nemá,
  proto `InEUCountry` čte zemi přes `?->` a vrátí `false`.
- **Vynechané části.** `Order::fromImport()` stojí na třídách, které kniha
  nedefinuje (`ImportedOrderRow`, `CustomerLookup`), proto v ukázce není.
  Double-dispatch do Doctrine (`QuerySpecification`, `Criteria`) a zbytková
  specifikace (`remainderUnsatisfiedBy()`) chybí také: ukázka běží bez databáze.
- **Doplněné třídy.** Kniha nerozepisuje `Account`, `Cart` ani `PricingService`.
  Ukázka jim dává jen to, co služby a factory volají; ceník a blacklist dodávají
  porty `PriceList` a `BlacklistRegistry` s in-memory adaptéry. Repozitáře košíků
  a účtů načítají přes `get()` jako v knize; chybějící záznam hlásí pojmenovanou
  `CartNotFoundException`, resp. `AccountNotFoundException`, které kniha nevypisuje.
- **Úložiště v paměti.** Repozitáře drží stav jen po dobu požadavku. Stránka skládá
  kroky, které by v aplikaci dělal command handler (checkout, převod); kniha
  je nerozepisuje.
- **Jmenné prostory.** Kniha používá `App\Ordering\…`, `App\Banking\…`
  a `App\SharedKernel\…`, ukázka `App\Chapter12_LesserPatterns\…`; `Money`
  a `Currency` bere ze sdíleného `App\Shared\Domain`.

## Odkaz na příručku

[Doplňující taktické vzory](https://ddd-v-symfony.katuscak.cz/mene-zname-vzory)
