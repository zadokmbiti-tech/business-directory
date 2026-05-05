<?php
define('MPESA_ENV', 'sandbox');

define('CONSUMER_KEY', 'Yfl69QrbEBSUjmeuMByhrjobD2ynnUo2zzown85IzmVGEwho');
define('CONSUMER_SECRET', 'sBdj9nP6TSbKLNulHnNzJZdX99RAtBJHzCeA8rWyGVBROsGxW3HAbVkM6TJe51uo');
define('SHORTCODE', '174379');
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('CALLBACK_URL', 'https://www.zaddybusinessnetwork.kesug.com/mpesa/callback.php');

define('BASE_URL', MPESA_ENV === 'sandbox'
    ? 'https://sandbox.safaricom.co.ke'
    : 'https://api.safaricom.co.ke');