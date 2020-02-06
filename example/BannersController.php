<?php

namespace App\Http\Controllers\Api\v2\Client;

use App\Http\Controllers\Api\v2\SendResult;
use App\Http\Models\Banner;
use App\Http\Resources\BannerResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BannersController extends Controller
{
    use SendResult;

    public function index($shop_id)
    {
        $banners = Banner::where('shop_id', $shop_id)->get();

        return $this->send('OK', ['sales_banners' => BannerResource::collection($banners)]);
    }
}
