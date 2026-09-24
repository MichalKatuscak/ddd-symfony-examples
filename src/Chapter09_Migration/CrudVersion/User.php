<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\CrudVersion;

/**
 * PŘED migrací: anemická entita z CRUD aplikace (v knize App\Entity\User).
 * Gettery a settery bez pravidel – stav, e-mail i heslo nastaví kdokoli
 * na cokoli. Pravidla žijí v kontroleru a v UserService, pokud vůbec.
 */
final class User
{
    private ?int $id = null;
    private string $name = '';
    private string $email = '';
    private string $password = '';
    private string $status = '';
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
}
