<?php

namespace App\Http\Controllers\Api\v2\Client;

use App\Http\Controllers\Api\v2\SendResult;
use App\Http\Controllers\SendPushNotification;
use App\Http\Models\ChatMessage;
use App\Http\Models\Order;
use App\Http\Models\Shop;
use App\Http\Resources\ClientChatHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    use SendResult;
    use SendPushNotification;

    public function chatHistory($shop_id, $order_id)
    {
        $shop = Shop::find($shop_id);

        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $order = Order::find($order_id);

        if (!$order || $order->shop_id != $shop_id || $order->client_id != auth('api')->id()) {
            return $this->send('Order Not Found', [], 404);
        }

        $messages = ChatMessage::where('order_id', $order_id)->get();

        return $this->send('OK', ['messages' => ClientChatHistory::collection($messages)]);
    }

    public function createMessage($shop_id, $order_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'string|nullable',
            'img'     => 'image|nullable',
        ]);

        if ($validator->fails() || (!$request->message && !$request->hasFile('img'))) {
            return $this->send('Bad Request', [], 400);
        }

        $shop = Shop::find($shop_id);

        if (!$shop) {
            return $this->send('Shop Not Found', [], 404);
        }

        $order = Order::find($order_id);

        if (!$order || $order->shop_id != $shop_id || $order->client_id != auth('api')->id()) {
            return $this->send('Order Not Found', [], 404);
        }

        $chatMessage = new ChatMessage();
        $chatMessage->order_id = $order_id;
        $chatMessage->message = $request->message;
        $chatMessage->author = 'client';
        if ($request->hasFile('img')) {
            $chatMessage->img = $request->file('img')->store('chat_images', 'public');
        }
        $chatMessage->save();

        if ($order->courier->receive_pushes && $order->courier->playerId && $order->courier->shop_id == $shop_id) {
            $data = [
                "event" => "new_message",
                "order_id" => $order->id,
            ];

            $this->sendPushNotification($order->courier->playerId, 'Сообщение в чате', $data, false);
        }

        return $this->send('OK', []);
    }
}
