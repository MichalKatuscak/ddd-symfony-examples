<?php

declare(strict_types=1);
namespace App\Twig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ClassNameExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('class_name', fn($obj) => (new \ReflectionClass($obj))->getShortName())];
    }
}
