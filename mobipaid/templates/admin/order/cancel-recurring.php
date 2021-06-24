<?php
/**
 * Skrill Payments Update Order
 *
 * The file is for displaying button update order at order detail (admin)
 * Copyright (c) Skrill
 *
 * @package Skrill/Templates
 * @located at  /template/admin/order
 */

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly.
}
?>

<style>
    .skrill-warning {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        border: 3px solid red;
        background-color: #d7cad2;
        color: #444 !important;
    }
</style>

<?php if ($is_show_warning_message) {?>
    <p class="form-field form-field-wide skrill-warning" style="padding: 10px !important;" id="cancel-recurring-message">
    <?php echo esc_html($warning_message); ?>
    </p>
<?php }?>

<?php if ($is_show_update_order) {?>
    <p class="form-field form-field-wide" style="text-align:right" id="cancel-recurring-button">
        <label for="order_status">&nbsp;</label>
        <a href="<?php echo esc_html($update_order_url); ?>" class="button save_order button-primary" >
    <?php echo esc_html(__('Stop Mobipaid subscription', 'wc-skrill')); ?>
        </a>
    </p>
<?php }?>

<?php if ($is_frontend) {?>
<script type="text/javascript">

    <?php if ($redirect_url) {?>
    window.location = "<?=$redirect_url?>";
    <?php }?>
    (function() {
        var orderElement = document.getElementsByClassName("shop_table order_details");
        if(document.getElementById("cancel-recurring-message")){
            var message = document.getElementById("cancel-recurring-message");
            orderElement[0].parentNode.insertBefore(message, orderElement[0].nextSibling);
        }

        if(document.getElementById("cancel-recurring-button")){
            var message = document.getElementById("cancel-recurring-button");
            orderElement[0].parentNode.insertBefore(message, orderElement[0].nextSibling);
        }

})();
</script>
<?php }?>