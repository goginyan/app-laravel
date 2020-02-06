<?php

namespace App\Http\Controllers\Api\v2\Client;

use App\Http\Controllers\Api\v2\SendResult;
use App\Http\Models\ClientSearch;
use App\Http\Models\Product;
use App\Http\Models\Shop;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller {
	use SendResult;

	public function index( Request $request )
    {
		if (!$request->has('shop_id')) {
			return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
		}
		if (!$request->has('page')) {
			return $this->send('Bad Request', ['page' => 'Parameter page is required'], 400);
		}
//		if (!$request->has('only_sales')) {
//			return $this->send('Bad Request', ['only_sales' => 'Parameter only_sales is required'], 400);
//		}

        $sort_by = '';
		$order = 'asc';
        if ($request->has('sort')) {
            switch ($request->sort) {
                case '0-1':
                    $sort_by = 'price';
                    $order = 'asc';
                    break;
                case '1-0':
                    $sort_by = 'price';
                    $order = 'desc';
                    break;
                case 'ABC':
                    $sort_by = 'name';
                    $order = 'asc';
                    break;
                default:
                    break;
            }
        }

        $products = Product::where('status', '1')->where('shop_id', $request->shop_id)->where('price', '<>', 0)->whereNotNull('price')
            ->when($request->only_sales, function ($q) use ($request) {
                return $q->where('sale', '!=', 0);
        })->when(isset($request->category_id), function ($q) use ($request) {
            return $q->whereHas('category', function ($q) use ($request){
                return $q->where('id', $request->category_id)->orWhere('parentId', $request->category_id);
            });
        })->when(isset($request->sub_category_id), function ($q) use ($request) {
            return $q->whereHas('category', function ($q) use ($request){
                return $q->where('id', $request->sub_category_id);
            });
        })->when($sort_by, function ($q) use ($request, $sort_by, $order) {
            if ($order === 'asc') {
                return $q->orderBy($sort_by);
            } else {
                return $q->orderByDesc($sort_by);
            }

        })->paginate(15);

		$data     = [
			'last_page' => $products->lastPage(),
			'page'      => $products->currentPage(),
            'total' => $products->total(),
            'per_page' => 15,
			'products'  => ProductResource::collection($products)
		];

		return $this->send('OK', $data);
	}

    public function show( Request $request, Shop $shop, Product $product )
    {
        $user = Auth::guard('api')->user();
	    $user->viewedProducts()->syncWithoutDetaching([$product->id]);
        return $this->send('OK', ['product' => ProductResource::make($product)]);
    }

    public function search( $shop_id, string $text )
    {
        $shop = Shop::find($shop_id);
        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $products = $shop->products()->where('price', '<>', 0)->whereNotNull('price')->where('status', '1')->where('name', 'LIKE', "%$text%")->get();

//      Save search data
        ClientSearch::create([
            'client_id' => auth('api')->id(),
            'shop_id'   => $shop_id,
            'search'    => $text
        ]);

        return $this->send('OK', ['products' => ProductResource::collection($products)]);
    }

    public function viewed(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('page')) {
            return $this->send('Bad Request', ['page' => 'Parameter page is required'], 400);
        }

        $sort_by = '';
        $order = 'asc';
        if ($request->has('sort')) {
            switch ($request->sort) {
                case '0-1':
                    $sort_by = 'price';
                    $order = 'asc';
                    break;
                case '1-0':
                    $sort_by = 'price';
                    $order = 'desc';
                    break;
                case 'ABC':
                    $sort_by = 'name';
                    $order = 'asc';
                    break;
                default:
                    break;
            }
        }

        $user = Auth::guard('api')->user();

        $products = $user->viewedProducts()->where('status', '1')->where('shop_id', $request->shop_id)
            ->when($sort_by, function ($q) use ($request, $sort_by, $order) {
            if ($order === 'asc') {
                return $q->orderBy($sort_by);
            } else {
                return $q->orderByDesc($sort_by);
            }

        })->paginate(15);

        $data     = [
            'last_page' => $products->lastPage(),
            'page'      => $products->currentPage(),
            'total' => $products->total(),
            'per_page' => 15,
            'products'  => ProductResource::collection($products)
        ];

        return $this->send('OK', $data);
    }

    public function favorites(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->send('Bad Request', ['shop_id' => 'Parameter shop_id is required'], 400);
        }
        if (!$request->has('page')) {
            return $this->send('Bad Request', ['page' => 'Parameter page is required'], 400);
        }

        $sort_by = '';
        $order = 'asc';
        if ($request->has('sort')) {
            switch ($request->sort) {
                case '0-1':
                    $sort_by = 'price';
                    $order = 'asc';
                    break;
                case '1-0':
                    $sort_by = 'price';
                    $order = 'desc';
                    break;
                case 'ABC':
                    $sort_by = 'name';
                    $order = 'asc';
                    break;
                default:
                    break;
            }
        }

        $user = Auth::guard('api')->user();

        $products = $user->favorites()->where('status', '1')->where('shop_id', $request->shop_id)
            ->when($sort_by, function ($q) use ($request, $sort_by, $order) {
                if ($order === 'asc') {
                    return $q->orderBy($sort_by);
                } else {
                    return $q->orderByDesc($sort_by);
                }

            })->paginate(15);

        $data     = [
            'last_page' => $products->lastPage(),
            'page'      => $products->currentPage(),
            'total' => $products->total(),
            'per_page' => 15,
            'products'  => ProductResource::collection($products)
        ];

        return $this->send('OK', $data);
    }

    public function addToFavorites( Product $product )
    {
        $user = Auth::guard('api')->user();
        $user->favorites()->syncWithoutDetaching([$product->id]);
        return $this->send('OK', []);
    }

    public function removeFromFavorites( Product $product )
    {
        $user = Auth::guard('api')->user();
        $user->favorites()->detach([$product->id]);
        return $this->send('OK', []);
    }
}
