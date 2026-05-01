<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn        = getDBConnection();
$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($business_id <= 0) { header('Location: categories.php'); exit; }

$stmt = $conn->prepare(
    "SELECT b.*, c.name AS category_name
     FROM businesses b
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.id = ? AND b.status = 'approved'"
);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$business) { header('Location: categories.php'); exit; }

// Operation hours
$operation_hours = [];
if (!empty($business['operation_hours']))
    $operation_hours = json_decode($business['operation_hours'], true) ?? [];

$days_of_week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$today        = date('l');
$now_mins     = (int)date('H') * 60 + (int)date('i');

function minsFromTime(string $t): int {
    [$h, $m] = explode(':', $t);
    return (int)$h * 60 + (int)$m;
}

$is_open_now = false;
if (!empty($operation_hours[$today]) && empty($operation_hours[$today]['closed'])) {
    $is_open_now = $now_mins >= minsFromTime($operation_hours[$today]['open']  ?? '00:00')
                && $now_mins <  minsFromTime($operation_hours[$today]['close'] ?? '00:00');
}

// Reviews
$rstmt = $conn->prepare(
    "SELECT r.*, COALESCE(u.full_name, r.reviewer_name, 'Anonymous') AS reviewer_name
     FROM reviews r LEFT JOIN users u ON r.user_id = u.id
     WHERE r.business_id = ? AND r.status = 'approved'
     ORDER BY r.created_at DESC"
);
$rstmt->bind_param("i", $business_id);
$rstmt->execute();
$reviews = []; $total_rating = 0;
$rres = $rstmt->get_result();
while ($row = $rres->fetch_assoc()) { $reviews[] = $row; $total_rating += $row['rating']; }
$rstmt->close();
$review_count = count($reviews);
$avg_rating   = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

// Already reviewed?
$user_already_reviewed = false;
if (!empty($_SESSION['user_id'])) {
    $uchk = $conn->prepare("SELECT id FROM reviews WHERE business_id=? AND user_id=? AND status IN ('pending','approved') LIMIT 1");
    $uid = (int)$_SESSION['user_id'];
    $uchk->bind_param("ii", $business_id, $uid);
    $uchk->execute(); $uchk->store_result();
    $user_already_reviewed = $uchk->num_rows > 0;
    $uchk->close();
}

$conn->close();

$maps_link = 'https://maps.google.com/?q=' . urlencode(($business['address'] ?? '') . ', Kenya');
$seed = $business_id % 5;
$off  = [0, 4, -3, 6, -2][$seed];

// Build compact hours summary e.g. "Mon–Sat 8:00 AM – 5:00 PM · Sun 10:00 AM – 7:30 PM"
function compactHours(array $oh): string {
    if (empty($oh)) return '';
    $abbr = ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed',
             'Thursday'=>'Thu','Friday'=>'Fri','Saturday'=>'Sat','Sunday'=>'Sun'];
    $fmt  = fn($t) => date('g:i A', strtotime($t));
    // Group consecutive days with same hours
    $groups = [];
    $days   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $prev   = null; $grp_start = null; $grp_days = [];
    foreach ($days as $day) {
        $h = $oh[$day] ?? null;
        $key = $h ? (!empty($h['closed']) ? 'CLOSED' : ($h['open'].'-'.$h['close'])) : 'CLOSED';
        if ($key !== $prev) {
            if ($prev !== null) {
                $groups[] = ['days'=>$grp_days,'key'=>$prev,'h'=>$oh[$grp_start] ?? null];
            }
            $grp_days  = [$day];
            $grp_start = $day;
            $prev      = $key;
        } else {
            $grp_days[] = $day;
        }
    }
    if ($prev !== null) $groups[] = ['days'=>$grp_days,'key'=>$prev,'h'=>$oh[$grp_start] ?? null];

    $parts = [];
    foreach ($groups as $g) {
        $d = $g['days'];
        $label = count($d) === 1
            ? $abbr[$d[0]]
            : $abbr[$d[0]] . '&ndash;' . $abbr[end($d)];
        if ($g['key'] === 'CLOSED') {
            $parts[] = '<span class="hsum-day">'.$label.'</span> <span class="hsum-closed">Closed</span>';
        } else {
            $h = $g['h'];
            $parts[] = '<span class="hsum-day">'.$label.'</span> '.$fmt($h['open']).' &ndash; '.$fmt($h['close']);
        }
    }
    return implode(' &nbsp;&middot;&nbsp; ', $parts);
}

function renderStars(float $r, string $sz = '20px'): string {
    $o = '<span class="sd" style="font-size:'.$sz.';line-height:1">';
    for ($i = 1; $i <= 5; $i++) {
        if ($r >= $i)        $o .= '<span class="s f">&#9733;</span>';
        elseif ($r >= $i-.5) $o .= '<span class="s h">&#9733;</span>';
        else                  $o .= '<span class="s e">&#9733;</span>';
    }
    return $o . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($business['name']); ?> &ndash; Zaddy</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#111113;--sur:#1a1a1f;--sur2:#0f0f14;
    --bor:#2a2a32;--bor2:#22222c;
    --acc:#f5c518;--acd:#c9a010;
    --txt:#f0f0f2;--mut:#7a7a8a;
    --grn:#22c55e;--red:#ef4444;
    --rad:14px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt);min-height:100vh}

/* NAV */
.hdr{background:#0d0d10;border-bottom:1px solid var(--bor);padding:16px 0;position:sticky;top:0;z-index:100}
.hdr-in{max-width:1100px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between}
.hdr h1{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--txt);cursor:pointer;letter-spacing:-.4px}
.nav{display:flex;gap:22px;align-items:center}
.nav a{color:var(--mut);text-decoration:none;font-size:.875rem;font-weight:500;transition:color .2s}
.nav a:hover{color:var(--txt)}
.reg{background:var(--acc);color:#111;padding:8px 20px;border-radius:8px;font-family:'Syne',sans-serif;font-weight:700;font-size:.82rem;text-decoration:none;transition:background .2s}
.reg:hover{background:var(--acd);color:#111}

/* LAYOUT */
.wrap{max-width:1100px;margin:0 auto;padding:32px 24px 64px}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--mut);text-decoration:none;font-size:.85rem;font-weight:500;margin-bottom:24px;transition:color .2s}
.back:hover{color:var(--acc)}

/* HERO */
.hero{background:var(--sur);border:1px solid var(--bor);border-radius:var(--rad);overflow:hidden;margin-bottom:24px}
.img-wrap{position:relative;height:320px;overflow:hidden}
.img-wrap img{width:100%;height:100%;object-fit:cover}
.ov{position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(10,10,14,.9))}
.no-img{height:220px;background:linear-gradient(135deg,#1e1e28,#2a2a38);display:flex;align-items:center;justify-content:center;font-size:72px}
.hero-body{padding:28px 32px 32px}

.cat-pill{display:inline-flex;align-items:center;background:rgba(245,197,24,.1);border:1px solid rgba(245,197,24,.25);color:var(--acc);font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:99px;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}
.biz-name{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--txt);margin-bottom:10px;letter-spacing:-.5px}

/* Stars */
.sd .s{color:#2a2a32}.sd .f{color:var(--acc)}
.sd .h{position:relative;color:#2a2a32}
.sd .h::after{content:'\2605';position:absolute;left:0;top:0;color:var(--acc);width:50%;overflow:hidden;display:block}

/* Rating row */
.agg{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.avg{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--acc);line-height:1}
.rmeta{display:flex;flex-direction:column;gap:3px}
.rcnt{font-size:.78rem;color:var(--mut)}

/* Hours summary strip */
.hsum{display:flex;align-items:flex-start;gap:10px;background:var(--sur2);border:1px solid var(--bor2);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.82rem;color:#ccc;flex-wrap:wrap;line-height:1.8}
.hsum-icon{font-size:16px;flex-shrink:0;margin-top:1px}
.hsum-day{color:var(--acc);font-weight:700}
.hsum-closed{color:var(--red);font-weight:600}
.hsum-badge{flex-shrink:0;display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:99px;font-size:.7rem;font-weight:700;text-transform:uppercase;margin-left:4px}
.hsum-badge.op{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#4ade80}
.hsum-badge.cl{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171}
.hsum-badge .dot{width:5px;height:5px;border-radius:50%;display:inline-block}
.hsum-badge.op .dot{background:#4ade80}
.hsum-badge.cl .dot{background:#f87171}

.biz-desc{color:#aaa;font-size:.95rem;line-height:1.8;margin-bottom:28px}

/* Detail grid */
.dgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:28px}
.dcrd{background:var(--sur2);border:1px solid var(--bor2);border-radius:10px;padding:16px;display:flex;align-items:flex-start;gap:12px}
.dico{font-size:20px;flex-shrink:0;margin-top:1px}
.dlbl{font-size:.68rem;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px}
.dval{font-size:.88rem;color:var(--txt);font-weight:500}
.dval a{color:var(--acc);text-decoration:none}
.dval a:hover{text-decoration:underline}

/* Action buttons */
.abts{display:flex;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:8px;font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:opacity .2s,transform .15s}
.btn:hover{opacity:.88;transform:translateY(-1px)}
.bp{background:var(--acc);color:#111}
.bw{background:#25D366;color:#fff}
.bo{background:transparent;color:var(--acc);border:1.5px solid var(--acc)}

/* Two col */
.two{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px}
@media(max-width:720px){
    .two{grid-template-columns:1fr}
    .biz-name{font-size:1.5rem}
    .hero-body{padding:20px}
}

/* Section card */
.sc{background:var(--sur);border:1px solid var(--bor);border-radius:var(--rad);overflow:hidden}
.sh{padding:18px 22px 14px;border-bottom:1px solid var(--bor);display:flex;align-items:center;justify-content:space-between}
.st{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;color:var(--txt);display:flex;align-items:center;gap:8px}
.sb{padding:20px 22px}

/* Open badge in section header */
.ob{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.ob.op{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#4ade80}
.ob.cl{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171}
.ob .dot{width:6px;height:6px;border-radius:50%}
.ob.op .dot{background:#4ade80}
.ob.cl .dot{background:#f87171}

/* Hours table */
.ht{width:100%;border-collapse:collapse}
.ht tr{border-bottom:1px solid var(--bor2)}
.ht tr:last-child{border-bottom:none}
.ht tr.tr{background:rgba(245,197,24,.05)}
.ht td{padding:9px 4px;font-size:.85rem}
.ht td.dc{color:var(--mut);font-weight:500;width:105px}
.ht tr.tr td.dc{color:var(--acc);font-weight:700}
.ht td.tc{color:var(--txt);text-align:right}
.ht td.cc{color:var(--red);text-align:right;font-size:.78rem;font-weight:600;text-transform:uppercase}
.tp{display:inline-block;background:rgba(245,197,24,.15);color:var(--acc);font-size:.65rem;font-weight:700;padding:1px 7px;border-radius:99px;text-transform:uppercase;margin-left:6px;vertical-align:middle}
.he{color:var(--mut);font-size:.85rem;font-style:italic}

/* SVG Map */
.fmw{overflow:hidden}
.fms{width:100%;display:block}
.map-pin-drop{animation:pinDrop .55s cubic-bezier(.36,2.1,.6,1) both;transform-origin:240px 124px}
@keyframes pinDrop{from{transform:scaleY(0);opacity:0}to{transform:scaleY(1);opacity:1}}
.map-pulse{animation:mapPulse 2.2s ease-out infinite;transform-origin:240px 103px}
@keyframes mapPulse{0%{r:8;opacity:.7}100%{r:22;opacity:0}}
.mas{display:flex;align-items:center;gap:8px;padding:10px 14px;border-top:1px solid var(--bor2);font-size:.78rem;color:var(--mut);background:var(--sur2)}
.mas a{margin-left:auto;color:var(--acc);font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap}
.mas a:hover{text-decoration:underline}

/* Reviews section */
.rs{background:var(--sur);border:1px solid var(--bor);border-radius:var(--rad);overflow:hidden;margin-bottom:24px}
.rl{display:flex;flex-direction:column}
.ri{padding:20px 22px;border-bottom:1px solid var(--bor2)}
.ri:last-child{border-bottom:none}
.rh{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#f5c518,#c9a010);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.78rem;color:#111;flex-shrink:0}
.rni{flex:1}
.rn{font-size:.88rem;font-weight:600;color:var(--txt)}
.rd{font-size:.72rem;color:var(--mut);margin-top:2px}
.rt{font-size:.85rem;color:#bbb;line-height:1.65}
.nr{padding:32px 22px;text-align:center;color:var(--mut);font-size:.88rem}

/* Review form */
.rfc{background:var(--sur);border:1px solid var(--bor);border-radius:var(--rad);overflow:hidden;margin-bottom:24px}
.sp{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:8px;margin-bottom:16px}
.sp input[type=radio]{display:none}
.sp label{font-size:32px;color:#2a2a32;cursor:pointer;transition:color .15s;user-select:none}
.sp label:hover,.sp label:hover~label,.sp input[type=radio]:checked~label{color:var(--acc)}
.fg{margin-bottom:16px}
.fl{display:block;font-size:.78rem;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
textarea.fc{width:100%;background:var(--sur2);border:1px solid var(--bor);border-radius:8px;padding:12px 14px;color:var(--txt);font-family:'DM Sans',sans-serif;font-size:.9rem;resize:vertical;min-height:100px;transition:border-color .2s}
textarea.fc:focus{outline:none;border-color:var(--acc)}
textarea.fc::placeholder{color:var(--mut)}
.bsr{background:var(--acc);color:#111;border:none;padding:11px 28px;border-radius:8px;font-family:'Syne',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;transition:background .2s}
.bsr:hover{background:var(--acd)}
.bsr:disabled{opacity:.5;cursor:not-allowed}
.al{padding:12px 16px;border-radius:8px;font-size:.85rem;margin-bottom:16px}
.al-s{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#4ade80}
.al-e{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171}
.al-i{background:rgba(245,197,24,.08);border:1px solid rgba(245,197,24,.2);color:#fbbf24}
.hidden{display:none}

footer{background:#0d0d10;border-top:1px solid var(--bor);text-align:center;padding:24px;font-size:.78rem;color:var(--mut);margin-top:16px}
</style>
</head>
<body>

<!-- NAV -->
<div class="hdr">
  <div class="hdr-in">
    <h1 onclick="location.href='index.php'">Zaddy Business Directory</h1>
    <nav class="nav">
      <a href="index.php">Home</a>
      <a href="categories.php">Categories</a>
      <a href="search.php">Search</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="auth/dashboard.php">Dashboard</a>
        <a href="auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="auth/login.php">Login</a>
        <a href="auth/register.php" class="reg">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</div>

<main class="wrap">
  <a href="javascript:history.back()" class="back">&#8592; Back</a>

  <!-- HERO CARD -->
  <div class="hero">
    <?php if (!empty($business['image'])): ?>
      <div class="img-wrap">
        <img src="uploads/<?php echo htmlspecialchars($business['image']); ?>"
             alt="<?php echo htmlspecialchars($business['name']); ?>">
        <div class="ov"></div>
      </div>
    <?php else: ?>
      <div class="no-img">&#127962;</div>
    <?php endif; ?>

    <div class="hero-body">

      <?php if (!empty($business['category_name'])): ?>
        <div class="cat-pill"><?php echo htmlspecialchars($business['category_name']); ?></div>
      <?php endif; ?>

      <h1 class="biz-name"><?php echo htmlspecialchars($business['name']); ?></h1>

      <!-- Star rating -->
      <div class="agg">
        <?php if ($review_count > 0): ?>
          <div class="avg"><?php echo number_format($avg_rating,1); ?></div>
          <div class="rmeta">
            <?php echo renderStars($avg_rating,'22px'); ?>
            <span class="rcnt"><?php echo $review_count; ?> review<?php echo $review_count!==1?'s':''; ?></span>
          </div>
        <?php else: ?>
          <div class="rmeta">
            <?php echo renderStars(0,'20px'); ?>
            <span class="rcnt">No reviews yet &mdash; be the first!</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Compact hours summary -->
      <?php $hsum = compactHours($operation_hours); ?>
      <?php if ($hsum): ?>
      <div class="hsum">
        <span class="hsum-icon">&#128336;</span>
        <span><?php echo $hsum; ?></span>
        <span class="hsum-badge <?php echo $is_open_now?'op':'cl'; ?>">
          <span class="dot"></span>
          <?php echo $is_open_now?'Open Now':'Closed Now'; ?>
        </span>
      </div>
      <?php endif; ?>

      <?php if (!empty($business['description'])): ?>
        <p class="biz-desc"><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
      <?php endif; ?>

      <!-- Detail cards -->
      <div class="dgrid">
        <?php if (!empty($business['address'])): ?>
        <div class="dcrd"><div class="dico">&#128205;</div><div>
          <div class="dlbl">Location</div>
          <div class="dval"><?php echo htmlspecialchars($business['address']); ?></div>
        </div></div>
        <?php endif; ?>

        <?php if (!empty($business['phone'])): ?>
        <div class="dcrd"><div class="dico">&#128222;</div><div>
          <div class="dlbl">Phone</div>
          <div class="dval"><a href="tel:<?php echo htmlspecialchars($business['phone']); ?>"><?php echo htmlspecialchars($business['phone']); ?></a></div>
        </div></div>
        <?php endif; ?>

        <?php if (!empty($business['email'])): ?>
        <div class="dcrd"><div class="dico">&#9993;</div><div>
          <div class="dlbl">Email</div>
          <div class="dval"><a href="mailto:<?php echo htmlspecialchars($business['email']); ?>"><?php echo htmlspecialchars($business['email']); ?></a></div>
        </div></div>
        <?php endif; ?>

        <?php if (!empty($business['website'])): ?>
        <div class="dcrd"><div class="dico">&#127760;</div><div>
          <div class="dlbl">Website</div>
          <div class="dval"><a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" rel="noopener">Visit Website &#8599;</a></div>
        </div></div>
        <?php endif; ?>
      </div>

      <!-- Action buttons -->
      <div class="abts">
        <?php if (!empty($business['phone'])): ?>
          <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="btn bp">&#128222; Call Now</a>
          <a href="https://wa.me/<?php echo preg_replace('/\D/','',$business['phone']); ?>" target="_blank" class="btn bw">&#128172; WhatsApp</a>
        <?php endif; ?>
        <?php if (!empty($business['email'])): ?>
          <a href="mailto:<?php echo htmlspecialchars($business['email']); ?>" class="btn bo">&#9993; Send Email</a>
        <?php endif; ?>
      </div>

    </div>
  </div><!-- /hero -->

  <!-- HOURS + MAP -->
  <div class="two">

    <!-- Full hours table -->
    <div class="sc">
      <div class="sh">
        <div class="st">&#128336; Operation Hours</div>
        <?php if (!empty($operation_hours)): ?>
          <span class="ob <?php echo $is_open_now?'op':'cl'; ?>">
            <span class="dot"></span>
            <?php echo $is_open_now?'Open Now':'Closed Now'; ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="sb">
        <?php if (!empty($operation_hours)): ?>
          <table class="ht">
            <?php foreach ($days_of_week as $day):
              $h  = $operation_hours[$day] ?? null;
              $it = ($day === $today);
              $ic = !empty($h['closed']);
              $fmt = fn($t) => date('g:i A', strtotime($t));
            ?>
            <tr class="<?php echo $it?'tr':''; ?>">
              <td class="dc"><?php echo $day; ?>
                <?php if ($it): ?><span class="tp">Today</span><?php endif; ?>
              </td>
              <?php if ($ic): ?>
                <td class="cc">Closed</td>
              <?php elseif ($h): ?>
                <td class="tc"><?php echo $fmt($h['open']).' &ndash; '.$fmt($h['close']); ?></td>
              <?php else: ?>
                <td class="cc">&mdash;</td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <p class="he">No operation hours provided.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- SVG Map -->
    <div class="sc">
      <div class="sh">
        <div class="st">&#128506; Location</div>
      </div>
      <?php if (!empty($business['address'])): ?>
      <div class="fmw">
        <svg class="fms" viewBox="0 0 500 200" xmlns="http://www.w3.org/2000/svg">
          <rect width="500" height="200" fill="#1a1a22"/>
          <rect x="0" y="0" width="<?php echo 70+$off;?>" height="55" fill="#222230" rx="2"/>
          <rect x="<?php echo 78+$off;?>" y="0" width="<?php echo 110-$off;?>" height="55" fill="#222230" rx="2"/>
          <rect x="200" y="0" width="<?php echo 80+$off;?>" height="55" fill="#222230" rx="2"/>
          <rect x="<?php echo 292+$off;?>" y="0" width="<?php echo 68-$off;?>" height="55" fill="#222230" rx="2"/>
          <rect x="372" y="0" width="128" height="55" fill="#222230" rx="2"/>
          <rect x="0" y="73" width="<?php echo 60+$off;?>" height="50" fill="#222230" rx="2"/>
          <rect x="<?php echo 78+$off;?>" y="73" width="<?php echo 100-$off;?>" height="50" fill="#222230" rx="2"/>
          <rect x="200" y="73" width="80" height="50" fill="#2a2820" rx="2"/>
          <rect x="200" y="73" width="80" height="50" fill="none" stroke="#f5c518" stroke-width="1.5" rx="2" opacity="0.6"/>
          <rect x="<?php echo 292+$off;?>" y="73" width="<?php echo 68+$off;?>" height="50" fill="#222230" rx="2"/>
          <rect x="372" y="73" width="128" height="50" fill="#222230" rx="2"/>
          <rect x="0" y="141" width="<?php echo 90-$off;?>" height="59" fill="#222230" rx="2"/>
          <rect x="<?php echo 98-$off;?>" y="141" width="<?php echo 90+$off;?>" height="59" fill="#222230" rx="2"/>
          <rect x="200" y="141" width="80" height="59" fill="#222230" rx="2"/>
          <rect x="<?php echo 292+$off;?>" y="141" width="<?php echo 68+$off;?>" height="59" fill="#222230" rx="2"/>
          <rect x="372" y="141" width="128" height="59" fill="#222230" rx="2"/>
          <rect x="0" y="63" width="500" height="10" fill="#16161e"/>
          <rect x="0" y="131" width="500" height="10" fill="#16161e"/>
          <rect x="<?php echo 68+$off;?>" y="0" width="10" height="200" fill="#16161e"/>
          <rect x="190" y="0" width="10" height="200" fill="#16161e"/>
          <rect x="362" y="0" width="10" height="200" fill="#16161e"/>
          <line x1="0" y1="68" x2="500" y2="68" stroke="#2a2a3a" stroke-width="0.8" stroke-dasharray="10 7"/>
          <line x1="0" y1="136" x2="500" y2="136" stroke="#2a2a3a" stroke-width="0.8" stroke-dasharray="10 7"/>
          <line x1="<?php echo 73+$off;?>" y1="0" x2="<?php echo 73+$off;?>" y2="200" stroke="#2a2a3a" stroke-width="0.8" stroke-dasharray="10 7"/>
          <line x1="195" y1="0" x2="195" y2="200" stroke="#2a2a3a" stroke-width="0.8" stroke-dasharray="10 7"/>
          <line x1="367" y1="0" x2="367" y2="200" stroke="#2a2a3a" stroke-width="0.8" stroke-dasharray="10 7"/>
          <text font-family="sans-serif" font-size="7" fill="#3a3a52" x="130" y="71" text-anchor="middle">MAIN ROAD</text>
          <text font-family="sans-serif" font-size="7" fill="#3a3a52" x="320" y="71" text-anchor="middle">MAIN ROAD</text>
          <text font-family="sans-serif" font-size="7" fill="#3a3a52" x="130" y="139" text-anchor="middle">HIGH STREET</text>
          <text font-family="sans-serif" font-size="7" fill="#3a3a52" x="320" y="139" text-anchor="middle">HIGH STREET</text>
          <circle class="map-pulse" cx="240" cy="103" r="10" fill="none" stroke="#f5c518" stroke-width="1.5"/>
          <ellipse cx="240" cy="124" rx="6" ry="2.5" fill="#000" opacity="0.45"/>
          <g class="map-pin-drop">
            <path d="M240 124 C240 124 228 110 228 102 A12 12 0 1 1 252 102 C252 110 240 124 240 124Z" fill="#f5c518" stroke="#c9a010" stroke-width="1"/>
            <circle cx="240" cy="102" r="4" fill="#111113"/>
          </g>
        </svg>
        <div class="mas">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7a7a8a" stroke-width="2" style="flex-shrink:0">
            <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          <?php echo htmlspecialchars($business['address']); ?>
          <a href="<?php echo $maps_link; ?>" target="_blank" rel="noopener">Open in Google Maps &#8599;</a>
        </div>
      </div>
      <?php else: ?>
        <div class="sb"><p class="he">No address provided.</p></div>
      <?php endif; ?>
    </div>

  </div><!-- /two -->

  <!-- REVIEWS LIST -->
  <div class="rs">
    <div class="sh">
      <div class="st">&#11088; Customer Reviews</div>
      <?php if ($review_count > 0): ?>
        <span style="font-size:.78rem;color:var(--mut)"><?php echo $review_count; ?> review<?php echo $review_count!==1?'s':''; ?></span>
      <?php endif; ?>
    </div>
    <?php if (empty($reviews)): ?>
      <div class="nr">No reviews yet.
        <?php if (isset($_SESSION['user_id'])): ?>
          Be the first to review below.
        <?php else: ?>
          <a href="auth/login.php" style="color:var(--acc)">Login</a> to leave a review.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="rl">
        <?php foreach ($reviews as $rev):
          $w   = explode(' ', trim($rev['reviewer_name'] ?? 'U'));
          $ini = strtoupper($w[0][0]);
          if (count($w) > 1) $ini .= strtoupper(end($w)[0]);
        ?>
        <div class="ri">
          <div class="rh">
            <div class="av"><?php echo htmlspecialchars($ini); ?></div>
            <div class="rni">
              <div class="rn"><?php echo htmlspecialchars($rev['reviewer_name'] ?? 'Anonymous'); ?></div>
              <div class="rd"><?php echo date('F Y', strtotime($rev['created_at'])); ?></div>
            </div>
            <?php echo renderStars((float)$rev['rating'],'16px'); ?>
          </div>
          <p class="rt"><?php echo nl2br(htmlspecialchars($rev['review_text'])); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- REVIEW FORM -->
  <?php if ($user_already_reviewed): ?>
    <div class="rfc">
      <div class="sh"><div class="st">&#9997; Leave a Review</div></div>
      <div class="sb">
        <div class="al al-i">You have already reviewed this business. Thank you!</div>
      </div>
    </div>

  <?php else: ?>
    <div class="rfc">
      <div class="sh"><div class="st">&#9997; Leave a Review</div></div>
      <div class="sb">
        <div id="ral" class="al hidden"></div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="fg">
          <label class="fl" for="rname">Your Name <span style="color:var(--mut);font-weight:400">(optional)</span></label>
          <input class="fc" type="text" id="rname" placeholder="Anonymous" maxlength="100" style="height:44px">
        </div>
        <?php endif; ?>
        <div class="fg">
          <label class="fl">Your Rating</label>
          <div class="sp" id="sp">
            <input type="radio" name="rating" id="s5" value="5"><label for="s5">&#9733;</label>
            <input type="radio" name="rating" id="s4" value="4"><label for="s4">&#9733;</label>
            <input type="radio" name="rating" id="s3" value="3"><label for="s3">&#9733;</label>
            <input type="radio" name="rating" id="s2" value="2"><label for="s2">&#9733;</label>
            <input type="radio" name="rating" id="s1" value="1"><label for="s1">&#9733;</label>
          </div>
        </div>
        <div class="fg">
          <label class="fl" for="rtxt">Your Review</label>
          <textarea class="fc" id="rtxt" placeholder="Share your experience with this business…" maxlength="1000"></textarea>
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <?php endif; ?>
        <button class="bsr" id="subBtn" onclick="submitReview()">Submit Review</button>
      </div>
    </div>
  <?php endif; ?>

</main>

<footer>&copy; <?php echo date('Y'); ?> Zaddy Business Directory. All rights reserved.</footer>

<script>
async function submitReview() {
    const btn    = document.getElementById('subBtn');
    const text   = document.getElementById('rtxt').value.trim();
    const chk    = document.querySelector('#sp input[type="radio"]:checked');
    const rating = chk ? parseInt(chk.value) : 0;
    const nameEl = document.getElementById('rname');
    const name   = nameEl ? nameEl.value.trim() : '';

    if (rating < 1 || rating > 5) { showAlert('Please select a star rating.', 'e'); return; }
    if (text.length < 10)          { showAlert('Review must be at least 10 characters.', 'e'); return; }

    btn.disabled = true;
    btn.textContent = 'Submitting\u2026';

    const fd = new FormData();
    fd.append('business_id', '<?php echo $business_id; ?>');
    fd.append('rating', rating);
    fd.append('review_text', text);
    fd.append('reviewer_name', name);

    try {
        const res  = await fetch('submit_review.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert(data.message, 's');
            document.getElementById('rtxt').value = '';
            if (nameEl) nameEl.value = '';
            document.querySelectorAll('#sp input').forEach(i => i.checked = false);
            btn.style.display = 'none';
        } else {
            showAlert(data.message, 'e');
            btn.disabled = false;
            btn.textContent = 'Submit Review';
        }
    } catch(err) {
        showAlert('Network error. Please try again.', 'e');
        btn.disabled = false;
        btn.textContent = 'Submit Review';
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('ral');
    el.className = 'al al-' + type;
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
</body>
</html>