<?php

namespace App\Http\Controllers;

use App\Services\EcpayPaymentCallbackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EcpayPaymentCallbackController extends Controller
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private EcpayPaymentCallbackService $ecpayPaymentCallbackService,
    ) {}

    /**
     * 處理綠界金流回應資訊
     */
    public function __invoke(Request $request): Response
    {
        $result = $this->ecpayPaymentCallbackService->handle($request->all());

        return response($result['content'], $result['status'])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
