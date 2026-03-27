<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Projection;

class OrderListProjection
{
    private string $orderId;
    private string $customerId;
    private int $totalAmount;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $orderId,
        string $customerId,
        int $totalAmount,
        string $status,
        \DateTimeImmutable $createdAt,
    ) {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function orderId(): string { return $this->orderId; }
    public function customerId(): string { return $this->customerId; }
    public function totalAmount(): int { return $this->totalAmount; }
    public function status(): string { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
