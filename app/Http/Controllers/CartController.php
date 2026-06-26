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
    public function index(Request $request)
    {
        $items = $this->cartService->getCartItems($request->user()->id);

        return response()->json([
            'data' => [
                'items' => $items,
                'total_quantity' => array_sum(array_column($items, 'quantity')),
                'subtotal' => array_sum(array_column($items, 'subtotal')),
            ],
        ]);
    }
}
