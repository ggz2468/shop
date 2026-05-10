<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>電子郵件驗證</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f6fb; font-family: 'Noto Sans TC', 'PingFang TC', 'Microsoft JhengHei', sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f6fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 22px 24px; background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: #ffffff; font-size: 20px; font-weight: 700;">
                            電子郵件驗證
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px; font-size: 15px; line-height: 1.8;">
                            <p style="margin: 0 0 12px 0;">
                                {{ $memberName ?? '會員' }} 您好：
                            </p>

                            <p style="margin: 0 0 12px 0;">
                                歡迎加入，請點擊下方按鈕完成電子郵件驗證。
                            </p>

                            <div style="margin: 24px 0; text-align: center;">
                                <a href="{{ $verificationUrl }}" style="display: inline-block; background-color: #0f766e; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 12px 24px; border-radius: 10px;">
                                    驗證電子郵件
                                </a>
                            </div>

                            <p style="margin: 0 0 12px 0; color: #374151;">
                                此連結將於 {{ $expiresInMinutes ?? 30 }} 分鐘後失效，請盡快完成驗證。
                            </p>

                            <p style="margin: 0 0 8px 0; color: #4b5563; font-size: 13px;">
                                若按鈕無法點擊，請複製以下連結到瀏覽器開啟：
                            </p>
                            <p style="margin: 0; word-break: break-all; font-size: 13px; color: #0f766e;">
                                {{ $verificationUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; line-height: 1.7; color: #6b7280;">
                            若您未申請本次驗證，請忽略此信件。此為系統自動發送信件，請勿直接回覆。
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>