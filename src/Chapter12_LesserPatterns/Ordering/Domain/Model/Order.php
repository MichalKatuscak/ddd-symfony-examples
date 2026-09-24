<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Model;

use App\Chapter12_LesserPatterns\Ordering\Domain\Event\OrderPlaced;
use App\Chapter12_LesserPatterns\Ordering\Domain\Exception\EmptyOrderException;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\OrderId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

/**
 * Varianta Order s továrnami z kapitoly Méně známé taktické vzory (08.04).
 *
 * Továrny stojí vedle kanonického Order::place(OrderId, CustomerId) a do
 * kanonického modelu nepatří. Kanonická továrna s položkami je
 * placeWithItems() z kapitoly Outbox Pattern.
 *
 * Kniha specifikacemi čte `shippingAddress`, ale placePhysical() adresu
 * nepřebírá. Ukázka ji předává čtvrtým parametrem; digitální objednávka
 * adresu nemá.
 */
final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items;

    /** @param list<OrderItem> $items */
    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
        array $items,
        private readonly OrderType $type,
        // Zde čas vzniku objednávky. V kanonickém modelu z Návrhu agregátu
        // nese placedAt čas potvrzení a plní ho až confirm().
        private readonly \DateTimeImmutable $placedAt,
        public readonly ?ShippingAddress $shippingAddress,
    ) {
        $this->items = $items;
        // Konstruktor jen plní stav. Eventy zaznamenávají factory metody –
        // konstruktorem prochází i reconstitute(), která žádný event vyvolat nesmí.
    }

    /**
     * Vznik objednávky s fyzickým zbožím – protějšek placeDigital() níže.
     *
     * @param list<OrderItem> $items
     */
    public static function placePhysical(
        CustomerId $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
        ShippingAddress $shippingAddress,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_values($items),
            type: OrderType::Physical,
            placedAt: $placedAt,
            shippingAddress: $shippingAddress,
        );
        $order->record(new OrderPlaced($order->id, $customerId));

        return $order;
    }

    /**
     * Polymorfní vznik – pouze digitální obsah, jiná pravidla
     * (žádná dopravní adresa, instantní doručení).
     *
     * @param list<DigitalItem> $items
     */
    public static function placeDigital(
        CustomerId $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_map(static fn (DigitalItem $i): OrderItem => $i->toOrderItem(), array_values($items)),
            type: OrderType::Digital,
            placedAt: $placedAt,
            shippingAddress: null,
        );
        $order->record(new OrderPlaced($order->id, $customerId));

        return $order;
    }

    /**
     * Rekonstituce ze stavu načteného z DB / event streamu.
     * Tento pojmenovaný konstruktor nekontroluje invarianty –
     * obnovovaný stav je z definice platný, jinak by se nedostal do persistence.
     *
     * @internal Smí volat pouze infrastruktura repozitáře.
     *
     * @param list<OrderItem> $items
     */
    public static function reconstitute(
        OrderId $id,
        CustomerId $customerId,
        array $items,
        OrderType $type,
        \DateTimeImmutable $placedAt,
        ?ShippingAddress $shippingAddress,
    ): self {
        return new self($id, $customerId, $items, $type, $placedAt, $shippingAddress);
    }

    public function type(): OrderType
    {
        return $this->type;
    }

    public function placedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function totalAmount(): Money
    {
        // Továrny prázdnou objednávku nepustí, první položka tedy existuje
        // vždy a určuje měnu součtu.
        $total = $this->items[0]->subtotal();

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }
}
