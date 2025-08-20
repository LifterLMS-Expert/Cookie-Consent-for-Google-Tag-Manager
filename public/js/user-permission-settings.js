jQuery(document).ready(function ($) {
    // Set cookie with 90 day expiry

    'use strict';

    // --- UTM and Referrer Tracking ---
    function setWwcCookie(name, value) {
        let expires = new Date();
        expires.setTime(expires.getTime() + (90 * 24 * 60 * 60 * 1000)); // 90 days
        document.cookie = name + '=' + encodeURIComponent(value) + 
            ';expires=' + expires.toUTCString() + 
            ';path=/' +
            ';Secure' +
            ';SameSite=Strict';
    }

    function getQueryParam(param) {
        let urlParams = new URLSearchParams(window.location.search);
        const value = urlParams.get(param) || '';
        // Sanitize UTM parameters to prevent injection
        return value.replace(/[<>'"{}()&=;]/g, '').substring(0, 255);
    }

    // Set UTM and referrer cookies on page load
    setWwcCookie('lle_utm_source', getQueryParam('utm_source'));
    setWwcCookie('lle_utm_medium', getQueryParam('utm_medium'));
    setWwcCookie('lle_utm_campaign', getQueryParam('utm_campaign'));
    
    // Only store referrer domain to prevent information leakage
    try {
        const referrerUrl = document.referrer ? new URL(document.referrer) : new URL(window.location.origin);
        setWwcCookie('lle_referrer_url', referrerUrl.hostname);
    } catch (e) {
        console.warn('Invalid referrer URL, using empty value');
        setWwcCookie('lle_referrer_url', '');
    }

    function getWwcCookie(name) {
        let nameEQ = name + "=";
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                try {
                    return decodeURIComponent(c.substring(nameEQ.length));
                } catch (e) {
                    console.warn('Failed to decode cookie value for ' + name);
                    return '';
                }
            }
        }
        return "";
    }

    function parseGtmItsCookie() {
        const gtmIts = getWwcCookie('GTM_ITS');
        if (!gtmIts) return {};
        
        const parts = gtmIts.split('|');
        const result = {};

        parts.forEach(part => {
            const [key, value] = part.split('=');
            if (key && value) {
                // Sanitize values from GTM cookie
                const sanitizedValue = value.replace(/[<>'"{}()&;]/g, '').substring(0, 255);
                result[key.trim()] = sanitizedValue;
            }
        });

        return result;
    }

    let utm_source = getWwcCookie('lle_utm_source');
    let utm_medium = getWwcCookie('lle_utm_medium');
    let utm_campaign = getWwcCookie('lle_utm_campaign');
    let referrer_url = getWwcCookie('lle_referrer_url');

    // Fallback to GTM_ITS if any value is empty
    const gtmData = parseGtmItsCookie();

    utm_source = utm_source || gtmData.utmcsr || '';
    utm_medium = utm_medium || gtmData.utmcmd || '';
    utm_campaign = utm_campaign || gtmData.utmccn || '';
    referrer_url = referrer_url || gtmData.pathname || '';
    
    function setConsentCookie(name, value) {
        let expires = new Date();
        expires.setTime(expires.getTime() + (90 * 24 * 60 * 60 * 1000)); // 90 days
        document.cookie = name + "=" + encodeURIComponent(value) + 
            ";expires=" + expires.toUTCString() + 
            ";path=/" + 
            ";Secure" +
            ";SameSite=Strict";
    }

    function deleteCookie(name) {
        document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; Secure; SameSite=Strict";
    }

    function sanitizeMessage(message) {
        if (!message || typeof message !== 'string') {
            return 'Unknown error occurred';
        }
        // Use jQuery to safely escape HTML
        return $('<div>').text(message).html();
    }

    var consentNotice = $("#gtm-consent-notice");
    var consentMessage = $("#gtm-consent-message");
    var consentYesButton = $("#gtm-consent-yes");
    var consentNoButton = $("#gtm-consent-no");
    var consentCloseButton = $("#gtm-consent-close");
    var consentMinimized = $("#gtm-consent-minimized");

    // Check for auto-open setting and consent status
    var shouldAutoOpen = consentNotice.data('auto-open') === 1;
    var hasConsent = document.cookie.indexOf("gtm_consent_granted=yes") !== -1 || 
        document.cookie.indexOf("gtm_consent_declined=yes") !== -1 ||
        document.cookie.indexOf("gtm_consent_no_action=yes") !== -1;
  
    if (hasConsent) {
        consentNotice.hide();
        consentMinimized.show();
    } else if (shouldAutoOpen) {
        consentNotice.show();
        consentMinimized.hide();
    } else {
        consentNotice.hide();
        consentMinimized.show();
    }

    consentMinimized.on("click", function() {
        consentNotice.show();
        consentMinimized.hide();
    });

    consentCloseButton.on("click", function(e) {
        e.preventDefault();

        consentNotice.fadeOut(500, function() {
            consentMinimized.fadeIn(500);
            jQuery("p#gtm-consent-message").hide();
        });

        // Only send AJAX if no "granted" or "declined" cookie exists
        var alreadyHandled = document.cookie.indexOf("gtm_consent_granted=yes") !== -1 ||
                            document.cookie.indexOf("gtm_consent_declined=yes") !== -1;

        if (!alreadyHandled) {
            $.post(
                gtm_consent_ajax.ajax_url,
                {
                    action: "gtm_consent_no_action",
                    _ajax_nonce: gtm_consent_ajax.nonce,
                    lle_utm_source: utm_source,
                    lle_utm_medium: utm_medium,
                    lle_utm_campaign: utm_campaign,
                    lle_referrer_url: referrer_url,
                }
            ).done(function(response) {
                if (response.success) {
                    setConsentCookie('gtm_consent_no_action', 'yes');
                } else {
                    console.warn('Failed to save no-action preference:', response);
                }
            }).fail(function() {
                console.warn('AJAX request failed for no-action preference');
            });
        } else {
            deleteCookie('gtm_consent_no_action');
        }
    });

    consentNoButton.on("click", function () {
        // Delete conflicting cookies FIRST - this is the fix
        deleteCookie('gtm_consent_no_action');
        deleteCookie('gtm_consent_granted');
        
        consentMessage.text('Your preference has been saved.').css("color", "orange").show();
        jQuery('button#gtm-consent-yes').html('Accept');
        jQuery('button#gtm-consent-no').html('Declined');
        
        $.post(
            gtm_consent_ajax.ajax_url,
            {
                action: "gtm_consent_declined",
                _ajax_nonce: gtm_consent_ajax.nonce,
                lle_utm_source: utm_source,
                lle_utm_medium: utm_medium,
                lle_utm_campaign: utm_campaign,
                lle_referrer_url: referrer_url,
            }
        ).done(function (response) {
            if (response.success) {
                consentNotice.fadeOut(500, function() {
                    consentMinimized.fadeIn(500);
                    jQuery("p#gtm-consent-message").hide();
                });
                setConsentCookie('gtm_consent_declined', 'yes');
            } else {
                // Sanitize error message to prevent XSS
                const sanitizedMessage = sanitizeMessage(response.data && response.data.message);
                consentMessage.text("Error: " + sanitizedMessage).css("color", "red").show();
            }
        }).fail(function() {
            consentMessage.text("Error occurred while saving your preference").css("color", "red").show();
        });

        // Update Google Analytics consent
        if (typeof gtag !== 'undefined') {
            gtag("consent", "update", {
                ad_storage: "denied",
                analytics_storage: "denied",
                ad_user_data: "denied",
                ad_personalization: "denied",
                functionality_storage: "denied",
                security_storage: "denied",
                personalization_storage: "denied"
            });
        }
    });

    consentYesButton.on("click", function () {
        // Delete conflicting cookies FIRST
        deleteCookie('gtm_consent_declined');
        deleteCookie('gtm_consent_no_action');
        
        setConsentCookie('gtm_consent_granted', 'yes');
        jQuery('button#gtm-consent-yes').html('Accepted');
        jQuery('button#gtm-consent-no').html('Decline');
        consentMessage.text('Thank you for accepting our cookie policy!').css("color", "green").show();
        
        // Update Google Analytics consent
        if (typeof gtag !== 'undefined') {
            gtag("consent", "update", {
                ad_storage: "granted",
                analytics_storage: "granted",
                ad_user_data: "granted",
                ad_personalization: "granted",
                functionality_storage: "granted",
                security_storage: "granted",
                personalization_storage: "granted",
            });
        }

        $.post(
            gtm_consent_ajax.ajax_url,
            {
                action: "gtm_consent_granted",
                _ajax_nonce: gtm_consent_ajax.nonce,
                lle_utm_source: utm_source,
                lle_utm_medium: utm_medium,
                lle_utm_campaign: utm_campaign,
                lle_referrer_url: referrer_url,
            }
        ).done(function (response) {
            if (response.success) {
                // Validate and load GTM script
                const gtmId = gtm_consent_ajax.gtm_id;
                if (!gtmId || typeof gtmId !== 'string') {
                    console.error('GTM ID is missing or invalid');
                    consentMessage.text("Configuration error: Invalid GTM ID").css("color", "red").show();
                    return;
                }

                // Validate GTM ID format (should match GTM-XXXXXXX or G-XXXXXXXXXX pattern)
                const gtmIdPattern = /^(GTM-[A-Z0-9]+|G-[A-Z0-9]+)$/i;
                if (!gtmIdPattern.test(gtmId)) {
                    console.error('GTM ID format is invalid:', gtmId);
                    consentMessage.text("Configuration error: Invalid GTM ID format").css("color", "red").show();
                    return;
                }

                var gtmScript = document.createElement("script");
                gtmScript.async = true;
                gtmScript.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(gtmId)}`;
                gtmScript.crossOrigin = "anonymous";
                gtmScript.integrity = ""; // Add SRI hash if available
                
                gtmScript.onload = function () {
                    window.dataLayer = window.dataLayer || [];
                    function gtag() {
                        dataLayer.push(arguments);
                    }
                    gtag("js", new Date());
                    gtag("config", gtmId);
                };

                gtmScript.onerror = function() {
                    console.error('Failed to load GTM script');
                    consentMessage.text("Failed to load tracking script").css("color", "red").show();
                };

                document.head.appendChild(gtmScript);
                
                consentNotice.fadeOut(250, function() {
                    $(this).hide();
                    consentMinimized.show();
                    jQuery("p#gtm-consent-message").hide();
                });
            } else {
                // Sanitize error message to prevent XSS
                const sanitizedMessage = sanitizeMessage(response.data && response.data.message);
                consentMessage.text("Error: " + sanitizedMessage).css("color", "red").show();
            }
        }).fail(function() {
            consentMessage.text("Error occurred while saving your preference").css("color", "red").show();
        });
    });

    // Auto accept logic
    if (typeof gtm_consent_ajax !== 'undefined' && 
        gtm_consent_ajax.auto_accept && 
        !hasConsent) {
        // Simulate Accept button click
        if (consentYesButton && consentYesButton.length) {
            setTimeout(function() {
                consentYesButton.trigger('click');
            }, 100); // Small delay to ensure everything is initialized
        }
        // Hide notice and minimized icon immediately
        consentNotice.hide();
        consentMinimized.hide();
        return;
    }
});