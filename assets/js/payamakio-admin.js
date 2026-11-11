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

    // Copy Pattern Text to Clipboard
    $(document).on('click', '.payamakio-btn-copy', function (e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $textarea = $('#' + targetId);
        var text = $textarea.val();
        var $btn = $(this);
        var origHtml = $btn.html();

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                $btn.html('<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> کپی شد!').css('color', '#059669');
                setTimeout(function () {
                    $btn.html(origHtml).css('color', '');
                }, 2200);
            });
        } else {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            $btn.html('<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> کپی شد!').css('color', '#059669');
            setTimeout(function () {
                $btn.html(origHtml).css('color', '');
            }, 2200);
        }
    });

    // Auto Register Pattern in IPPanel via Edge API
    $(document).on('click', '.payamakio-btn-auto-reg', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var patternKey = $btn.data('key');
        var targetInputId = $btn.data('target-input');
        var messageText = $('#def-pattern-' + patternKey).val();

        if (!confirm('آیا مایلید این الگوی پیامک به صورت مستقیم در پنل IPPanel شما ثبت گردد؟')) {
            return;
        }

        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="payamakio-spinner" style="display:inline-block;width:12px;height:12px;margin-left:6px;vertical-align:middle;"></span> در حال ثبت...');

        $.ajax({
            url: payamakio_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'payamakio_register_pattern',
                nonce: payamakio_vars.nonce,
                pattern_key: patternKey,
                message: messageText
            },
            success: function (res) {
                $btn.prop('disabled', false).html(origHtml);
                if (res.success) {
                    $('#' + targetInputId).val(res.data.pattern_code).css({
                        'border-color': '#10b981',
                        'box-shadow': '0 0 0 3px rgba(16, 185, 129, 0.2)'
                    });
                    alert('الگو با موفقیت در سامانه آی پی پنل ثبت شد!\nکد الگو: ' + res.data.pattern_code + '\nوضعیت: ' + res.data.status + '\nکد پترن به صورت خودکار در کادر مربوطه درج شد.');
                } else {
                    alert('خطا در ثبت الگو: ' + (res.data ? res.data.message : 'خطای سرور'));
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(origHtml);
                alert('خطا در برقراری ارتباط با سرور.');
            }
        });
    });

    // Variable Chip Click - Insert into Textarea
    $(document).on('click', '.payamakio-var-pill', function () {
        var varCode = $(this).find('code').text();
        var $card = $(this).closest('.payamakio-default-pattern-card');
        var $textarea = $card.find('.payamakio-pattern-textarea');

        if ($textarea.length) {
            var val = $textarea.val();
            if (val.indexOf(varCode) === -1) {
                $textarea.val(val + ' ' + varCode);
            }
        }
    });

    // Live Test SMS Send Runner
    $('#payamakio-btn-test-send').on('click', function (e) {
        e.preventDefault();

        var mobile  = $('#test_mobile').val();
        var pattern = $('#test_pattern').val();
        var param1  = $('#test_param1').val();
        var param2  = $('#test_param2').val();

        if (!mobile) {
            alert('لطفا شماره موبایل گیرنده را وارد فرمایید.');
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
