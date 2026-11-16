<?php
/**
 * Payamakio Authentication Popup Modal Template
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( is_user_logged_in() ) {
    return;
}
?>

<div id="payamakio-auth-modal" class="payamakio-auth-modal" style="display: none !important;" role="dialog" aria-modal="true" aria-labelledby="payamakio-auth-modal-title">
    <div class="payamakio-auth-modal-backdrop"></div>
    <div class="payamakio-auth-modal-dialog">
        <button type="button" class="payamakio-auth-modal-close" aria-label="بستن پنجره">
            <?php echo Payamakio_Helper::icon( 'close', 16 ); ?>
        </button>
        <div class="payamakio-auth-modal-content">
            <?php Payamakio_Module_Auth::render_auth_card( array( 'in_modal' => true ) ); ?>
        </div>
    </div>
</div>
