<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap <?php echo self::SLUG ?>">
    <div class="content">
        <div class="inner">
            <h2><?php echo self::NAME ?> Options <span class="version" title="<?php echo self::NAME ?> version <?php echo self::VERSION ?>"><?php echo self::VERSION ?></span></h2>

            <form action="options.php" method="post">
                <?php settings_fields(self::SLUG); ?>
                <?php do_settings_sections(self::SLUG); ?>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>"></p>
            </form>
        </div>
    </div>

    <div class="sidebar">
        <div class="inner">
            <div class="section coffee">
                <p>If you find this plugin useful, consider showing your support by sending some coffee my way. Thanks!</p>
                <div class="form">
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                        <input type="hidden" name="cmd" value="_donations">
                        <input type="hidden" name="business" value="98V8HW5QKDK8W">
                        <input type="hidden" name="lc" value="US">
                        <input type="hidden" name="no_note" value="0">
                        <input type="hidden" name="cn" value="Want to say hello?">
                        <input type="hidden" name="no_shipping" value="1">
                        <input type="hidden" name="rm" value="1">
                        <input type="hidden" name="return" value="<?php echo get_admin_url('', $this->getOptionsPageUrl()) ?>">
                        <input type="hidden" name="currency_code" value="USD">
                        <input type="hidden" name="bn" value="Splitleaf_Donate_<?php echo self::NAME ?>_US">
                        <input type="hidden" name="item_name" value="Support <?php echo self::NAME ?>! (WordPress Plugin)">
                        <select name="amount">
                            <option value="5.00">One cup ($5.00)</option>
                            <option value="10.00">Two cups ($10.00)</option>
                            <option value="15.00">All the cups! ($15.00+)</option>
                        </select>
                        <button type="submit" name="submit" title="PayPal - The safer, easier way to pay online!" class="button button-donate">Donate</button>
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" class="pixel" />
                    </form>
                </div>
            </div>
            
            <div class="section share">
                <ul>
                    <li><a href="http://wordpress.org/support/view/plugin-reviews/<?php echo urlencode(self::NAME_LOWER) ?>" class="button button-rate" target="_blank">Rate It</a></li>
                    <li><a href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(self::DOWNLOAD_URL) ?>" class="button button-share" target="_blank">Share It</a></li>
                    <li><a href="http://twitter.com/share?url=<?php echo urlencode(self::DOWNLOAD_URL) ?>&text=<?php echo urlencode(self::NAME.' is a great plugin to track your space usage right from the WordPress admin. Try it! #'.self::NAME_LOWER) ?>" class="button button-tweet" target="_blank">Tweet It</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>