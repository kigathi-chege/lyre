<?php

$basicResponseCodes = generate_basic_model_response_codes();

return [
    0000 => "success",

    1001 => "register-user",
    1002 => "login-user",
    1003 => "get-current-user",
    1004 => "logout",
    1005 => "email-verified",
    1006 => "resend-email-verification",
    1007 => "otp-verified",

    3001 => "get-admin-stats",

    4004 => 'setup',

] + $basicResponseCodes;