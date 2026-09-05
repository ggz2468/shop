<?php

namespace App\Http\Controllers;

use App\Enums\Order\PaymentMethod;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private OrderService $orderService,
    ) {}

    /**
     * 建立訂單
     *
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
