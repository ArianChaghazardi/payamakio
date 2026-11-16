/**
 * Payamakio Authentication JavaScript
 * Digits-style interactive SMS OTP, password login, registration, and modal handling
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

(function ($) {
    'use strict';

    /**
     * Convert Persian and Arabic numbers to standard English digits
     */
    function toEnglishDigits(str) {
        if (!str) return '';
        var persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        var arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        var english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        for (var i = 0; i < 10; i++) {
            str = str.replace(new RegExp(persian[i], 'g'), english[i]);
            str = str.replace(new RegExp(arabic[i], 'g'), english[i]);
        }
        return str;
    }

    /**
     * Convert English digits to Persian digits
     */
    function toPersianDigits(str) {
        if (str === null || str === undefined) return '';
        str = str.toString();
        var english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        var persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        for (var i = 0; i < 10; i++) {
            str = str.replace(new RegExp(english[i], 'g'), persian[i]);
        }
        return str;
    }

    /**
     * Validate Iranian mobile format: 09XXXXXXXXX
     */
    function isValidMobile(mobile) {
        var clean = toEnglishDigits(mobile).replace(/[^0-9]/g, '');
        if (clean.length === 10 && clean.indexOf('9') === 0) {
            clean = '0' + clean;
        }
        return (clean.length === 11 && clean.indexOf('09') === 0);
    }

    /**
     * Show floating alert inside card
     */
    function showAlert($card, message, type) {
        var $alert = $card.find('.payamakio-auth-alert');
        $alert.removeClass('alert-error alert-success').addClass(type === 'success' ? 'alert-success' : 'alert-error');
        $alert.find('.payamakio-auth-alert-text').html(message);
        $alert.slideDown(200);
    }

    /**
     * Clear alert
     */
    function hideAlert($card) {
        $card.find('.payamakio-auth-alert').slideUp(150);
    }

    /**
     * Initialize Auth Card
     */
    function initAuthCard($card) {
        var timerInterval = null;
        var defaultCooldown = parseInt($card.data('cooldown'), 10) || 120;
        var codeLength = parseInt($card.data('code-length'), 10) || 5;

        // 1. Tab Switcher
        $card.on('click', '.payamakio-auth-tab-btn', function () {
            var view = $(this).data('view');
            $card.find('.payamakio-auth-tab-btn').removeClass('is-active');
            $(this).addClass('is-active');

            $card.find('.payamakio-auth-view').removeClass('is-active');
            $card.find('.payamakio-view-' + view).addClass('is-active');
            hideAlert($card);
        });

        // 2. Eye Toggle for Passwords
        $card.on('click', '.payamakio-pwd-toggle', function () {
            var $input = $(this).siblings('.payamakio-pwd-input');
            var isPass = ($input.attr('type') === 'password');
            $input.attr('type', isPass ? 'text' : 'password');
        });

        // 3. Switch to Forgot Password View
        $card.on('click', '.payamakio-btn-switch-reset', function (e) {
            e.preventDefault();
            $card.find('.payamakio-auth-tab-btn').removeClass('is-active');
            $card.find('.payamakio-auth-view').removeClass('is-active');
            $card.find('.payamakio-view-reset').addClass('is-active');
            $card.find('.payamakio-form-reset-send').show();
            $card.find('.payamakio-form-reset-verify').hide();
            hideAlert($card);
        });

        // 4. Back to Login from Reset
        $card.on('click', '.payamakio-btn-back-to-login', function (e) {
            e.preventDefault();
            $card.find('.payamakio-auth-tab-btn[data-view="otp"]').trigger('click');
        });

        // 5. Start Resend Countdown Timer
        function startTimer(seconds) {
            clearInterval(timerInterval);
            var remaining = seconds || defaultCooldown;
            var $timerWrap = $card.find('.payamakio-auth-timer');
            var $resendBtn = $card.find('.payamakio-auth-btn-resend');
            var $countdown = $card.find('.payamakio-timer-countdown');

            $timerWrap.show();
            $resendBtn.hide();

            function updateDisplay() {
                var m = Math.floor(remaining / 60);
                var s = remaining % 60;
                var formatted = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
                $countdown.text(toPersianDigits(formatted));

                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    $timerWrap.hide();
                    $resendBtn.show();
                }
                remaining--;
            }

            updateDisplay();
            timerInterval = setInterval(updateDisplay, 1000);
        }

        // 6. Transition to Step 2 (Digit Verification)
        function showStep2(mobile, mode, fname, lname, email, pwd) {
            var $step1 = $card.find('.payamakio-form-otp-send');
            var $step2 = $card.find('.payamakio-form-otp-verify');

            $card.find('.payamakio-target-mobile').val(mobile);
            $card.find('.payamakio-target-mode').val(mode || 'auto');
            $card.find('.payamakio-target-fname').val(fname || '');
            $card.find('.payamakio-target-lname').val(lname || '');
            $card.find('.payamakio-target-email').val(email || '');
            $card.find('.payamakio-target-pwd').val(pwd || '');

            $card.find('.payamakio-auth-sent-number').text(mobile);

            $step1.slideUp(180);
            $card.find('.payamakio-form-register').slideUp(180);
            $step2.slideDown(200);

            // Clear digit boxes and focus first
            $card.find('.payamakio-digit-box').val('').removeClass('is-filled');
            setTimeout(function () {
                $card.find('.payamakio-digit-box').first().focus();
            }, 250);

            startTimer(defaultCooldown);
        }

        // 7. Edit Phone Button (Back to Step 1)
        $card.on('click', '.payamakio-auth-btn-edit-phone', function () {
            clearInterval(timerInterval);
            $card.find('.payamakio-form-otp-verify').slideUp(180);
            $card.find('.payamakio-form-otp-send').slideDown(200);
            hideAlert($card);
        });

        // 8. Handle OTP Send Request
        function handleSendOtp($form, mode, fname, lname, email, pwd) {
            var $btn = $form.find('.payamakio-auth-submit');
            var $mobileInput = $form.find('.payamakio-input-mobile');
            var mobile = toEnglishDigits($mobileInput.val()).trim();

            if (!isValidMobile(mobile)) {
                showAlert($card, 'لطفا شماره موبایل ۱۱ رقمی معتبر را وارد نمایید (مانند 09123456789).', 'error');
                $mobileInput.focus();
                return;
            }

            $btn.addClass('is-loading');
            hideAlert($card);

            var postData = {
                action: 'payamakio_auth_send_otp',
                nonce: (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.nonce : '',
                mobile: mobile,
                mode: mode || 'auto',
                first_name: fname || '',
                last_name: lname || '',
                email: email || '',
                password: pwd || ''
            };

            var ajaxUrl = (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.ajax_url : '/wp-admin/admin-ajax.php';

            $.post(ajaxUrl, postData, function (res) {
                $btn.removeClass('is-loading');
                if (res && res.success) {
                    showAlert($card, res.data.message || 'کد تایید پیامک شد.', 'success');
                    showStep2(mobile, mode, fname, lname, email, pwd);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'خطا در ارسال کد تایید. لطفا دوباره تلاش فرمایید.';
                    showAlert($card, errMsg, 'error');
                }
            }, 'json').fail(function () {
                $btn.removeClass('is-loading');
                showAlert($card, 'خطای ارتباط با سرور. لطفا اتصال اینترنت را بررسی کنید.', 'error');
            });
        }

        // Form 1: OTP Send Submit
        $card.on('submit', '.payamakio-form-otp-send', function (e) {
            e.preventDefault();
            handleSendOtp($(this), 'auto');
        });

        // Form 3: Register Submit -> Sends OTP
        $card.on('submit', '.payamakio-form-register', function (e) {
            e.preventDefault();
            var fname = $(this).find('input[name="first_name"]').val() || '';
            var lname = $(this).find('input[name="last_name"]').val() || '';
            var email = $(this).find('input[name="email"]').val() || '';
            handleSendOtp($(this), 'register', fname, lname, email, '');
        });

        // Resend Button Click
        $card.on('click', '.payamakio-auth-btn-resend', function () {
            var mobile = $card.find('.payamakio-target-mobile').val();
            var mode = $card.find('.payamakio-target-mode').val();
            var fname = $card.find('.payamakio-target-fname').val();
            var lname = $card.find('.payamakio-target-lname').val();
            var email = $card.find('.payamakio-target-email').val();
            var pwd = $card.find('.payamakio-target-pwd').val();

            if (!mobile) return;
            handleSendOtp($card.find('.payamakio-form-otp-verify'), mode, fname, lname, email, pwd);
        });

        // 9. Digit Boxes Auto-Advance & Paste
        $card.on('input', '.payamakio-digit-box', function (e) {
            var val = toEnglishDigits($(this).val()).replace(/[^0-9]/g, '');
            $(this).val(val ? val.charAt(0) : '');

            if (val) {
                $(this).addClass('is-filled');
                var $next = $(this).next('.payamakio-digit-box');
                if ($next.length) {
                    $next.focus();
                }
            } else {
                $(this).removeClass('is-filled');
            }

            // Collect full code
            var fullCode = '';
            $card.find('.payamakio-digit-box').each(function () {
                fullCode += $(this).val();
            });
            $card.find('.payamakio-full-otp-input').val(fullCode);

            // Auto submit when all boxes filled
            if (fullCode.length === codeLength) {
                $card.find('.payamakio-form-otp-verify').trigger('submit');
            }
        });

        // Backspace handling in digit boxes
        $card.on('keydown', '.payamakio-digit-box', function (e) {
            if (e.key === 'Backspace' && !$(this).val()) {
                var $prev = $(this).prev('.payamakio-digit-box');
                if ($prev.length) {
                    $prev.focus().val('');
                }
            }
        });

        // Paste Handling
        $card.on('paste', '.payamakio-digit-box', function (e) {
            e.preventDefault();
            var pasteData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            var cleanDigits = toEnglishDigits(pasteData).replace(/[^0-9]/g, '');

            if (cleanDigits.length >= codeLength) {
                $card.find('.payamakio-digit-box').each(function (idx) {
                    var ch = cleanDigits.charAt(idx);
                    $(this).val(ch).addClass('is-filled');
                });
                $card.find('.payamakio-full-otp-input').val(cleanDigits.substring(0, codeLength));
                $card.find('.payamakio-form-otp-verify').trigger('submit');
            }
        });

        // 10. Verify OTP & Login / Register
        $card.on('submit', '.payamakio-form-otp-verify', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.payamakio-auth-submit');

            // Gather digits
            var otpCode = '';
            $card.find('.payamakio-digit-box').each(function () {
                otpCode += toEnglishDigits($(this).val());
            });

            if (otpCode.length < codeLength) {
                showAlert($card, 'لطفا کد تایید ' + toPersianDigits(codeLength) + ' رقمی را به صورت کامل وارد نمایید.', 'error');
                return;
            }

            $btn.addClass('is-loading');
            hideAlert($card);

            var postData = {
                action: 'payamakio_auth_verify_otp',
                nonce: (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.nonce : '',
                mobile: $form.find('.payamakio-target-mobile').val(),
                otp_code: otpCode,
                mode: $form.find('.payamakio-target-mode').val(),
                first_name: $form.find('.payamakio-target-fname').val(),
                last_name: $form.find('.payamakio-target-lname').val(),
                email: $form.find('.payamakio-target-email').val(),
                password: $form.find('.payamakio-target-pwd').val(),
                redirect_to: $form.find('input[name="redirect_to"]').val()
            };

            var ajaxUrl = (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.ajax_url : '/wp-admin/admin-ajax.php';

            $.post(ajaxUrl, postData, function (res) {
                $btn.removeClass('is-loading');
                if (res && res.success) {
                    showAlert($card, res.data.message || 'ورود با موفقیت انجام شد. در حال انتقال...', 'success');
                    clearInterval(timerInterval);
                    setTimeout(function () {
                        window.location.href = res.data.redirect || window.location.href;
                    }, 800);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'کد تایید وارد شده نامعتبر یا منقضی شده است.';
                    showAlert($card, errMsg, 'error');
                    $card.find('.payamakio-digit-box').val('').removeClass('is-filled').first().focus();
                }
            }, 'json').fail(function () {
                $btn.removeClass('is-loading');
                showAlert($card, 'خطا در برقراری ارتباط با سرور.', 'error');
            });
        });

        // 11. Password Login Submit
        $card.on('submit', '.payamakio-form-password-login', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.payamakio-auth-submit');

            $btn.addClass('is-loading');
            hideAlert($card);

            var postData = {
                action: 'payamakio_auth_password_login',
                nonce: (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.nonce : '',
                log: toEnglishDigits($form.find('input[name="log"]').val()).trim(),
                pwd: $form.find('input[name="pwd"]').val(),
                remember: $form.find('input[name="remember"]').is(':checked') ? 'forever' : '',
                redirect_to: $form.find('input[name="redirect_to"]').val()
            };

            var ajaxUrl = (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.ajax_url : '/wp-admin/admin-ajax.php';

            $.post(ajaxUrl, postData, function (res) {
                $btn.removeClass('is-loading');
                if (res && res.success) {
                    showAlert($card, res.data.message || 'ورود موفقیت آمیز بود. در حال انتقال...', 'success');
                    setTimeout(function () {
                        window.location.href = res.data.redirect || window.location.href;
                    }, 800);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'اطلاعات ورود نادرست است.';
                    showAlert($card, errMsg, 'error');
                }
            }, 'json').fail(function () {
                $btn.removeClass('is-loading');
                showAlert($card, 'خطا در ورود به حساب. لطفا مجددا تلاش کنید.', 'error');
            });
        });

        // 12. Forgot Password Flow
        $card.on('submit', '.payamakio-form-reset-send', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.payamakio-auth-submit');
            var mobile = toEnglishDigits($form.find('.payamakio-input-mobile').val()).trim();

            if (!isValidMobile(mobile)) {
                showAlert($card, 'لطفا شماره موبایل ۱۱ رقمی معتبر را وارد نمایید.', 'error');
                return;
            }

            $btn.addClass('is-loading');
            hideAlert($card);

            var postData = {
                action: 'payamakio_auth_send_otp',
                nonce: (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.nonce : '',
                mobile: mobile,
                mode: 'reset'
            };

            var ajaxUrl = (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.ajax_url : '/wp-admin/admin-ajax.php';

            $.post(ajaxUrl, postData, function (res) {
                $btn.removeClass('is-loading');
                if (res && res.success) {
                    showAlert($card, res.data.message || 'کد بازیابی ارسال گردید.', 'success');
                    $form.slideUp(180);
                    $card.find('.payamakio-target-reset-mobile').val(mobile);
                    $card.find('.payamakio-form-reset-verify').slideDown(200);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'کاربری با این شماره یافت نشد.';
                    showAlert($card, errMsg, 'error');
                }
            }, 'json').fail(function () {
                $btn.removeClass('is-loading');
                showAlert($card, 'خطا در ارسال کد بازیابی.', 'error');
            });
        });

        $card.on('submit', '.payamakio-form-reset-verify', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.payamakio-auth-submit');

            $btn.addClass('is-loading');
            hideAlert($card);

            var postData = {
                action: 'payamakio_auth_reset_password',
                nonce: (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.nonce : '',
                mobile: $form.find('.payamakio-target-reset-mobile').val(),
                otp_code: toEnglishDigits($form.find('#payamakio-reset-code').val()).trim(),
                new_password: $form.find('#payamakio-new-pass').val()
            };

            var ajaxUrl = (typeof payamakioAuthVars !== 'undefined') ? payamakioAuthVars.ajax_url : '/wp-admin/admin-ajax.php';

            $.post(ajaxUrl, postData, function (res) {
                $btn.removeClass('is-loading');
                if (res && res.success) {
                    showAlert($card, res.data.message || 'رمز جدید ذخیره شد و وارد شدید. در حال انتقال...', 'success');
                    setTimeout(function () {
                        window.location.href = res.data.redirect || window.location.href;
                    }, 1000);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'کد بازیابی نامعتبر است.';
                    showAlert($card, errMsg, 'error');
                }
            }, 'json').fail(function () {
                $btn.removeClass('is-loading');
                showAlert($card, 'خطا در تغییر رمز عبور.', 'error');
            });
        });
    }

    /**
     * Modal Handling
     */
    function initModal() {
        var $modal = $('#payamakio-auth-modal');
        if (!$modal.length) return;

        function openModal() {
            $modal.fadeIn(200);
            $('body').addClass('payamakio-modal-open');
        }

        function closeModal() {
            $modal.fadeOut(180);
            $('body').removeClass('payamakio-modal-open');
        }

        $(document).on('click', '.payamakio-auth-modal-btn, a[href="#payamakio-auth"]', function (e) {
            e.preventDefault();
            openModal();
        });

        $modal.on('click', '.payamakio-auth-modal-close, .payamakio-auth-modal-backdrop', function () {
            closeModal();
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                closeModal();
            }
        });
    }

    // Document Ready Boot
    $(document).ready(function () {
        $('.payamakio-auth-card').each(function () {
            initAuthCard($(this));
        });
        initModal();
    });

})(jQuery);
