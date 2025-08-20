<?php
/**
 * Initial Traffic Source tracking functionality
 *
 * @package    GTMConsentManager
 * @subpackage GTMConsentManager/includes
 */

class GTM_Initial_Traffic_Source {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'output_tracking_script'));
    }

    /**
     * Output the tracking script in footer
     */
    public function output_tracking_script() {
        // Only output script if user hasn't been tracked yet
        if (!isset($_COOKIE['GTM_ITS'])) {
            ?>
            <script>
            /**
             * Initial Traffic Source Tracker
             * Original script by Lunametrics, modified by Analytics Mania
             * Further adapted for Cookie Consent for Google Tag Manager
             */
            (function(document) {
                var referrer = document.referrer;
                var thisPathname = window.location.pathname;
                var gaReferral = {
                    'utmcsr': '(direct)',
                    'utmcmd': '(none)',
                    'utmccn': '(not set)',
                    'pathname': thisPathname
                };
                var thisHostname = document.location.hostname;
                var thisDomain = getDomain_(thisHostname);
                var referringDomain = getDomain_(document.referrer);
                var cookieExpiration = new Date(+new Date() + 1000 * 60 * 60 * 24);
                var qs = document.location.search.replace('?', '');
                var hash = document.location.hash.replace('#', '');
                var gaParams = parseGoogleParams(qs + '#' + hash);
                var referringInfo = parseGaReferrer(referrer);
                var storedVals = getCookie_('__utmz') || getCookie_('__utmzz');
                var newCookieVals = [];
                var keyMap = {
                    'utm_source': 'utmcsr',
                    'utm_medium': 'utmcmd',
                    'utm_campaign': 'utmccn',
                    'utm_content': 'utmcct',
                    'utm_term': 'utmctr',
                    'gclid': 'utmgclid',
                    'dclid': 'utmdclid'
                };

                var keyFilter = ['utmcsr', 'utmcmd', 'utmccn', 'utmcct', 'utmctr', 'pathname'];
                var keyName, values, _val, _key, raw, key, len, i;

                if (sessionCookie && referringDomain === thisDomain) {
                    gaParams = null;
                    referringInfo = null;
                }

                if (gaParams && (gaParams.utm_source || gaParams.gclid || gaParams.dclid)) {
                    for (key in gaParams) {
                        if (typeof gaParams[key] !== 'undefined') {
                            keyName = keyMap[key];
                            gaReferral[keyName] = gaParams[key];
                        }
                    }

                    if (gaParams.gclid || gaParams.dclid) {
                        gaReferral.utmcsr = 'google';
                        gaReferral.utmcmd = gaReferral.utmgclid ? 'cpc' : 'cpm';
                    }
                } else if (referringInfo) {
                    gaReferral.utmcsr = referringInfo.source;
                    gaReferral.utmcmd = referringInfo.medium;
                    if (referringInfo.term) gaReferral.utmctr = referringInfo.term;
                } else if (storedVals) {
                    values = {};
                    raw = storedVals.split('|');
                    len = raw.length;

                    for (i = 0; i < len; i++) {
                        _val = raw[i].split('=');
                        _key = _val[0].split('.').pop();
                        values[_key] = _val[1];
                    }

                    gaReferral = values;
                }

                for (key in gaReferral) {
                    if (typeof gaReferral[key] !== 'undefined' && keyFilter.indexOf(key) > -1) {
                        newCookieVals.push(key + '=' + gaReferral[key]);
                    }
                }

                if (!getCookie_('GTM_ITS')) {
                    writeCookie_('GTM_ITS', newCookieVals.join('|'), cookieExpiration, '/', thisDomain);
                }
                function parseGoogleParams(str) {
                    var campaignParams = ['source', 'medium', 'campaign', 'term', 'content'];
                    var regex = new RegExp('(utm_(' + campaignParams.join('|') + ')|(d|g)clid)=.*?([^&#]*|$)', 'gi');
                    var gaParams = str.match(regex);
                    var paramsObj, vals, len, i;

                    if (gaParams) {
                        paramsObj = {};
                        len = gaParams.length;

                        for (i = 0; i < len; i++) {
                            vals = gaParams[i].split('=');
                            if (vals) {
                                paramsObj[vals[0]] = vals[1];
                            }
                        }
                    }

                    return paramsObj;
                }

                function parseGaReferrer(referrer) {
                    if (!referrer) return;

                    var searchEngines = {
                        'daum.net': { 'p': 'q', 'n': 'daum' },
                        'eniro.se': { 'p': 'search_word', 'n': 'eniro' },
                        'naver.com': { 'p': 'query', 'n': 'naver' },
                        'yahoo.com': { 'p': 'p', 'n': 'yahoo' },
                        'msn.com': { 'p': 'q', 'n': 'msn' },
                        'bing.com': { 'p': 'q', 'n': 'live' },
                        'google': { 'p': 'q', 'n': 'google' },
                        // Add more search engines as needed
                    };

                    var a = document.createElement('a');
                    var values = {};
                    var searchEngine, termRegex, term;

                    a.href = referrer;

                    // Shim for the billion google search engines
                    if (a.hostname.indexOf('google') > -1) {
                        referringDomain = 'google';
                    }

                    if (searchEngines[referringDomain]) {
                        searchEngine = searchEngines[referringDomain];
                        termRegex = new RegExp(searchEngine.p + '=.*?([^&#]*|$)', 'gi');
                        term = a.search.match(termRegex);

                        values.source = searchEngine.n;
                        values.medium = 'organic';
                        values.term = (term ? term[0].split('=')[1] : '') || '(not provided)';
                    } else if (referringDomain !== thisDomain) {
                        values.source = a.hostname;
                        values.medium = 'referral';
                    }

                    return values;
                }

                function writeCookie_(name, value, expiration, path, domain) {
                    var str = name + '=' + value + ';';
                    if (expiration) str += 'Expires=' + expiration.toGMTString() + ';';
                    if (path) str += 'Path=' + path + ';';
                    if (domain) str += 'Domain=' + domain + ';';

                    document.cookie = str;
                }

                function getCookie_(name) {
                    var cookies = '; ' + document.cookie;
                    var cvals = cookies.split('; ' + name + '=');
                    if (cvals.length > 1) return cvals.pop().split(';')[0];
                }

                function getDomain_(url) {
                    if (!url) return;

                    var a = document.createElement('a');
                    a.href = url;

                    try {
                        return a.hostname.match(/[^.]*\.[^.]{2,3}(?:\.[^.]{2,3})?$/)[0];
                    } catch(squelch) {}
                }
            })(document);
            </script>
            <?php
        }
    }
}
