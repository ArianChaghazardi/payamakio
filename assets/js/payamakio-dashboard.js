/**
 * Payamakio WordPress Dashboard Widget JavaScript
 * Asynchronous live metrics and wallet balance refresh
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

(function ($) {
    'use strict';

    /**
     * Refresh Dashboard Widget content via AJAX
     */
    window.payamakioDashboardRefresh = function (btn) {
        var $btn = $(btn);
        if ($btn.hasClass('is-spinning')) {
            return;
        }

        $btn.addClass('is-spinning');

        var data = {
            action: 'payamakio_dashboard_refresh',
            nonce: (typeof payamakioDashboardVars !== 'undefined') ? payamakioDashboardVars.nonce : ''
        };

        var ajaxUrl = (typeof payamakioDashboardVars !== 'undefined') ? payamakioDashboardVars.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');

        $.post(ajaxUrl, data, function (res) {
            $btn.removeClass('is-spinning');
            if (res && res.success && res.data && res.data.html) {
                $('#payamakio-dashboard-widget-content').html(res.data.html);
            }
        }, 'json').fail(function () {
            $btn.removeClass('is-spinning');
        });
    };

})(jQuery);
