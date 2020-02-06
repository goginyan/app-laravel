<?php

namespace App\Http\Controllers\Api\v2\Client;

use App\Http\Controllers\Api\v2\SendResult;
use App\Http\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller {
	use SendResult;

	public function categories(Request $request) {
		if (!request()->has('shop_id')) {
            return $this->send('Bad Request', [], 400);
        }
        $categories = Category::where('shop_id', request()->shop_id)->whereNull('parentId')
            ->whereHas('subcategories', function ($q) use ($request) {
                return $q->whereHas('products', function ($q) use ($request) {
                    return $q->where('status', 1)->where('price', '!=', 0)
                        ->when($request->only_sales, function ($q) {
                            return $q->where('sale', '!=', 0);
                        });
                });
            })->orderBy('priority', 'asc')->get();

		return $this->send('OK', ['categories' => CategoryResource::collection($categories)]);
	}

	public function subcategories(Request $request) {
		if (!request()->has('shop_id')) {
			return $this->send('Bad Request', [], 400);
		}
		if (!request()->has('category_id')) {
			return $this->send('Bad Request', [], 400);
		}
		$categories = Category::where('shop_id', request()->shop_id)
            ->where('parentId', request()->category_id)->whereHas('products', function ($q) {
                return $q->where('status', 1)->where('price', '!=', 0);
            })->when($request->only_sales, function ($q) {
                return $q->whereHas('products', function ($q) {
                    return $q->where('sale', '!=', 0);
                });
            })->orderBy('priority', 'asc')->get();

		return $this->send('OK', ['categories' => CategoryResource::collection($categories)]);
	}
}
