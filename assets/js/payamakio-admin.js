/**
 * Payamakio Admin JavaScript
 * Author: Arian Chaghazardi
 * Brand: HectorTM
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Tab Switching
    $('.payamakio-tab-btn').on('click', function (e) {
        e.preventDefault();
        var targetId = $(this).data('tab');

        $('.payamakio-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.payamakio-tab-content').removeClass('active');
        $('#' + targetId).addClass('active');

        if (history.pushState) {
            history.pushState(null, null, '#' + targetId);
        }
    });

    // Check URL hash on load
    if (window.location.hash) {
        var hash = window.location.hash.replace('#', '');
        var $btn = $('.payamakio-tab-btn[data-tab="' + hash + '"]');
        if ($btn.length) {
            $btn.trigger('click');
        }
    }

    // Gateway Radio Switcher
    $('input[name="payamakio[gateway]"]').on('change', function () {
        var selectedGw = $(this).val();

        $('.payamakio-gateway-option').removeClass('selected');
        $(this).closest('.payamakio-gateway-option').addClass('selected');

        $('.payamakio-sub-settings').hide();
        $('#gw-settings-' + selectedGw).fadeIn(200);
    });

    // Live Test SMS Send Runner
    $('#payamakio-btn-test-send').on('click', function (e) {
        e.preventDefault();

        var mobile  = $('#test_mobile').val();
        var pattern = $('#test_pattern').val();
        var param1  = $('#test_param1').val();
        var param2  = $('#test_param2').val();

        if (!mobile || !pattern) {
            alert('لطفا شماره موبایل گیرنده و کد پترن را وارد فرمایید.');
            return;
        }

        var $btn = $(this);
        var $spinner = $('#payamakio-test-spinner');
        var $result = $('#payamakio-test-result');

        $btn.prop('disabled', true);
        $spinner.show();
        $result.hide().empty();

        $.ajax({
            url: payamakio_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'payamakio_send_test',
                nonce: payamakio_vars.nonce,
                mobile: mobile,
                pattern: pattern,
                param1: param1,
                param2: param2
            },
            success: function (res) {
                $spinner.hide();
                $btn.prop('disabled', false);

                if (res.success) {
                    $result.html(
                        '<div class="payamakio-toast toast-success">' +
                        '<strong>ارسال موفقیت آمیز بود!</strong> ' + res.data.message +
                        (res.data.tracking ? '<br><small>کد پیگیری درگاه: ' + res.data.tracking + '</small>' : '') +
                        '</div>'
                    ).fadeIn(200);
                } else {
                    $result.html(
                        '<div class="payamakio-toast toast-error">' +
                        '<strong>خطا در ارسال:</strong> ' + (res.data ? res.data.message : 'پاسخ نامعتبر از سرور') +
                        '</div>'
                    ).fadeIn(200);
                }
            },
            error: function () {
                $spinner.hide();
                $btn.prop('disabled', false);
                $result.html(
                    '<div class="payamakio-toast toast-error">' +
                    'خطای ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی نمایید.' +
                    '</div>'
                ).fadeIn(200);
            }
        });
    });

    // Clear Logs
    $('#payamakio-btn-clear-logs').on('click', function (e) {
        e.preventDefault();

        if (!confirm('آیا از پاکسازی تمام تاریخچه پیامک های ارسالی اطمینان دارید؟')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('در حال پاکسازی...');

        $.ajax({
            url: payamakio_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'payamakio_clear_logs',
                nonce: payamakio_vars.nonce
            },
            success: function (res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert('خطا در پاکسازی تاریخچه.');
                    $btn.prop('disabled', false).text('پاکسازی تاریخچه');
                }
            },
            error: function () {
                alert('خطا در برقراری ارتباط با سرور.');
                $btn.prop('disabled', false).text('پاکسازی تاریخچه');
            }
        });
    });
});
