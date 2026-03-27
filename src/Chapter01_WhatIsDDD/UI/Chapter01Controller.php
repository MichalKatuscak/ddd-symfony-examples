<?php

declare(strict_types=1);
namespace App\Chapter01_WhatIsDDD\UI;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;
use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\ContextMap\CatalogProductTranslator;
use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter01Controller extends AbstractController
{
    private static array $catalog = [
        ['name' => 'Symfony v praxi', 'price' => 59900],
        ['name' => 'Domain-Driven Design', 'price' => 89900],
        ['name' => 'Clean Architecture', 'price' => 74900],
    ];

    #[Route('/examples/co-je-ddd', name: 'chapter01')]
    public function index(Request $request): Response
    {
        $products = array_map(
            fn($p) => new Product(ProductId::generate(), $p['name'], new Price($p['price'], 'CZK')),
            self::$catalog,
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

        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Symfony v praxi',
            description: 'Kompletní průvodce frameworkem',
            stockQty: 14,
            weightKg: 0.45,
        );

        $orderProduct = new OrderProduct(
            productId: 'prod-42',
            unitPriceCents: 59900,
            currency: 'CZK',
            taxRate: 0.21,
        );

        $translator = new CatalogProductTranslator();
        $translatedProduct = $translator->toOrderProduct($catalogProduct, 59900, 'CZK', 0.21);

        return $this->render('examples/chapter01/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
            'catalogProduct' => $catalogProduct,
            'orderProduct' => $orderProduct,
            'translator' => true,
            'translatedProduct' => $translatedProduct,
        ]);
    }
}
