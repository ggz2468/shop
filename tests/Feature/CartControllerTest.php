<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試未驗證的訪客無法存取購物車相關的 API 端點
     */
    public function test_guest_cannot_access_cart_index(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    /**
     * 測試已驗證的會員可以存取購物車相關的 API 端點
     */
    public function test_index_returns_empty_cart_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $this->mockCartItemsForMember($member, []);

        $response = $this->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                        ],
                    ],
                    'total_quantity',
                ],
            ])
            ->assertJson([
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                ],
            ]);
    }

    /**
     * 測試已驗證的會員可以存取購物車相關的 API 端點，並返回購物車中的產品
     */
    public function test_index_returns_cart_items_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        // 模擬購物車中有產品
        $cartItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
            ],
        ];

        $this->mockCartItemsForMember($member, $cartItems);

        $response = $this->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                        ],
                    ],
                    'total_quantity',
                ],
            ])
            ->assertJson([
                'data' => [
                    'items' => $cartItems,
                    'total_quantity' => 3,
                ],
            ]);
    }

    /**
     * 測試查詢購物車時會使用目前登入會員的 ID
     */
    public function test_index_uses_authenticated_member_id_to_fetch_cart_items(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $this->mockCartItemsForMember($member, []);

        $response = $this->getJson('/api/cart');

        $response->assertOk();
    }

    /**
     * 測試購物車產品項目回傳指定欄位結構
     */
    public function test_index_returns_expected_cart_item_structure(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $this->mockCartItemsForMember($member, [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
        ]);

        $response = $this->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                        ],
                    ],
                    'total_quantity',
                ],
            ]);
    }

    /**
     * 測試購物車總數量會加總所有產品項目的數量
     */
    public function test_index_calculates_total_quantity_from_cart_items(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $this->mockCartItemsForMember($member, [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
            [
                'product_id' => 2,
                'quantity' => 5,
            ],
            [
                'product_id' => 3,
                'quantity' => 1,
            ],
        ]);

        $response = $this->getJson('/api/cart');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total_quantity' => 8,
                ],
            ]);
    }

    /**
     * 測試購物車查詢會套用 RateLimiter。
     */
    public function test_index_is_rate_limited_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('getCartItems')
            ->times(60)
            ->with($member->id)
            ->andReturn([]);

        $this->app->instance(CartService::class, $cartService);

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->getJson('/api/cart')->assertOk();
        }

        $this->getJson('/api/cart')->assertStatus(429);
    }

    /**
     * 測試未驗證的訪客無法加入產品到購物車。
     */
    public function test_guest_cannot_add_product_to_cart(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'product_id' => 1,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * 測試加入產品時必須提供產品 ID 與數量。
     */
    public function test_store_returns_422_when_required_payload_is_missing(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')->never();

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    /**
     * 測試加入產品時產品 ID 必須存在。
     */
    public function test_store_returns_422_when_product_does_not_exist(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')->never();

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => 999,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    /**
     * 測試加入產品時產品 ID 必須是整數。
     */
    public function test_store_returns_422_when_product_id_is_not_integer(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')->never();

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => 'abc',
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    /**
     * 測試加入產品時數量必須是正整數。
     */
    public function test_store_returns_422_when_quantity_is_invalid(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')->never();

        $this->app->instance(CartService::class, $cartService);

        foreach ([0, -1, 'abc'] as $quantity) {
            $response = $this->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity']);
        }
    }

    /**
     * 測試已驗證的會員可以加入產品到購物車，並回傳 service 結果。
     */
    public function test_store_adds_product_to_cart_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')
            ->once()
            ->with($member->id, $product->id, 2)
            ->andReturn([
                'status' => 201,
                'message' => '產品已加入購物車。',
                'data' => [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ]);

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '產品已加入購物車。',
                'data' => [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ]);
    }

    /**
     * 測試加入產品後可從購物車查詢 API 讀回該產品。
     */
    public function test_store_persists_product_to_cart_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();
        Cache::forget("cart:member:{$member->id}:items");

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(201)
            ->assertJson([
                'message' => '產品已加入購物車。',
                'data' => [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ]);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 2,
                        ],
                    ],
                    'total_quantity' => 2,
                ],
            ]);
    }

    /**
     * 測試不同會員的購物車資料會互相隔離。
     */
    public function test_store_keeps_cart_items_isolated_between_members(): void
    {
        $member = Member::factory()->create();
        $anotherMember = Member::factory()->create();
        $product = Product::factory()->create();
        Cache::forget("cart:member:{$member->id}:items");
        Cache::forget("cart:member:{$anotherMember->id}:items");

        Sanctum::actingAs($member);
        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(201);

        Sanctum::actingAs($anotherMember);
        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                ],
            ]);
    }

    /**
     * 測試重複加入相同產品時會累加購物車中的產品數量。
     */
    public function test_store_increments_quantity_when_product_already_exists_in_cart(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();
        Cache::forget("cart:member:{$member->id}:items");

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(201);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(201);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 3,
                        ],
                    ],
                    'total_quantity' => 3,
                ],
            ]);
    }

    /**
     * 測試加入產品時 service 回傳失敗狀態應原樣回傳。
     */
    public function test_store_propagates_service_failure_status_and_message(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')
            ->once()
            ->with($member->id, $product->id, 3)
            ->andReturn([
                'status' => 409,
                'message' => '產品無法加入購物車。',
            ]);

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => '產品無法加入購物車。',
            ]);
    }

    /**
     * 測試加入產品時 service 儲存失敗應回傳 503 錯誤訊息。
     */
    public function test_store_returns_503_when_cart_service_cannot_store_item(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')
            ->once()
            ->with($member->id, $product->id, 1)
            ->andReturn([
                'status' => 503,
                'message' => '產品加入會員購物車失敗，請稍後再試。',
            ]);

        $this->app->instance(CartService::class, $cartService);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(503)
            ->assertJson([
                'message' => '產品加入會員購物車失敗，請稍後再試。',
                'data' => [],
            ]);
    }

    /**
     * 測試加入產品會套用 RateLimiter。
     */
    public function test_store_is_rate_limited_for_authenticated_member(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);
        $product = Product::factory()->create();

        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('storeCartItem')
            ->times(60)
            ->with($member->id, $product->id, 1)
            ->andReturn([
                'status' => 201,
                'message' => '產品已加入購物車。',
            ]);

        $this->app->instance(CartService::class, $cartService);

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertStatus(201);
        }

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(429);
    }

    /**
     * 模擬指定會員的購物車項目
     *
     * @param \App\Models\Member $member
     * @param array<int, array<string, mixed>> $cartItems
     */
    private function mockCartItemsForMember(Member $member, array $cartItems): void
    {
        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('getCartItems')
            ->once()
            ->with($member->id)
            ->andReturn($cartItems);

        $this->app->instance(CartService::class, $cartService);
    }
}
