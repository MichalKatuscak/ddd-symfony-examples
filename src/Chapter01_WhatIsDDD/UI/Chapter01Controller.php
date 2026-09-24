<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\UI;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\ContextMap\CatalogProductTranslator;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter01Controller extends AbstractController
{
    private const array CATALOG = [
        ['name' => 'Symfony v praxi', 'price' => 59_900],
        ['name' => 'Domain-Driven Design', 'price' => 89_900],
        ['name' => 'Clean Architecture', 'price' => 74_900],
    ];

    #[Route('/examples/co-je-ddd', name: 'chapter01')]
    public function index(Request $request): Response
    {
        $products = array_map(
            static fn (array $p): Product => new Product(
                ProductId::generate(),
                $p['name'],
                new Money($p['price'], Currency::CZK),
            ),
            self::CATALOG,
        );

        $cart = Cart::empty();
        if ($request->isMethod('POST')) {
            foreach ($request->request->all('items') as $idx => $qty) {
                $qty = (int) $qty;
                if ($qty > 0 && isset($products[$idx])) {
                    $cart->add($products[$idx], $qty);
                }
            }
        }

        // Tentýž produkt ve dvou kontextech: společná je jen identita.
        $catalogProduct = new CatalogProduct(
            id: $products[0]->id,
            name: 'Symfony v praxi',
            description: 'Kompletní průvodce frameworkem',
            stockQty: 14,
            weightKg: 0.45,
        );

        $orderProduct = (new CatalogProductTranslator())->toOrderProduct(
            $catalogProduct,
            new Money(59_900, Currency::CZK),
            21,
        );

        return $this->render('examples/chapter01/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
            'catalogProduct' => $catalogProduct,
            'orderProduct' => $orderProduct,
            ...ExampleCatalog::navigation('chapter01'),
        ]);
    }
}
