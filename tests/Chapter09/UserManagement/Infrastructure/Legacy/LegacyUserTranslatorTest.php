<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\UserManagement\Infrastructure\Legacy;

use App\Chapter09_Migration\UserManagement\Domain\ValueObject\UserStatus;
use App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\Exception\UnmappableLegacyStatusException;
use App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\LegacyUserTranslator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class LegacyUserTranslatorTest extends TestCase
{
    #[TestWith(['pending_verification', UserStatus::PendingVerification])]
    #[TestWith(['active', UserStatus::Active])]
    #[TestWith(['banned', UserStatus::Blocked])]
    #[TestWith(['deleted', UserStatus::Blocked])]
    public function test_legacy_status_is_mapped_explicitly(string $legacy, UserStatus $expected): void
    {
        $user = (new LegacyUserTranslator())->toDomain($this->row(['status' => $legacy]));

        self::assertSame($expected, $user->status());
    }

    public function test_unknown_legacy_status_fails_loudly(): void
    {
        $this->expectException(UnmappableLegacyStatusException::class);

        (new LegacyUserTranslator())->toDomain($this->row(['status' => 'PLACED']));
    }

    public function test_translation_keeps_history_and_records_no_event(): void
    {
        $user = (new LegacyUserTranslator())->toDomain($this->row([]));

        // Rekonstituce, ne registrace: datum i aktivační token zůstávají z legacy řádku.
        self::assertSame('2019-03-01 08:00:00', $user->createdAt->format('Y-m-d H:i:s'));
        self::assertSame('legacy-token-123', $user->verificationToken()?->value);
        self::assertSame([], $user->releaseEvents());
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function row(array $override): array
    {
        return $override + [
            'uuid'               => '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03',
            'name'               => 'Jan Novák',
            'email'              => 'jan@firma.cz',
            'password'           => '$2y$10$legacyhash',
            'status'             => 'pending_verification',
            'created_at'         => '2019-03-01 08:00:00',
            'verification_token' => 'legacy-token-123',
        ];
    }
}
