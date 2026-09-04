<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單已建立</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f6fb; font-family: 'Noto Sans TC', 'PingFang TC', 'Microsoft JhengHei', sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f6fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 22px 24px; background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); color: #ffffff; font-size: 20px; font-weight: 700;">
                            訂單已建立
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px; font-size: 15px; line-height: 1.8;">
                            <p style="margin: 0 0 12px 0;">
                                {{ $memberName ?? '會員' }} 您好：
                            </p>

                            <p style="margin: 0 0 16px 0;">
                                您的訂單已成功建立，我們已收到您的訂購資訊。
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 0 0 20px 0; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 14px 16px; color: #4b5563; font-size: 13px;">
                                        訂單編號
                                    </td>
                                    <td align="right" style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #111827;">
                                        {{ $orderNumber }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 10px 0; font-weight: 700; color: #111827;">
                                訂購明細
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin: 0 0 20px 0; border: 1px solid #e5e7eb;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th align="left" style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: 12px; font-weight: 700;">商品</th>
                                        <th align="center" style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: 12px; font-weight: 700;">數量</th>
                                        <th align="right" style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: 12px; font-weight: 700;">小計</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderDetails as $orderDetail)
                                        <tr>
                                            <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top;">
                                                <div style="font-weight: 700; color: #111827;">
                                                    {{ $orderDetail->product_name }}
                                                </div>
                                                <div style="margin-top: 4px; color: #6b7280; font-size: 12px; line-height: 1.6;">
                                                    SKU：{{ $orderDetail->product_sku }}<br>
                                                    顏色：{{ $orderDetail->product_color }} / 尺寸：{{ $orderDetail->product_size }}<br>
                                                    單價：NT$ {{ number_format($orderDetail->product_price) }}
                                                </div>
                                            </td>
                                            <td align="center" style="padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; color: #374151;">
                                                {{ $orderDetail->quantity }}
                                            </td>
                                            <td align="right" style="padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; color: #111827; font-weight: 700;">
                                                NT$ {{ number_format($orderDetail->subtotal) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 0 0 16px 0;">
                                <tr>
                                    <td style="padding: 4px 0; color: #4b5563;">商品小計</td>
                                    <td align="right" style="padding: 4px 0; color: #111827;">NT$ {{ number_format($itemsSubtotal) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0; color: #4b5563;">稅額</td>
                                    <td align="right" style="padding: 4px 0; color: #111827;">NT$ {{ number_format($taxAmount) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0; color: #4b5563;">運費</td>
                                    <td align="right" style="padding: 4px 0; color: #111827;">NT$ {{ number_format($shippingFee) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0 0 0; border-top: 1px solid #e5e7eb; color: #111827; font-size: 16px; font-weight: 700;">訂單總金額</td>
                                    <td align="right" style="padding: 12px 0 0 0; border-top: 1px solid #e5e7eb; color: #1d4ed8; font-size: 18px; font-weight: 700;">NT$ {{ number_format($totalAmount) }}</td>
                                </tr>
                            </table>

                            <p style="margin: 0; color: #4b5563; font-size: 13px;">
                                您可以至會員中心查看訂單狀態。若訂單內容有誤，請盡快聯繫客服協助處理。
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; line-height: 1.7; color: #6b7280;">
                            此為系統自動發送信件，請勿直接回覆。
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>