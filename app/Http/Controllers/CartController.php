<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;
use App\Models\Product;

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
     * 取得購物車內容
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

    /**
     * 更新購物車中指定產品的數量
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, Product $product)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->cartService->updateCartItem($request->user()->id, $product->id, $validated['quantity']);

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ], $result['status']);
    }

    /**
     * 刪除購物車中指定的產品
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyItem(Request $request, Product $product)
    {
        $result = $this->cartService->destroyCartItem($request->user()->id, $product->id);

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'] ?? [],
        ], $result['status']);
    }
}
