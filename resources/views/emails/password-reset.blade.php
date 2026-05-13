<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重設密碼</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f6fb; font-family: 'Noto Sans TC', 'PingFang TC', 'Microsoft JhengHei', sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f6fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 22px 24px; background: linear-gradient(135deg, #b45309 0%, #92400e 100%); color: #ffffff; font-size: 20px; font-weight: 700;">
                            重設密碼
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px; font-size: 15px; line-height: 1.8;">
                            <p style="margin: 0 0 12px 0;">
                                {{ $memberName ?? '會員' }} 您好：
                            </p>

                            <p style="margin: 0 0 12px 0;">
                                我們收到您重設密碼的請求，請點擊下方按鈕設定新密碼。
                            </p>

                            <div style="margin: 24px 0; text-align: center;">
                                <a href="{{ $resetPasswordUrl }}" style="display: inline-block; background-color: #b45309; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 12px 24px; border-radius: 10px;">
                                    前往重設密碼
                                </a>
                            </div>

                            <p style="margin: 0 0 12px 0; color: #374151;">
                                此連結將於 {{ $expiresInMinutes ?? 30 }} 分鐘後失效，且僅可使用一次。
                            </p>

                            <p style="margin: 0 0 8px 0; color: #4b5563; font-size: 13px;">
                                若按鈕無法點擊，請複製以下連結到瀏覽器開啟：
                            </p>
                            <p style="margin: 0; word-break: break-all; font-size: 13px; color: #b45309;">
                                {{ $resetPasswordUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; line-height: 1.7; color: #6b7280;">
                            若您未申請重設密碼，請忽略此信件並考慮更新帳號安全設定。此為系統自動發送信件，請勿直接回覆。
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
