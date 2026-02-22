<?php
// help.php
session_start();
$user_name  = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Help & Support | PROTECTORS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body{
    background:#f5f7fa;
    padding-top:120px;
    font-family:'Segoe UI', system-ui, sans-serif;
}

/* HERO */
.help-hero{
    background:linear-gradient(135deg,#0d0d0d,#1f2428);
    color:#fff;
    padding:50px 30px;
    border-radius:14px;
    margin-bottom:40px;
}
.help-hero span{color:#f59c1f}

/* CARDS */
.help-card{
    background:#fff;
    border-radius:14px;
    padding:25px;
    height:100%;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
}
.help-icon{
    font-size:32px;
    color:#4a6fa5;
    margin-bottom:15px;
}

/* SECTION */
.help-section{
    background:#fff;
    border-radius:14px;
    padding:30px;
    margin-bottom:30px;
    box-shadow:0 6px 18px rgba(0,0,0,.07);
}

/* FAQ */
.faq-item{
    border-bottom:1px solid #eee;
    padding:15px 0;
}
.faq-item:last-child{border:none}

/* CHAT */
.chat-box{
    background:#fff;
    border-radius:14px;
    padding:30px;
    box-shadow:0 8px 22px rgba(0,0,0,.1);
}

/* MOBILE */
@media(max-width:768px){
    body{padding-top:100px;}
}
</style>
</head>

<body>

<?php include "includes/header.php"; ?>

<div class="container">

<!-- HERO -->
<div class="help-hero text-center">
    <h1 class="fw-bold">Help & Support <span>Center</span></h1>
    <p class="mb-0 mt-2">Everything you need to know about our products & services</p>
</div>

<!-- QUICK SUPPORT -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="help-card text-center">
            <div class="help-icon"><i class="fas fa-box"></i></div>
            <h5 class="fw-bold">Products</h5>
            <p class="text-muted">Details about screen protectors & back cases.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="help-card text-center">
            <div class="help-icon"><i class="fas fa-tools"></i></div>
            <h5 class="fw-bold">Installation</h5>
            <p class="text-muted">Step-by-step guide for perfect fitting.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="help-card text-center">
            <div class="help-icon"><i class="fas fa-headset"></i></div>
            <h5 class="fw-bold">Live Support</h5>
            <p class="text-muted">Instant chat with our support team.</p>
        </div>
    </div>
</div>

<!-- PRODUCT DETAILS -->
<div class="help-section">
    <h3 class="fw-bold mb-3">📱 Our Products Explained</h3>

    <h5 class="fw-semibold">Screen Protectors</h5>
    <p class="text-muted">
        Screen protectors are used to protect your mobile display from scratches,
        cracks, dust, and accidental drops.
    </p>

    <ul class="text-muted">
        <li>Clear / Matte / Privacy / Mirror types available</li>
        <li>Exact fit for each phone model</li>
        <li>Smooth touch & HD clarity</li>
    </ul>

    <h5 class="fw-semibold mt-4">Back Cases</h5>
    <p class="text-muted">
        Back cases protect the phone body and camera from damage while giving
        a stylish look.
    </p>

    <ul class="text-muted">
        <li>Hard case & plastic case options</li>
        <li>Design-based collections</li>
        <li>Brand & model selection required</li>
    </ul>
</div>

<!-- 9H FIBRE vs NORMAL -->
<div class="help-section">
    <h3 class="fw-bold mb-3">🛡️ 9H Fibre Protection vs Normal Tempered Glass</h3>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Feature</th>
                    <th>9H Fibre Glass</th>
                    <th>Normal Tempered</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Strength</td>
                    <td>Very High (9H)</td>
                    <td>Medium</td>
                </tr>
                <tr>
                    <td>Flexibility</td>
                    <td>Flexible & shock absorb</td>
                    <td>Hard & brittle</td>
                </tr>
                <tr>
                    <td>Touch Sensitivity</td>
                    <td>Excellent</td>
                    <td>Good</td>
                </tr>
                <tr>
                    <td>Break Resistance</td>
                    <td>High</td>
                    <td>Low</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- INSTALLATION GUIDE -->
<div class="help-section">
    <h3 class="fw-bold mb-3">🛠️ Installation Guide (Protector)</h3>

    <ol class="text-muted">
        <li>Clean the screen using wet & dry wipes.</li>
        <li>Remove dust using dust-absorber sticker.</li>
        <li>Align the glass with speaker & edges.</li>
        <li>Place gently and press from center.</li>
        <li>Let air bubbles disappear naturally.</li>
    </ol>

    <p class="text-muted mb-0">
        ⚠️ Install in a dust-free environment for best results.
    </p>
</div>

<!-- USAGE TIPS -->
<div class="help-section">
    <h3 class="fw-bold mb-3">✅ Usage & Care Tips</h3>
    <ul class="text-muted">
        <li>Avoid sharp objects on screen.</li>
        <li>Clean with microfiber cloth only.</li>
        <li>Do not bend tempered glass.</li>
        <li>Use case + protector together for max safety.</li>
    </ul>
</div>

<!-- FAQ -->
<div class="help-section">
    <h3 class="fw-bold mb-3">❓ Frequently Asked Questions</h3>

    <div class="faq-item">
        <strong>How long does delivery take?</strong>
        <p class="text-muted mb-0">Delivery takes 3–5 working days.</p>
    </div>

    <div class="faq-item">
        <strong>Can I return a product?</strong>
        <p class="text-muted mb-0">Yes, within 7 days if unused.</p>
    </div>

    <div class="faq-item">
        <strong>Does protector support fingerprint?</strong>
        <p class="text-muted mb-0">Yes, compatible with in-display fingerprint.</p>
    </div>
</div>

<!-- LIVE CHAT -->
<div class="chat-box text-center mb-5">
    <h4 class="fw-bold">Still Need Help?</h4>
    <p class="text-muted mb-4">Chat with our support team instantly.</p>

    <a href="javascript:void(0)" class="btn btn-primary btn-lg"
       onclick="Tawk_API.maximize()">
        <i class="fas fa-comments me-2"></i>
        Chat with Support
    </a>
</div>

</div>

<?php include "includes/footer.php"; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- TAWK.TO -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/693c1662e0ccea197d8fdddf/1jc9besvk';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>

<?php if($user_name && $user_email): ?>
<script>
Tawk_API.onLoad = function(){
    Tawk_API.setAttributes({
        name : "<?= htmlspecialchars($user_name) ?>",
        email: "<?= htmlspecialchars($user_email) ?>"
    }, function(){});
};
</script>
<?php endif; ?>

</body>
</html>
