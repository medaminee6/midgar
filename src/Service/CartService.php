<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_SESSION_KEY = 'shopping_cart';

    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Add a product to the cart
     */
    public function addItem(int $productId, int $quantity = 1): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'quantity' => $quantity,
                'added_at' => new \DateTime(),
            ];
        }

        $this->requestStack->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Remove a product from the cart
     */
    public function removeItem(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->requestStack->getSession()->set(self::CART_SESSION_KEY, $cart);
        }
    }

    /**
     * Update quantity of a product in the cart
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();

        if ($quantity <= 0) {
            $this->removeItem($productId);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            $this->requestStack->getSession()->set(self::CART_SESSION_KEY, $cart);
        }
    }

    /**
     * Get all items in the cart
     */
    public function getCart(): array
    {
        return $this->requestStack->getSession()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Get number of items in the cart
     */
    public function getCartCount(): int
    {
        $cart = $this->getCart();
        $count = 0;

        foreach ($cart as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Clear the entire cart
     */
    public function clearCart(): void
    {
        $this->requestStack->getSession()->remove(self::CART_SESSION_KEY);
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }

    /**
     * Get product IDs in cart
     */
    public function getProductIds(): array
    {
        return array_keys($this->getCart());
    }
}
