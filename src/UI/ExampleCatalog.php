<?php

declare(strict_types=1);

namespace App\UI;

/**
 * Přehled ukázek v pořadí příručky.
 *
 * Čísla kapitol přebírá z frontmatteru příručky (chapter_number), adresáře
 * ukázek mají historická jména. Ukázky bez route mají jen testy; na přehledu
 * se ukážou bez odkazu a navigace předchozí/další je přeskakuje.
 */
final class ExampleCatalog
{
    private const BOOK_URL = 'https://ddd-v-symfony.katuscak.cz';

    /**
     * @return list<array{num: string, title: string, desc: string, book_path: string, dir: string, tests: string, route: ?string}>
     */
    public static function all(): array
    {
        return [
            ['num' => '01', 'title' => 'Co je Domain-Driven Design', 'desc' => 'Čistý doménový model, Bounded Context a Anti-Corruption Layer', 'book_path' => '/co-je-ddd', 'dir' => 'Chapter01_WhatIsDDD', 'tests' => 'tests/Chapter01', 'route' => 'chapter01'],
            ['num' => '06', 'title' => 'Základní koncepty DDD', 'desc' => 'Entita, hodnotové objekty, agregát, repozitář, doménová služba a události', 'book_path' => '/zakladni-koncepty', 'dir' => 'Chapter03_BasicConcepts', 'tests' => 'tests/Chapter03', 'route' => 'chapter03'],
            ['num' => '07', 'title' => 'Návrh agregátu', 'desc' => 'Kanonický agregát Order, jeho invarianty a události', 'book_path' => '/navrh-agregatu', 'dir' => 'Chapter02_AggregateDesign', 'tests' => 'tests/Chapter02', 'route' => null],
            ['num' => '08', 'title' => 'Doplňující taktické vzory', 'desc' => 'Specification, Domain Service, Factory a Module', 'book_path' => '/mene-zname-vzory', 'dir' => 'Chapter12_LesserPatterns', 'tests' => 'tests/Chapter12', 'route' => 'chapter12'],
            ['num' => '10', 'title' => 'Implementace v Symfony 8', 'desc' => 'Registrace uživatele: hodnotové objekty, custom typy Doctrine, Messenger', 'book_path' => '/implementace-v-symfony', 'dir' => 'Chapter04_Implementation', 'tests' => 'tests/Chapter04', 'route' => 'chapter04'],
            ['num' => '11', 'title' => 'Autorizace v DDD', 'desc' => 'Voter a invariant agregátu', 'book_path' => '/autorizace-v-ddd', 'dir' => 'Chapter10_Authorization', 'tests' => 'tests/Chapter10', 'route' => null],
            ['num' => '12', 'title' => 'CQRS', 'desc' => 'Příkaz, projekce do read modelu a dotaz přes DBAL', 'book_path' => '/cqrs', 'dir' => 'Chapter05_CQRS', 'tests' => 'tests/Chapter05', 'route' => 'chapter05'],
            ['num' => '13', 'title' => 'Event Sourcing', 'desc' => 'Event store s verzí streamu, rekonstrukce agregátu a projekce', 'book_path' => '/event-sourcing', 'dir' => 'Chapter06_EventSourcing', 'tests' => 'tests/Chapter06', 'route' => 'chapter06'],
            ['num' => '14', 'title' => 'Ságy a Process Managery', 'desc' => 'Orchestrovaná sága a kompenzační kroky', 'book_path' => '/sagy-a-process-managery', 'dir' => 'Chapter07_Sagas', 'tests' => 'tests/Chapter07', 'route' => 'chapter07'],
            ['num' => '15', 'title' => 'Outbox Pattern', 'desc' => 'Transactional Outbox, relay a idempotentní inbox', 'book_path' => '/outbox-pattern', 'dir' => 'Chapter11_OutboxPattern', 'tests' => 'tests/Chapter11', 'route' => 'chapter11'],
            ['num' => '17', 'title' => 'Testování DDD', 'desc' => 'Unit testy doménového modelu bez frameworku a databáze', 'book_path' => '/testovani-ddd', 'dir' => 'Chapter08_Testing', 'tests' => 'tests/Chapter08', 'route' => 'chapter08'],
            ['num' => '18', 'title' => 'Migrace z CRUD na DDD', 'desc' => 'Invarianty místo setterů: User před migrací a po ní', 'book_path' => '/migrace-z-crud', 'dir' => 'Chapter09_Migration', 'tests' => 'tests/Chapter09', 'route' => 'chapter09'],
        ];
    }

    public static function bookUrl(string $path = ''): string
    {
        return self::BOOK_URL . $path;
    }

    /**
     * Sousední ukázky s UI v pořadí příručky, ve tvaru pro šablony ukázek.
     *
     * @return array{prev_route: ?string, prev_title: ?string, next_route: ?string, next_title: ?string}
     */
    public static function navigation(string $route): array
    {
        $routed = array_values(array_filter(self::all(), static fn (array $e): bool => $e['route'] !== null));
        $index = array_search($route, array_column($routed, 'route'), true);

        if ($index === false) {
            throw new \InvalidArgumentException(sprintf('Ukázka s route "%s" v katalogu není.', $route));
        }

        $prev = $routed[$index - 1] ?? null;
        $next = $routed[$index + 1] ?? null;

        return [
            'prev_route' => $prev['route'] ?? null,
            'prev_title' => $prev['title'] ?? null,
            'next_route' => $next['route'] ?? null,
            'next_title' => $next['title'] ?? null,
        ];
    }
}
