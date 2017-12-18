jQuery(document).ready(function (jQuery) {

    var sezameTimer = false;

    var sezameAuthStatusFunction = function (authinfo) {
        var data = {
            'action': 'sezame_status_action',
            'sezameNonce': SezameAjax.nonce,
            auth_id: authinfo.auth_id,
            username: authinfo.username,
            started: authinfo.started,
            redirect: SezameAjax.redirect
        };

        jQuery.post(SezameAjax.ajaxurl, data).always(function (statusinfo) {
            if (statusinfo.status == 'initiated' && sezameTimer) {
                sezameTimer = setTimeout(function () {
                    sezameAuthStatusFunction(statusinfo);
                }, 1000);
            } else if (statusinfo.status == 'authorized') {
                if (statusinfo.redirect)
                    document.location.href = statusinfo.redirect;
            } else {
                if (statusinfo.message) {
                    jQuery('#sezame-message-box').show().text(statusinfo.message);
                }

                jQuery('#sezameLoginBubble').hide();
            }
        });
    };

    jQuery('#sezame-do-login').on('click', function () {
        var data = {
            'action': 'sezame_login_action',
            'username': jQuery('#user_login').val() || jQuery('#username').val(),
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (authinfo, textStatus, jqXHR) {
            jQuery('#sezameLoginBubble').show();
            jQuery('#sezame-message-box').hide();
            sezameTimer = true;
            sezameAuthStatusFunction(authinfo);

        }).fail(function (jqXHR) {
            jQuery('#sezame-message-box').show().text(jqXHR.responseJSON.message);
            jQuery('#sezameLoginBubble').hide();
        });

        return false;
    });

    jQuery("#sezame-cancel-login").click(function (evt) {
        if (sezameTimer) clearTimeout(sezameTimer);
        jQuery('#sezameLoginBubble').hide();
    });

    jQuery('#sezame-removepair').on('click', function () {
        var data = {
            'action': 'sezame_removepairing_action',
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

    jQuery('#sezame-fraud-option').on('click', function () {
        var data = {
            'action': 'sezame_savefraudoption_action',
            'sezame_fraud': jQuery(this).prop('checked'),
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });


    jQuery('#sezame-register').on('click', function () {
        var data = {
            'action': 'sezame_register_action',
            'sezameNonce': SezameAjax.nonce,
            'email': jQuery('#sezame-setting-email').val(),
            'fraud': jQuery('#sezame-setting-fraud').prop('checked'),
            'timeout': jQuery('#sezame-setting-timeout').val(),
            'enabled': jQuery('#sezame-setting-enabled').prop('checked')
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

    jQuery('#sezame-sign').on('click', function () {
        var data = {
            'action': 'sezame_sign_action',
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

    jQuery('#sezame-makecsr').on('click', function () {
        var data = {
            'action': 'sezame_makecsr_action',
            'sezameNonce': SezameAjax.nonce,
            'keypassword': jQuery('#sezame-setting-keypassword').val(),
            'clientcode': jQuery('#sezame-setting-clientcode').val(),
            'sharedsecret': jQuery('#sezame-setting-sharedsecret').val()
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

    jQuery('#sezame-expert-sign').on('click', function () {
        var data = {
            'action': 'sezame_signexpert_action',
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

    jQuery('#sezame-cancel').on('click', function () {
        var data = {
            'action': 'sezame_cancel_action',
            'sezameNonce': SezameAjax.nonce
        };

        jQuery.post(SezameAjax.ajaxurl, data, function (data, textStatus, jqXHR) {
            location.reload(true);
        }).fail(function (jqXHR) {
            alert(jqXHR.responseJSON.message);
        });

        return false;
    });

});