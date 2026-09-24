<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\UI;

use App\Chapter03_BasicConcepts\Domain\Order\Customer;
use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Chapter03_BasicConcepts\Domain\Service\ShippingFeeService;
use App\Chapter03_BasicConcepts\Domain\User\Email;
use App\Chapter03_BasicConcepts\Domain\User\User;
use App\Chapter03_BasicConcepts\Domain\User\UserId;
use App\Chapter03_BasicConcepts\Infrastructure\Persistence\InMemoryOrderRepository;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ukázka běží bez databáze: každý požadavek sestaví novou objednávku,
 * uloží ji do repozitáře v paměti a teprve potom vyzvedne události.
 */
final class Chapter03Controller extends AbstractController
{
    private const array ORDER_ACTIONS = ['add_item', 'confirm_with_item', 'confirm_empty', 'add_after_confirm'];

    #[Route('/examples/zakladni-koncepty', name: 'chapter03')]
    public function index(Request $request): Response
    {
        $order = null;
        $result = null;
        $error = null;
        $voResult = null;
        $voError = null;
        $feeResult = null;
        $events = [];
        $action = (string) $request->request->get('action', '');

        if ($request->isMethod('POST')) {
            try {
                if (in_array($action, self::ORDER_ACTIONS, true)) {
                    $order = Order::place(OrderId::generate(), CustomerId::generate());
                    $result = $this->runOrderAction($action, $order, $request);
                    $events = $this->saveAndRelease($order);
                } elseif ($action === 'vo_email') {
                    $raw = (string) $request->request->get('email', '');
                    $email = Email::fromUserInput($raw);
                    $voResult = ['type' => 'email', 'raw' => $raw, 'value' => $email->value];
                } elseif ($action === 'vo_money') {
                    $a = new Money($this->toCents($request, 'amount_a'), Currency::CZK);
                    $b = new Money(
                        $this->toCents($request, 'amount_b'),
                        Currency::tryFrom((string) $request->request->get('currency_b', 'CZK')) ?? Currency::CZK,
                    );
                    $voResult = ['type' => 'money', 'a' => $a, 'b' => $b, 'sum' => $a->add($b)];
                } elseif ($action === 'shipping_fee') {
                    $order = Order::place(OrderId::generate(), CustomerId::generate());
                    $lines = max(1, min(10, (int) $request->request->get('lines', 1)));
                    for ($i = 0; $i < $lines; ++$i) {
                        $order->addItem(ProductId::generate(), 1, new Money(29_900, Currency::CZK));
                    }
                    $vip = $request->request->getBoolean('vip');
                    $fee = (new ShippingFeeService())->feeFor($order, new Customer($order->customerId, $vip));
                    $feeResult = ['lines' => $lines, 'vip' => $vip, 'fee' => $fee];
                }
            } catch (\InvalidArgumentException $e) {
                // Porušení formátu hodnoty (neplatný e-mail, záporná částka).
                $this->assignError($action, 'InvalidArgumentException: ' . $e->getMessage(), $error, $voError);
            } catch (\DomainException $e) {
                // Porušení doménového pravidla – pojmenovaná výjimka.
                $message = (new \ReflectionClass($e))->getShortName() . ': ' . $e->getMessage();
                $this->assignError($action, $message, $error, $voError);
            }
        }

        return $this->render('examples/chapter03/index.html.twig', [
            'order' => $order,
            'result' => $result,
            'error' => $error,
            'voResult' => $voResult,
            'voError' => $voError,
            'feeResult' => $feeResult,
            'events' => $events,
            'entityDemo' => $this->entityEqualityDemo(),
            ...ExampleCatalog::navigation('chapter03'),
        ]);
    }

    private function runOrderAction(string $action, Order $order, Request $request): ?string
    {
        switch ($action) {
            case 'add_item':
                $order->addItem(
                    ProductId::generate(),
                    max(1, (int) $request->request->get('qty', 1)),
                    new Money($this->toCents($request, 'price'), Currency::CZK),
                );

                return 'Položka přidána. Celkem: ' . $this->format($order->totalAmount());

            case 'confirm_with_item':
                $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
                $order->confirm();

                return 'Objednávka potvrzena. Stav: ' . $order->status()->value;

            case 'confirm_empty':
                $order->confirm(); // EmptyOrderException

                return null;

            case 'add_after_confirm':
                $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
                $order->confirm();
                $order->addItem(ProductId::generate(), 1, new Money(5_000, Currency::CZK)); // InvalidOrderStateTransitionException

                return null;
        }

        return null;
    }

    /**
     * Druhá polovina životního cyklu z 06.09: nejdřív uložit, pak vyzvednout
     * události. V aplikaci by je handler poslal na event bus; zde se jen vypíšou.
     *
     * @return list<array{class: string, payload: array<string, mixed>}>
     */
    private function saveAndRelease(Order $order): array
    {
        (new InMemoryOrderRepository())->save($order);

        return array_map(fn (object $event): array => [
            'class' => (new \ReflectionClass($event))->getShortName(),
            'payload' => array_map($this->scalar(...), get_object_vars($event)),
        ], $order->releaseEvents());
    }

    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeImmutable => $value->format('Y-m-d H:i:s'),
            $value instanceof \Stringable => (string) $value,
            default => $value,
        };
    }

    /** @return array{sameIdentity: bool, looseEquality: bool, strictIdentity: bool} */
    private function entityEqualityDemo(): array
    {
        $id = UserId::generate();
        // Tentýž uživatel načtený dvakrát, jedna instance mezitím změnila e-mail.
        $first = new User($id, 'Jana Nováková', new Email('jana@example.com'));
        $second = new User(UserId::fromString($id->value), 'Jana Nováková', new Email('jana@example.com'));
        $second->changeEmail(new Email('jana.novakova@example.com'));

        return [
            'sameIdentity' => $first->equals($second),
            'looseEquality' => $first == $second,
            'strictIdentity' => $first === $second,
        ];
    }

    private function assignError(string $action, string $message, ?string &$error, ?string &$voError): void
    {
        if (in_array($action, ['vo_email', 'vo_money'], true)) {
            $voError = $message;
        } else {
            $error = $message;
        }
    }

    private function toCents(Request $request, string $field): int
    {
        return (int) round((float) $request->request->get($field, '0') * 100);
    }

    private function format(Money $money): string
    {
        return number_format($money->amountInCents / 100, 2, ',', ' ') . ' ' . $money->currency->value;
    }
}
