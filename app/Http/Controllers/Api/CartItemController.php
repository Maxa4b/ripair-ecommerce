<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartItemRequest;
use App\Models\Catalog\ProductVariant;
use App\Models\Commerce\Cart;
use App\Models\Commerce\CartItem;
use App\Services\Commerce\CartService;
use Illuminate\Http\JsonResponse;

class CartItemController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {
    }

    public function store(Cart $cart, CartItemRequest $request): JsonResponse
    {
        $this->guardCart($cart);
        $variant = ProductVariant::findOrFail($request->integer('variant_id'));
        $item = $this->cartService->addItem($cart, $variant, $request->integer('quantity', 1));

        return response()->json($item->load('variant.product'));
    }

    public function update(Cart $cart, CartItem $item, CartItemRequest $request): JsonResponse
    {
        $this->guardCart($cart, $item);

        $this->cartService->updateItem($item, $request->integer('quantity'));

        return response()->json($cart->fresh('items'));
    }

    public function destroy(Cart $cart, CartItem $item): JsonResponse
    {
        $this->guardCart($cart, $item);
        $this->cartService->removeItem($item);

        return response()->json(['status' => 'ok']);
    }

    private function guardCart(Cart $cart, ?CartItem $item = null): void
    {
        $token = request()->string('token')->toString();
        $userMatches = request()->user()?->id === $cart->user_id;
        $tokenMatches = $token && hash_equals($cart->token, $token);

        abort_unless($userMatches || $tokenMatches, 403);

        if ($item) {
            abort_unless($item->cart_id === $cart->id, 403);
        }
    }
}
