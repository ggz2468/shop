<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;

class CartController extends Controller
{
    /**
     * 建構子
     * 
     * @param \App\Services\CartService $cartService
     * @return void
     */
    public function __construct(
        private CartService $cartService,
    ) {
        
    }

    /**
     * 顯示購物車內容
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $items = $this->cartService->getCartItems($request->user()->id);

        return response()->json([
            'data' => [
                'items' => $items,
                'total_quantity' => array_sum(array_column($items, 'quantity')),
            ],
        ]);
    }

    /**
     * 將產品加入購物車
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeItem(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->cartService->storeCartItem($request->user()->id, $validated['product_id'], $validated['quantity']);

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ], $result['status']);
    }
}
