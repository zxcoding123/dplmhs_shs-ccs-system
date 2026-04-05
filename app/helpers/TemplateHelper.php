<?php

class TemplateHelper
{
    public static function activation($name, $email, $token)
    {
        $link = "https://localhost/csms-system/login/activate.php?email=$email&token=$token";

        return "
        <div style='margin:0;padding:0;background-color:#f4f6f9;font-family:Arial, sans-serif;'>
            <table align='center' width='100%' cellpadding='0' cellspacing='0' style='max-width:600px;margin:40px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);'>
                
                <tr>
                    <td style='background:#2d3748;padding:20px;text-align:center;color:#ffffff;'>
                        <h2 style='margin:0;'>CSMS System</h2>
                    </td>
                </tr>

                <tr>
                    <td style='padding:30px;'>
                        <h2 style='margin-top:0;color:#333;'>Hello, $name 👋</h2>

                        <p style='color:#555;line-height:1.6;'>
                            Welcome! Your account has been created successfully.
                            To get started, please confirm your email address by clicking the button below.
                        </p>

                        <div style='text-align:center;margin:30px 0;'>
                            <a href='$link' 
                               style='background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;display:inline-block;font-weight:bold;'>
                               Activate Account
                            </a>
                        </div>

                        <p style='color:#777;font-size:14px;line-height:1.5;'>
                            If the button doesn’t work, copy and paste the link below into your browser:
                        </p>

                        <p style='word-break:break-all;color:#4f46e5;font-size:13px;'>
                            $link
                        </p>

                        <hr style='margin:30px 0;border:none;border-top:1px solid #eee;'>

                        <p style='color:#999;font-size:12px;text-align:center;'>
                            If you did not create this account, you can safely ignore this email.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style='background:#f9fafb;padding:15px;text-align:center;font-size:12px;color:#888;'>
                        © " . date('Y') . " CSMS System. All rights reserved.
                    </td>
                </tr>

            </table>
        </div>
        ";
    }
}
