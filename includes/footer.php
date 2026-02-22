<!-- =========================
   FOOTER (DESKTOP ONLY)
========================= -->

<style>
/* =========================
   FOOTER BASE
========================= */
.site-footer {
    background-color: #000;
    color: #ddd;
    padding: 50px 5% 20px;
    margin-top: 60px;
    font-size: 0.95rem;
    line-height: 1.6;
}

/* =========================
   FOOTER GRID
========================= */
.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 40px;
}

.footer-column h3 {
    color: #fff;
    margin-bottom: 18px;
    font-size: 1.25rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #aaa;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #f6850d;
}

/* =========================
   COPYRIGHT
========================= */
.footer-copyright {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #2d3047;
    color: #aaa;
    font-size: 0.9rem;
}

/* =========================
   HIDE FOOTER ON MOBILE
========================= */
@media (max-width: 767px) {
    .site-footer {
        display: none !important;
    }
}
</style>

<footer class="site-footer">

    <div class="footer-content">

        <!-- BRAND -->
        <div class="footer-column">
            <h3>PROTECTORS</h3>
            <p>
                Premium screen protectors and mobile accessories for all major
                smartphone brands. Protecting your devices since 2015.
            </p>
        </div>

        <!-- QUICK LINKS -->
        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul class="footer-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="help.php">Help Center</a></li>
            </ul>
        </div>

        <!-- SUPPORT -->
        <div class="footer-column">
            <h3>Support</h3>
            <ul class="footer-links">
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="shipping.php">Shipping Policy</a></li>
                <li><a href="returns.php">Return Policy</a></li>
                <li><a href="warranty.php">Warranty</a></li>
                <li><a href="installation.php">Installation Guide</a></li>
            </ul>
        </div>

        <!-- CONTACT -->
        <div class="footer-column">
            <h3>Contact Info</h3>
            <ul class="footer-links">
                <li>📍 123 Tech Street, San Francisco, CA</li>
                <li>📞 9342573137</li>
                <li>✉️ support@protectors.com</li>
            </ul>
        </div>

    </div>

    <div class="footer-copyright">
        &copy; <?= date("Y") ?> PROTECTORS. All rights reserved.
    </div>

</footer>

<!-- =========================
   TAWK.TO LIVE CHAT
========================= -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/693c1662e0ccea197d8fdddf/1jc9besvk';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
})();
</script>
