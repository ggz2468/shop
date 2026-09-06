<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出貨資訊已建立</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f6fb; font-family: 'Noto Sans TC', 'PingFang TC', 'Microsoft JhengHei', sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f6fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 22px 24px; background: linear-gradient(135deg, #047857 0%, #065f46 100%); color: #ffffff; font-size: 20px; font-weight: 700;">
                            出貨資訊已建立
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px; font-size: 15px; line-height: 1.8;">
                            <p style="margin: 0 0 12px 0;">
                                {{ $memberName ?? '會員' }} 您好：
                            </p>

                            <p style="margin: 0 0 16px 0;">
                                您的訂單已建立出貨資訊，我們會依配送流程安排後續出貨。
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
                                <tr>
                                    <td style="padding: 14px 16px; border-top: 1px solid #e5e7eb; color: #4b5563; font-size: 13px;">
                                        物流追蹤編號
                                    </td>
                                    <td align="right" style="padding: 14px 16px; border-top: 1px solid #e5e7eb; font-size: 16px; font-weight: 700; color: #047857;">
                                        {{ $trackingNumber ?? '待物流商提供' }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 10px 0; font-weight: 700; color: #111827;">
                                收件資訊
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 0 0 16px 0; border-collapse: collapse; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 10px 12px; width: 110px; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: 13px;">收件人</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $recipientName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 12px; width: 110px; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: 13px;">聯絡電話</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ $recipientPhone }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 12px; width: 110px; background-color: #f9fafb; color: #4b5563; font-size: 13px;">收件地址</td>
                                    <td style="padding: 10px 12px; color: #111827;">{{ $recipientAddress }}</td>
                                </tr>
                            </table>

                            <p style="margin: 0; color: #4b5563; font-size: 13px;">
                                您可以至會員中心查看配送狀態。若收件資訊有誤，請盡快聯繫客服協助處理。
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