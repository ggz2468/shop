<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentCheckoutController extends Controller
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private OrderPaymentCheckoutService $orderPaymentCheckoutService,
    ) {}

    /**
     * 提供客戶端串接金流時所需的資料
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $result = $this->orderPaymentCheckoutService->show($request->user()->id, $order);
        $response = [];

        if (array_key_exists('message', $result)) {
            $response['message'] = $result['message'];
        }

        if (array_key_exists('data', $result)) {
            $response['data'] = $result['data'];
        }

        return response()->json($response, $result['status']);
    }
}
