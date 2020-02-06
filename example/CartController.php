<?php

namespace App\Http\Controllers\Api\v2\Client;

use App\Http\Controllers\Api\v2\SendResult;
use App\Http\Models\Cart;
use App\Http\Models\CartItem;
use App\Http\Models\Product;
use App\Http\Models\Shop;
use App\Http\Resources\CartResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    use SendResult;

    public function getShopCart($shop_id)
    {
        $shop = Shop::find($shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();
        $user->carts()->where('shop_id', '!=', $shop->id)->delete();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'status'  => 'new',
            ]);
        }

        $cart->calculateProductPrice();

        return $this->send('OK', CartResource::make($cart));
    }

    public function addOneToCart(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('product_id')) {
            return $this->send('Bad Request', ['product_id' => 'Parameter product_id is required'], 400);
        }

        $shop = Shop::find($request->shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->send('Product Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();
        $user->carts()->where('shop_id', '!=', $shop->id)->delete();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'status'  => 'new',
            ]);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();
        if (!$cartItem) {
            $cartItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);
        } else {
            $cartItem->quantity = $cartItem->quantity + 1;
            $cartItem->save();
        }
        $cart->calculateProductPrice();
        $data = [
            'product' => ProductResource::make($product)
        ];
        return $this->send('Cart Updated', $data);
    }

    public function minusOneFromCart(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('product_id')) {
            return $this->send('Bad Request', ['product_id' => 'Parameter product_id is required'], 400);
        }

        $shop = Shop::find($request->shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->send('Product Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();
        if (!$cart) {
            return $this->send('Cart is empty', [], 400);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();
        if (!$cartItem) {
            return $this->send('No Product In Cart', [], 400);
        } else {
            $cartItem->quantity = $cartItem->quantity - 1;
            if (!$cartItem->quantity) {
                $cartItem->delete();
            } else {
                $cartItem->save();
            }
        }
        $cart->calculateProductPrice();
        $data = [
            'product' => ProductResource::make($product)
        ];
        return $this->send('Cart Updated', $data);
    }

    public function removeFromCart(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('product_id')) {
            return $this->send('Bad Request', ['product_id' => 'Parameter product_id is required'], 400);
        }

        $shop = Shop::find($request->shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->send('Product Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();

        if (!$cart) {
            return $this->send('Cart is empty', [], 400);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();
        if (!$cartItem) {
            return $this->send('No Product In Cart', [], 400);
        } else {
            $cartItem->delete();
        }

        $cart->calculateProductPrice();
        $data = [
            'product' => ProductResource::make($product)
        ];
        return $this->send('Cart Updated', $data);
    }

    public function getCartCount($shop_id)
    {
        $shop = Shop::find($shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();

        if (!$cart) {
            $data['count'] = 0;
            $data['all_price'] = 0;
        } else {
            $data['count'] = 0;
            $data['all_price'] = $cart->products_price;

            foreach ($cart->cartItems as $item) {
                $data['count'] += $item->quantity;
            }
        }

        return $this->send('OK', $data);
    }

    public function addCountToCart(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('product_id')) {
            return $this->send('Bad Request', ['product_id' => 'Parameter product_id is required'], 400);
        }
        if (!$request->has('count')) {
            return $this->send('Bad Request', ['count' => 'Parameter count is required'], 400);
        }

        $shop = Shop::find($request->shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->send('Product Not Found', [], 404);
        }

        $user = Auth::guard('api')->user();

        $cart = $user->carts()->where('shop_id', $shop->id)->first();
        $user->carts()->where('shop_id', '!=', $shop->id)->delete();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'status'  => 'new',
            ]);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();
        if (!$cartItem) {
            $cartItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $request->count]);
        } else {
            $cartItem->quantity = $request->count;
            $cartItem->save();
        }

        $cart->calculateProductPrice();
        $data = [
            'product' => ProductResource::make($product)
        ];
        return $this->send('Cart Updated', $data);
    }
}
