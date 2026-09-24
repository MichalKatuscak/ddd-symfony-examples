<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\UI;

use App\Chapter12_LesserPatterns\Banking\Domain\Account;
use App\Chapter12_LesserPatterns\Banking\Domain\AccountId;
use App\Chapter12_LesserPatterns\Banking\Domain\AccountRepository;
use App\Chapter12_LesserPatterns\Banking\Domain\Service\MoneyTransferService;
use App\Chapter12_LesserPatterns\Banking\Domain\TransferReference;
use App\Chapter12_LesserPatterns\Ordering\Application\Service\FreeShippingPolicy;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\Cart;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartId;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartLine;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartRepository;
use App\Chapter12_LesserPatterns\Ordering\Domain\Factory\OrderFromCartFactory;
use App\Chapter12_LesserPatterns\Ordering\Domain\Repository\OrderRepository;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;
use App\Chapter12_LesserPatterns\Ordering\Infrastructure\InMemoryBlacklistRegistry;
use App\Chapter12_LesserPatterns\Ordering\Infrastructure\InMemoryPriceList;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\UI\ExampleCatalog;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stránka ukázky skládá kroky, které by v aplikaci dělal command handler
 * (checkout, převod). Kniha je nerozepisuje; podstatné jsou doménové
 * třídy, které kontroler jen volá. Stav drží in-memory repozitáře, takže
 * každý požadavek začíná od čistých účtů.
 */
final class Chapter12Controller extends AbstractController
{
    private const array CUSTOMERS = [
        '01920000-0000-7000-8000-000000000001' => 'Běžný zákazník',
        InMemoryPriceList::WHOLESALE_CUSTOMER => 'Velkoodběratel (sleva 10 %)',
        InMemoryBlacklistRegistry::BLOCKED_CUSTOMER => 'Zákazník na blacklistu',
    ];

    private const string SOURCE_ACCOUNT = '01920000-0000-7000-8000-00000000acc1';
    private const string TARGET_ACCOUNT_CZK = '01920000-0000-7000-8000-00000000acc2';
    private const string TARGET_ACCOUNT_EUR = '01920000-0000-7000-8000-00000000acc3';

    public function __construct(
        private readonly CartRepository $carts,
        private readonly OrderRepository $orders,
        private readonly OrderFromCartFactory $orderFromCart,
        private readonly FreeShippingPolicy $freeShipping,
        private readonly AccountRepository $accounts,
        private readonly MoneyTransferService $transfers,
        private readonly ClockInterface $clock,
    ) {}

    #[Route('/examples/mene-zname-vzory', name: 'chapter12')]
    public function index(Request $request): Response
    {
        $this->seedAccounts();

        $checkout = null;
        $transfer = null;

        if ($request->isMethod('POST')) {
            match ($request->request->getString('action')) {
                'checkout' => $checkout = $this->checkout($request),
                'transfer' => $transfer = $this->transfer($request),
                default => null,
            };
        }

        return $this->render('examples/chapter12/index.html.twig', [
            'customers' => self::CUSTOMERS,
            'products' => InMemoryPriceList::PRODUCTS,
            'checkout' => $checkout,
            'transfer' => $transfer,
            'accounts' => [
                'Zdrojový účet (CZK)' => $this->accounts->get(AccountId::fromString(self::SOURCE_ACCOUNT)),
                'Cílový účet (CZK)' => $this->accounts->get(AccountId::fromString(self::TARGET_ACCOUNT_CZK)),
                'Cílový účet (EUR)' => $this->accounts->get(AccountId::fromString(self::TARGET_ACCOUNT_EUR)),
            ],
            ...ExampleCatalog::navigation('chapter12'),
        ]);
    }

    /** @return array<string, mixed> */
    private function checkout(Request $request): array
    {
        $customerId = $request->request->getString('customerId');
        $productId = $request->request->getString('productId');

        if (!isset(self::CUSTOMERS[$customerId], InMemoryPriceList::PRODUCTS[$productId])) {
            return ['error' => 'Neznámý zákazník nebo produkt.'];
        }

        try {
            $cart = new Cart(
                CartId::generate(),
                [new CartLine(ProductId::fromString($productId), max(1, $request->request->getInt('quantity', 1)))],
                new ShippingAddress('Ukázková 1', 'Město', '110 00', strtoupper($request->request->getString('country', 'CZ'))),
            );
            $this->carts->save($cart);

            // Factory class: košík, pricing a hodiny jí dodal container.
            $order = $this->orderFromCart->fromCart($cart->id, CustomerId::fromString($customerId));
            $this->orders->save($order);

            return [
                'customer' => self::CUSTOMERS[$customerId],
                'country' => $order->shippingAddress?->countryCode,
                'unitPrice' => $order->items()[0]->unitPrice,
                'quantity' => $order->items()[0]->quantity,
                'total' => $order->totalAmount(),
                'freeShipping' => $this->freeShipping->isEligible($order),
            ];
        } catch (\DomainException|\InvalidArgumentException $e) {
            return ['error' => $e::class . ': ' . $e->getMessage()];
        }
    }

    /** @return array<string, mixed> */
    private function transfer(Request $request): array
    {
        $from = $this->accounts->get(AccountId::fromString(self::SOURCE_ACCOUNT));
        $to = $this->accounts->get(AccountId::fromString(
            $request->request->getString('target') === 'eur' ? self::TARGET_ACCOUNT_EUR : self::TARGET_ACCOUNT_CZK,
        ));
        $amount = new Money(max(1, $request->request->getInt('amount', 100)) * 100, Currency::CZK);

        try {
            $this->transfers->transfer($from, $to, $amount, TransferReference::generate(), $this->clock->now());

            // Uložení obou účtů je vědomé porušení vodítka „jeden agregát
            // na transakci“, viz poznámka za ukázkou v 08.03.
            $this->accounts->save($from);
            $this->accounts->save($to);

            return ['amount' => $amount];
        } catch (\DomainException $e) {
            return ['error' => $e::class . ': ' . $e->getMessage()];
        }
    }

    private function seedAccounts(): void
    {
        $this->accounts->save(new Account(AccountId::fromString(self::SOURCE_ACCOUNT), new Money(50_000, Currency::CZK)));
        $this->accounts->save(new Account(AccountId::fromString(self::TARGET_ACCOUNT_CZK), new Money(10_000, Currency::CZK)));
        $this->accounts->save(new Account(AccountId::fromString(self::TARGET_ACCOUNT_EUR), new Money(10_000, Currency::EUR)));
    }
}
