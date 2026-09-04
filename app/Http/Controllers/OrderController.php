<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Enums\Order\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * 建構子
     * 
     * @param \App\Services\OrderService $orderService
     * @return void
     */
    public function __construct(
        private OrderService $orderService,
    ) {
        
    }

    /**
     * 建立訂單
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        Validator::make([
            'idempotency_key' => $idempotencyKey,
        ], [
            'idempotency_key' => ['required', 'string', 'max:64'],
        ])->validate();

        $validated = $request->validate([
            'payment_method' => [
                'required',
                'integer',
                Rule::in(array_map(fn (PaymentMethod $paymentMethod): int => $paymentMethod->value, PaymentMethod::cases())),
            ],
        ]);

        $result = $this->orderService->storeOrder($request->user()->id, $idempotencyKey, $validated['payment_method']);

        $response = [
            'message' => $result['message'],
        ];

        if (array_key_exists('data', $result)) {
            $response['data'] = $result['data'];
        }

        return response()->json($response, $result['status']);
    }
}
