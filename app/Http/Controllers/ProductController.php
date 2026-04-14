<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;

class ProductController extends Controller
{
    /**
     * 建構子
     * 
     * @param \App\Services\ProductService $productService
     * @return void
     */
    public function __construct(
        private ProductService $productService
    ) {
        
    }

    /**
     * 取得熱門產品
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopularProducts()
    {
        $products = $this->productService->getPopularProducts();
        return response()->json($products);
    }
}
