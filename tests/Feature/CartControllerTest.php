<?php

namespace Tests\Feature;

use App\Services\CartService;
use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    /**
     * 測試未驗證的訪客無法存取購物車相關的 API 端點
     */
    public function test_guest_cannot_access_cart_index(): void
    {
        $response = $this->getJson('/api/carts');

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

        $response = $this->getJson('/api/carts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                            'price',
                            'subtotal',
                        ],
                    ],
                    'total_quantity',
                    'subtotal',
                ],
            ])
            ->assertJson([
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                    'subtotal' => 0,
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
                'price' => 100,
                'subtotal' => 200,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
                'price' => 200,
                'subtotal' => 200,
            ],
        ];

        $this->mockCartItemsForMember($member, $cartItems);

        $response = $this->getJson('/api/carts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                            'price',
                            'subtotal',
                        ],
                    ],
                    'total_quantity',
                    'subtotal',
                ],
            ])
            ->assertJson([
                'data' => [
                    'items' => $cartItems,
                    'total_quantity' => 3,
                    'subtotal' => 400,
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

        $response = $this->getJson('/api/carts');

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
                'price' => 100,
                'subtotal' => 200,
            ],
        ]);

        $response = $this->getJson('/api/carts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'product_id',
                            'quantity',
                            'price',
                            'subtotal',
                        ],
                    ],
                    'total_quantity',
                    'subtotal',
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
                'price' => 100,
                'subtotal' => 200,
            ],
            [
                'product_id' => 2,
                'quantity' => 5,
                'price' => 80,
                'subtotal' => 400,
            ],
            [
                'product_id' => 3,
                'quantity' => 1,
                'price' => 250,
                'subtotal' => 250,
            ],
        ]);

        $response = $this->getJson('/api/carts');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total_quantity' => 8,
                ],
            ]);
    }

    /**
     * 測試購物車小計總額會加總所有產品項目的小計
     */
    public function test_index_calculates_subtotal_from_cart_item_subtotals(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member);

        $this->mockCartItemsForMember($member, [
            [
                'product_id' => 1,
                'quantity' => 2,
                'price' => 100,
                'subtotal' => 200,
            ],
            [
                'product_id' => 2,
                'quantity' => 3,
                'price' => 125,
                'subtotal' => 375,
            ],
        ]);

        $response = $this->getJson('/api/carts');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'subtotal' => 575,
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
            $this->getJson('/api/carts')->assertOk();
        }

        $this->getJson('/api/carts')->assertStatus(429);
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
