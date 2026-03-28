</div>
</div>
<footer id="wf_footer" class="wf_footer wf_footer--one clearfix">
    <?php
    $img_uri = get_template_directory_uri() . '/assets/images/';
    ?>
    <div class="dh-footer__inner wf-container">

        <!-- Col 1: Logo + App badges -->
        <div class="dh-footer__brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="dh-footer__logo">
                <img src="<?php echo esc_url($img_uri . 'HUTCHMainLogo.svg'); ?>" alt="HUTCH">
            </a>
            <div class="dh-footer__badges">
                <a href="#" class="dh-footer__badge" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url($img_uri . 'GooglePlay.svg'); ?>" alt="Get it on Google Play">
                </a>
                <a href="#" class="dh-footer__badge" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url($img_uri . 'AppStore.svg'); ?>" alt="Download on the App Store">
                </a>
            </div>
        </div>

        <!-- Col 2: Company links -->
        <div class="dh-footer__col">
            <h4 class="dh-footer__heading">Company</h4>
            <ul class="dh-footer__links">
                <li><a href="#">About us</a></li>
                <li><a href="#">Delivery</a></li>
                <li><a href="#">Legal Notice</a></li>
                <li><a href="#">Terms &amp; conditions</a></li>
                <li><a href="#">Secure payment</a></li>
                <li><a href="#">Contact us</a></li>
            </ul>
        </div>

        <!-- Col 3: Contact + social -->
        <div class="dh-footer__col">
            <h4 class="dh-footer__heading">Contact</h4>
            <ul class="dh-footer__contact">
                <li>
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>234 Galle Rd, Colombo, Sri Lanka</span>
                </li>
                <li>
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    <a href="tel:0788777111">0788 777 111</a>
                </li>
                <li>
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <a href="mailto:cs@hutchison.lk">cs@hutchison.lk</a>
                </li>
            </ul>
            <ul class="dh-footer__social">
                <li>
                    <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <img src="<?php echo esc_url($img_uri . 'FaceBook.svg'); ?>" alt="Facebook">
                    </a>
                </li>
                <li>
                    <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                        <img src="<?php echo esc_url($img_uri . 'Twitter.svg'); ?>" alt="Twitter">
                    </a>
                </li>
                <li>
                    <a href="#" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                        <img src="<?php echo esc_url($img_uri . 'LinkedIn.svg'); ?>" alt="LinkedIn">
                    </a>
                </li>
                <li>
                    <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <img src="<?php echo esc_url($img_uri . 'Instagram.svg'); ?>" alt="Instagram">
                    </a>
                </li>
            </ul>
        </div>

    </div>
</footer>
<?php
do_action('shopire_top_scroller');
do_action('shopire_footer_mobile_menu');
?>
<?php wp_footer(); ?>
</body>

</html>