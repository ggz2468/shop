<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Resources\ProductResource;

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
     * 取得首頁所需的熱門商品列表
     */
    public function index()
    {
        $products = $this->productService->getPopularProducts();
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
