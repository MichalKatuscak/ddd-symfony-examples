<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\UI;

use App\Chapter12_LesserPatterns\Application\EvaluatePromo\EvaluatePromoCommand;
use App\Chapter12_LesserPatterns\Application\EvaluatePromo\EvaluatePromoHandler;
use App\Chapter12_LesserPatterns\Application\Factory\OrderFromCartFactory;
use App\Chapter12_LesserPatterns\Application\Factory\PricingService;
use App\Chapter12_LesserPatterns\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter12_LesserPatterns\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter12_LesserPatterns\Domain\Customer\Customer;
use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\Cart;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Service\Account;
use App\Chapter12_LesserPatterns\Domain\Service\InsufficientFundsException;
use App\Chapter12_LesserPatterns\Domain\Service\MoneyTransferService;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryAccountRepository;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryCartRepository;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryCustomerRepository;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter12Controller extends AbstractController
{
    public function __construct(
        private readonly InMemoryOrderRepository $orders,
        private readonly InMemoryCustomerRepository $customers,
        private readonly InMemoryAccountRepository $accounts,
        private readonly InMemoryCartRepository $carts,
    ) {}

    #[Route('/examples/mene-zname-vzory', name: 'chapter12')]
    public function index(Request $request): Response
    {
        $this->ensureAccountsSeeded();

        $promoResult = null;
        $promoError = null;
        $transferResult = null;
        $transferError = null;
        $factoryResult = null;
        $factoryError = null;

        if ($request->isMethod('POST')) {
            match ($request->request->get('action', '')) {
                'promo' => [$promoResult, $promoError] = $this->demoSpecification($request),
                'transfer' => [$transferResult, $transferError] = $this->demoDomainService($request),
                'factory' => [$factoryResult, $factoryError] = $this->demoFactory($request),
                default => null,
            };
        }

        return $this->render('examples/chapter12/index.html.twig', [
            'promo_result' => $promoResult,
            'promo_error' => $promoError,
            'transfer_result' => $transferResult,
            'transfer_error' => $transferError,
            'factory_result' => $factoryResult,
            'factory_error' => $factoryError,
            'accounts' => $this->accounts->findAll(),
            'prev_route' => 'chapter09',
            'prev_title' => 'Migrace z CRUD',
            'next_route' => null,
            'next_title' => null,
        ]);
    }

    /** @return array{0: ?array<string, mixed>, 1: ?string} */
    private function demoSpecification(Request $request): array
    {
        try {
            $country = strtoupper(trim((string) $request->request->get('country', 'CZ')));
            $totalCzk = max(1, (int) $request->request->get('total', 1500));

            $placeHandler = new PlaceOrderHandler($this->orders);
            $orderId = $placeHandler(new PlaceOrderCommand(
                customerId: 'demo-customer',
                shippingCountry: $country,
                items: [[
                    'name' => 'Položka A',
                    'quantity' => 1,
                    'unitPriceMinorUnits' => $totalCzk * 100,
                ]],
            ));

            $promoHandler = new EvaluatePromoHandler($this->orders);
            $result = $promoHandler(new EvaluatePromoCommand(
                orderId: $orderId,
                freeShippingThresholdMinorUnits: 100_000, // 1000 CZK
            ));

            return [[
                'eligible' => $result->eligible,
                'aboveFreeShippingThreshold' => $result->aboveFreeShippingThreshold,
                'shipsToEU' => $result->shipsToEU,
                'notInBlacklist' => $result->notInBlacklist,
                'orderTotalFormatted' => $result->orderTotalFormatted,
                'shippingCountry' => $result->shippingCountry,
            ], null];
        } catch (\DomainException $e) {
            return [null, 'DomainException: ' . $e->getMessage()];
        }
    }

    /** @return array{0: ?array<string, mixed>, 1: ?string} */
    private function demoDomainService(Request $request): array
    {
        try {
            $amountCzk = max(1, (int) $request->request->get('amount', 100));
            $amount = new Money($amountCzk * 100);

            $from = $this->accounts->findById('acc-from');
            $to = $this->accounts->findById('acc-to');

            $service = new MoneyTransferService();
            $service->transfer($from, $to, $amount);

            $this->accounts->save($from);
            $this->accounts->save($to);

            return [[
                'fromBalance' => $from->balance()->formatted(),
                'toBalance' => $to->balance()->formatted(),
                'amount' => $amount->formatted(),
            ], null];
        } catch (InsufficientFundsException $e) {
            return [null, 'InsufficientFundsException: ' . $e->getMessage()];
        } catch (\DomainException $e) {
            return [null, 'DomainException: ' . $e->getMessage()];
        }
    }

    /** @return array{0: ?array<string, mixed>, 1: ?string} */
    private function demoFactory(Request $request): array
    {
        try {
            $cartId = 'cart-' . uniqid('', true);
            $customerId = CustomerId::generate();
            $premium = $request->request->getBoolean('premium');
            $itemPriceCzk = max(1, (int) $request->request->get('price', 500));

            $customer = new Customer(
                id: $customerId,
                email: 'demo@katuscak.cz',
                countryCode: 'CZ',
                premium: $premium,
            );
            $this->customers->save($customer);

            $cart = new Cart(
                id: $cartId,
                customerId: $customerId,
                rawItems: [
                    ['name' => 'Položka A', 'quantity' => 2, 'unitPriceMinorUnits' => $itemPriceCzk * 100],
                ],
                shippingCountry: 'CZ',
            );
            $this->carts->save($cart);

            $factory = new OrderFromCartFactory(
                carts: $this->carts,
                customers: $this->customers,
                pricing: new PricingService(),
            );

            $order = $factory->fromCart($cartId);
            $this->orders->save($order);

            return [[
                'orderId' => substr($order->id()->value, 0, 8) . '…',
                'total' => $order->total()->formatted(),
                'premium' => $premium,
                'itemCount' => count($order->items()),
            ], null];
        } catch (\DomainException $e) {
            return [null, 'DomainException: ' . $e->getMessage()];
        }
    }

    private function ensureAccountsSeeded(): void
    {
        if ($this->accounts->findAll() === []) {
            $this->accounts->save(new Account('acc-from', new Money(500_00)));
            $this->accounts->save(new Account('acc-to', new Money(100_00)));
        }
    }
}
