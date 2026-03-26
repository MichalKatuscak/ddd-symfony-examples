<?php
namespace App\Chapter05_CQRS\Infrastructure\Persistence;
use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}
    public function save(Order $order): void { $this->em->persist($order); $this->em->flush(); }
    public function findById(OrderId $id): ?Order { return $this->em->find(Order::class, $id->value); }
    public function findAll(): array { return $this->em->getRepository(Order::class)->findAll(); }
}
