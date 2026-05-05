<?php
require_once '../config/dbconnection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$business_id = (int)($_GET['business_id'] ?? 0);
$conn = getDBConnection();

// Get business info
$stmt = $conn->prepare("SELECT * FROM businesses WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $business_id, $_SESSION['user_id']);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$business) {
    header('Location: ../index.php');
    exit;
}

// Get plans
$plans = $conn->query("SELECT * FROM plans ORDER BY price ASC")->fetch_all(MYSQLI_ASSOC);
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe – Zaddy Business Network</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #1a2332; }

        header { background: #1a2332; padding: 16px 24px; }
        header a { color: #e8a020; font-size: 22px; font-weight: 700; text-decoration: none; }

        .container { max-width: 860px; margin: 40px auto; padding: 0 16px; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        .sub { color: #5a6a7a; margin-bottom: 32px; }

        .plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 36px; }

        .plan {
            background: #fff;
            border: 2px solid #e0e6ef;
            border-radius: 12px;
            padding: 28px 24px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .plan:hover { border-color: #e8a020; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .plan.selected { border-color: #e8a020; background: #fffbf2; }
        .plan.popular::before {
            content: 'POPULAR';
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: #e8a020; color: #fff; font-size: 10px; font-weight: 700;
            padding: 3px 12px; border-radius: 20px; letter-spacing: 1px;
        }
        .plan h2 { font-size: 20px; margin-bottom: 4px; }
        .plan .price { font-size: 32px; font-weight: 700; color: #e8a020; margin: 10px 0 4px; }
        .plan .price span { font-size: 14px; color: #5a6a7a; font-weight: 400; }
        .plan ul { list-style: none; margin-top: 14px; }
        .plan ul li { font-size: 13.5px; color: #3a4a5a; padding: 4px 0; }
        .plan ul li::before { content: '✓ '; color: #e8a020; font-weight: 700; }

        .pay-form { background: #fff; border-radius: 12px; padding: 28px 24px; border: 1px solid #e0e6ef; }
        .pay-form h3 { margin-bottom: 18px; font-size: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #3a4a5a; }
        input { width: 100%; padding: 11px 14px; border: 1.5px solid #d0d8e4; border-radius: 8px; font-size: 14px; margin-bottom: 18px; outline: none; }
        input:focus { border-color: #e8a020; }

        .btn {
            width: 100%; padding: 14px;
            background: #e8a020; color: #fff;
            border: none; border-radius: 8px;
            font-size: 16px; font-weight: 700;
            cursor: pointer; transition: background .2s;
        }
        .btn:hover { background: #c88010; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }

        .msg { margin-top: 16px; padding: 12px 16px; border-radius: 8px; font-size: 14px; display: none; }
        .msg.success { background: #e6f9ef; color: #1a7a40; border: 1px solid #b2dfcb; display: block; }
        .msg.error   { background: #fdecea; color: #b71c1c; border: 1px solid #f5c6c6; display: block; }
        .msg.info    { background: #e8f0fe; color: #1a2d6e; border: 1px solid #b3c6f7; display: block; }

        .business-tag { background: #f0f4ff; border: 1px solid #d0d8f0; border-radius: 8px; padding: 10px 16px; margin-bottom: 24px; font-size: 14px; color: #1a2332; }
        .business-tag strong { color: #e8a020; }
    </style>
</head>
<body>

<header>
    <a href="../index.php">Zaddy.</a>
</header>

<div class="container">
    <h1>Choose a Subscription Plan</h1>
    <p class="sub">Activate your listing on Zaddy Business Network</p>

    <div class="business-tag">
        Subscribing for: <strong><?= htmlspecialchars($business['name']) ?></strong>
    </div>

    <!-- Plans -->
    <div class="plans">
        <?php foreach ($plans as $i => $plan): ?>
        <div class="plan <?= $i === 1 ? 'popular' : '' ?>" 
             onclick="selectPlan(<?= $plan['id'] ?>, <?= $plan['price'] ?>, this)">
            <h2><?= htmlspecialchars($plan['name']) ?></h2>
            <div class="price">KSh <?= number_format($plan['price'], 0) ?> <span>/ month</span></div>
            <ul>
                <?php foreach (explode(',', $plan['features']) as $f): ?>
                <li><?= trim(htmlspecialchars($f)) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Form -->
    <div class="pay-form">
        <h3>Pay via M-Pesa</h3>
        <label>M-Pesa Phone Number</label>
        <input type="tel" id="phone" placeholder="e.g. 0799867545" maxlength="10" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">

        <label>Selected Plan</label>
        <input type="text" id="plan_label" placeholder="Select a plan above" readonly>

        <button class="btn" id="pay-btn" onclick="pay()" disabled>Pay Now</button>
        <div class="msg" id="msg"></div>
    </div>
</div>

<script>
let selectedPlanId   = null;
let selectedAmount   = null;
const businessId     = <?= $business_id ?>;

function selectPlan(planId, price, el) {
    document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    selectedPlanId = planId;
    selectedAmount = price;
    document.getElementById('plan_label').value = el.querySelector('h2').innerText + ' — KSh ' + price.toLocaleString() + '/month';
    document.getElementById('pay-btn').disabled = false;
}

function pay() {
    const phone = document.getElementById('phone').value.trim();
    const msg   = document.getElementById('msg');
    const btn   = document.getElementById('pay-btn');

    if (!phone || phone.length < 10) {
        msg.className = 'msg error';
        msg.innerText = 'Please enter a valid M-Pesa phone number.';
        return;
    }
    if (!selectedPlanId) {
        msg.className = 'msg error';
        msg.innerText = 'Please select a plan first.';
        return;
    }

    btn.disabled  = true;
    btn.innerText = 'Processing...';
    msg.className = 'msg info';
    msg.innerText = '⏳ STK Push sent! Check your phone and enter your M-Pesa PIN.';

    const formData = new FormData();
    formData.append('phone', phone);
    formData.append('plan_id', selectedPlanId);
    formData.append('business_id', businessId);

    fetch('../mpesa/stk_push.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.ResponseCode === '0') {
                msg.className = 'msg success';
                msg.innerText = '✅ Payment initiated! Enter your M-Pesa PIN on your phone. Your listing will activate automatically once confirmed.';
            } else {
                msg.className = 'msg error';
                msg.innerText = '❌ Error: ' + (data.errorMessage || data.ResultDesc || 'Something went wrong. Try again.');
                btn.disabled  = false;
                btn.innerText = 'Pay Now';
            }
        })
        .catch(() => {
            msg.className = 'msg error';
            msg.innerText = '❌ Network error. Please try again.';
            btn.disabled  = false;
            btn.innerText = 'Pay Now';
        });
}
</script>

</body>
</html>