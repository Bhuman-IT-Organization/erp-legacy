/**
 * Voice commands for the admin area.
 *
 * The microphone button in the header (application/views/admin/includes/header.php)
 * calls toggleVoiceCommand(). The button and the report endpoints it drives were
 * already in the application; this file is the driver that was missing, which is why
 * clicking the button raised "toggleVoiceCommand is not defined".
 *
 * Recognition is the browser's Web Speech API. Nothing is sent to a third party: the
 * transcript is matched here and the result is an ordinary navigation or an ordinary
 * request to this application.
 *
 * IMPORTANT - the Web Speech API only works in a secure context. That means HTTPS, or
 * http://localhost. Over plain HTTP on a LAN address (http://192.168.x.x/...) the
 * browser refuses to start the microphone, and the button will report that rather
 * than failing silently.
 *
 * Adding a command: add an entry to REPORTS for anything that maps to a URL. Anything
 * that is reachable from the sidebar needs no entry at all - "go to ..." resolves
 * against the rendered menu, so it automatically follows the signed-in user's
 * permissions and stays correct when menus change.
 */
(function (window, $) {
    "use strict";

    // Every user-visible string, kept together so this can be translated later. The
    // header button title is hardcoded English in header.php, so these match it.
    var STR = {
        unsupported:
            "Voice commands need Chrome, Edge or Safari. This browser does not support speech recognition.",
        insecure:
            "Voice commands need a secure connection. Open the site over HTTPS (or via localhost) and try again.",
        denied:
            "Microphone access was blocked. Allow the microphone for this site in your browser settings, then try again.",
        noMic: "No microphone was found.",
        listening: "Listening\u2026 say a command, or say \u201Chelp\u201D",
        noSpeech: "Nothing was heard. Try again.",
        heard: "Heard: ",
        unknown:
            "I did not recognise that command. Say \u201Chelp\u201D to see what is available.",
        stopped: "Voice commands stopped.",
        opening: "Opening ",
        searching: "Searching for ",
        noSearchBox: "The search box is not available on this page.",
        genericError: "Voice recognition failed: "
    };

    // Reports live outside the sidebar, so they are listed explicitly. Each maps to a
    // controller method that streams a PDF and accepts optional from_date / to_date.
    // "phrases" are matched longest-first, so a more specific phrase always wins over
    // a shorter one that is a substring of it.
    var REPORTS = [
        {
            url: "accounting/trial_balance_pdf",
            label: "Trial Balance",
            phrases: ["trial balance", "trialbalance"]
        },
        {
            url: "accounting/profit_and_loss_pdf",
            label: "Profit and Loss",
            phrases: [
                "profit and loss statement",
                "profit and loss",
                "profit & loss",
                "profit loss",
                "income statement",
                "p and l",
                "pnl"
            ]
        },
        {
            url: "accounting/balance_sheet_pdf",
            label: "Balance Sheet",
            phrases: ["balance sheet", "balancesheet"]
        },
        {
            url: "purchase/purchase_inv_report_pdf",
            label: "Purchase Invoice Report",
            phrases: [
                "purchase invoice report",
                "purchase invoices report",
                "supplier invoice report",
                "vendor invoice report"
            ]
        },
        {
            url: "purchase/po_voucher_report_pdf",
            label: "PO Voucher Report",
            phrases: [
                "po voucher report",
                "purchase order voucher report",
                "purchase order voucher",
                "po voucher"
            ]
        },
        {
            url: "purchase/import_goods_report_pdf",
            label: "Import Goods Report",
            phrases: ["import goods report", "imported goods report", "import goods"]
        },
        {
            // Deliberately last of the purchase reports: "purchase report" is a
            // substring of the more specific ones above.
            url: "purchase/purchase_report_pdf",
            label: "Purchase Report",
            phrases: [
                "purchase order report",
                "purchase report",
                "po report",
                "purchase orders report"
            ]
        }
    ];

    // Words people put in front of a command that carry no meaning for matching.
    var LEADING_FILLER = /^(?:please\s+|can\s+you\s+|could\s+you\s+|kiro\s+|hey\s+)+/;
    var ACTION_WORDS = /^(?:open|show|show\s+me|get|give\s+me|download|print|generate|display|view|fetch|run|pull\s+up|bring\s+up|go\s+to|goto|take\s+me\s+to|navigate\s+to)\s+/;

    var recognition = null;
    var listening = false;
    // Set when the user asks to stop, so onend does not report "nothing was heard".
    var cancelled = false;

    /* ------------------------------------------------------------------ helpers */

    function notify(type, message) {
        if (typeof window.alert_float === "function") {
            window.alert_float(type, message);
        } else if (type === "danger" || type === "warning") {
            window.alert(message);
        }
    }

    function adminUrl(path) {
        // admin_url is emitted as a global by application/helpers/admin_helper.php and
        // already carries a trailing slash.
        var base = typeof window.admin_url === "string" ? window.admin_url : "/";
        return base + path;
    }

    function normalise(text) {
        return String(text || "")
            .toLowerCase()
            .replace(/[.,!?;:]+$/g, "")
            .replace(/\s+/g, " ")
            .trim();
    }

    function stripPrefixes(text) {
        var out = text.replace(LEADING_FILLER, "").trim();
        // Only one action word is removed. "show me the purchase report" -> after the
        // action word the article is dropped below.
        out = out.replace(ACTION_WORDS, "").trim();
        out = out.replace(/^(?:the|a|an|my)\s+/, "").trim();
        return out;
    }

    function pad(n) {
        return (n < 10 ? "0" : "") + n;
    }

    function ymd(date) {
        return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
    }

    /**
     * Pull a date range out of the phrase.
     *
     * Returns { from, to, label, rest } where rest is the phrase with the range
     * wording removed, so command matching is not confused by it. When no range is
     * mentioned, from/to are null and the endpoint applies its own default of
     * 1 January to today.
     */
    function extractRange(phrase) {
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth();

        var ranges = [
            {
                re: /\b(?:for\s+)?today\b/,
                build: function () {
                    return { from: ymd(now), to: ymd(now), label: "today" };
                }
            },
            {
                re: /\b(?:for\s+)?yesterday\b/,
                build: function () {
                    var d = new Date(y, m, now.getDate() - 1);
                    return { from: ymd(d), to: ymd(d), label: "yesterday" };
                }
            },
            {
                re: /\b(?:for\s+)?this\s+month\b/,
                build: function () {
                    return {
                        from: ymd(new Date(y, m, 1)),
                        to: ymd(new Date(y, m + 1, 0)),
                        label: "this month"
                    };
                }
            },
            {
                re: /\b(?:for\s+)?last\s+month\b/,
                build: function () {
                    return {
                        from: ymd(new Date(y, m - 1, 1)),
                        to: ymd(new Date(y, m, 0)),
                        label: "last month"
                    };
                }
            },
            {
                re: /\b(?:for\s+)?this\s+quarter\b/,
                build: function () {
                    var qs = Math.floor(m / 3) * 3;
                    return {
                        from: ymd(new Date(y, qs, 1)),
                        to: ymd(new Date(y, qs + 3, 0)),
                        label: "this quarter"
                    };
                }
            },
            {
                re: /\b(?:for\s+)?last\s+quarter\b/,
                build: function () {
                    var qs = Math.floor(m / 3) * 3 - 3;
                    return {
                        from: ymd(new Date(y, qs, 1)),
                        to: ymd(new Date(y, qs + 3, 0)),
                        label: "last quarter"
                    };
                }
            },
            {
                re: /\b(?:for\s+)?(?:this\s+year|year\s+to\s+date|ytd)\b/,
                build: function () {
                    return { from: ymd(new Date(y, 0, 1)), to: ymd(now), label: "this year" };
                }
            },
            {
                re: /\b(?:for\s+)?last\s+year\b/,
                build: function () {
                    return {
                        from: ymd(new Date(y - 1, 0, 1)),
                        to: ymd(new Date(y - 1, 11, 31)),
                        label: "last year"
                    };
                }
            },
            {
                re: /\b(?:for\s+)?last\s+(\d{1,3})\s+days?\b/,
                build: function (match) {
                    var days = parseInt(match[1], 10);
                    var d = new Date(y, m, now.getDate() - days);
                    return { from: ymd(d), to: ymd(now), label: "the last " + days + " days" };
                }
            },
            {
                // A spoken year: "for 2025"
                re: /\b(?:for\s+|in\s+)?((?:19|20)\d{2})\b/,
                build: function (match) {
                    var yr = parseInt(match[1], 10);
                    return {
                        from: yr + "-01-01",
                        to: yr + "-12-31",
                        label: String(yr)
                    };
                }
            }
        ];

        for (var i = 0; i < ranges.length; i++) {
            var match = phrase.match(ranges[i].re);
            if (match) {
                var built = ranges[i].build(match);
                built.rest = phrase.replace(ranges[i].re, " ").replace(/\s+/g, " ").trim();
                return built;
            }
        }

        return { from: null, to: null, label: null, rest: phrase };
    }

    /* ----------------------------------------------------------------- sidebar */

    /**
     * Navigable targets taken from the rendered menu.
     *
     * Read from the DOM on purpose rather than kept as a list of routes here: the
     * sidebar only contains what this user is allowed to see, so a command can never
     * send someone to a page they will be denied, and new menu items work with no
     * change to this file.
     */
    function menuTargets() {
        var targets = [];
        var seen = {};

        $("#side-menu a[href], #setup-menu-wrapper a[href]").each(function () {
            var $a = $(this);
            var href = $a.attr("href");

            if (!href || href === "#" || href.indexOf("javascript:") === 0) {
                return;
            }

            // The label is the link's own text, without any counter badge.
            var $clone = $a.clone();
            $clone.find(".badge, .menu-item-badge, span.pull-right").remove();
            var text = normalise($clone.text());

            if (text === "" || seen[text]) {
                return;
            }

            seen[text] = true;
            targets.push({ label: $a.text().trim().replace(/\s+/g, " "), match: text, href: href });
        });

        return targets;
    }

    function findMenuTarget(phrase) {
        var targets = menuTargets();
        var i;

        for (i = 0; i < targets.length; i++) {
            if (targets[i].match === phrase) {
                return targets[i];
            }
        }

        // Longest label that the phrase starts with, so "invoices" does not beat
        // "recurring invoices" when the latter was actually said.
        var best = null;
        for (i = 0; i < targets.length; i++) {
            if (
                phrase.indexOf(targets[i].match) === 0 ||
                targets[i].match.indexOf(phrase) === 0
            ) {
                if (!best || targets[i].match.length > best.match.length) {
                    best = targets[i];
                }
            }
        }

        return best;
    }

    /* -------------------------------------------------------------------- help */

    function helpRows() {
        var reportRows = REPORTS.map(function (r) {
            return { say: r.phrases[0], does: r.label + " (PDF)" };
        });

        return [{ heading: "Reports" }]
            .concat(reportRows)
            .concat([
                { heading: "Date range - add to any report" },
                { say: "\u2026 this month", does: "Current calendar month" },
                { say: "\u2026 last month", does: "Previous calendar month" },
                { say: "\u2026 this quarter / last quarter", does: "Calendar quarter" },
                { say: "\u2026 this year / last year", does: "Calendar year" },
                { say: "\u2026 last 30 days", does: "Rolling window" },
                { say: "\u2026 for 2025", does: "A specific year" },
                { heading: "Navigation" },
                { say: "go to customers", does: "Any item in the sidebar, by its name" },
                { say: "open invoices", does: "Same, using \u201Copen\u201D" },
                { heading: "Search" },
                { say: "search for acme", does: "Runs the header search" },
                { say: "find invoice 123", does: "Same as search" },
                { heading: "Control" },
                { say: "help", does: "Show this list" },
                { say: "stop", does: "Stop listening" },
                { say: "log out", does: "Sign out" }
            ]);
    }

    function showHelp() {
        var rows = helpRows()
            .map(function (row) {
                if (row.heading) {
                    return (
                        '<tr><td colspan="2" class="bold" style="background:#f7f7f7;">' +
                        row.heading +
                        "</td></tr>"
                    );
                }
                return (
                    '<tr><td style="width:45%;"><code>' +
                    row.say +
                    "</code></td><td>" +
                    row.does +
                    "</td></tr>"
                );
            })
            .join("");

        var $modal = $("#voice-command-help");

        if ($modal.length === 0) {
            $modal = $(
                '<div class="modal fade" id="voice-command-help" tabindex="-1" role="dialog" aria-labelledby="voice-command-help-title">' +
                    '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    '<h4 class="modal-title" id="voice-command-help-title"><i class="fa fa-microphone"></i> Voice commands</h4>' +
                    "</div>" +
                    '<div class="modal-body">' +
                    '<p class="text-muted">Click the microphone, then say one of these. Words like &ldquo;show me&rdquo; or &ldquo;please&rdquo; in front are ignored.</p>' +
                    '<table class="table table-condensed" id="voice-command-help-rows"></table>' +
                    "</div>" +
                    '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>' +
                    "</div></div></div>"
            );
            $("body").append($modal);
        }

        $modal.find("#voice-command-help-rows").html(rows);
        $modal.modal("show");
    }

    /* ------------------------------------------------------------------- state */

    function setIcon(state) {
        var $icon = $("#voice-mic-icon");
        var $btn = $("#voice-cmd-btn");

        if ($icon.length === 0) {
            return;
        }

        $icon.removeClass("fa-microphone fa-microphone-slash tw-text-neutral-500 text-danger text-success");

        if (state === "listening") {
            $icon.addClass("fa-microphone text-danger");
            $btn.attr("aria-pressed", "true");
        } else {
            $icon.addClass("fa-microphone tw-text-neutral-500");
            $btn.attr("aria-pressed", "false");
        }
    }

    function showStatus(text) {
        var $panel = $("#voice-command-status");

        if ($panel.length === 0) {
            $panel = $(
                '<div id="voice-command-status" role="status" aria-live="polite" ' +
                    'style="position:fixed;z-index:99999;bottom:20px;left:50%;transform:translateX(-50%);' +
                    "max-width:90vw;padding:10px 18px;border-radius:20px;background:rgba(33,33,33,.92);" +
                    'color:#fff;font-size:13px;box-shadow:0 2px 10px rgba(0,0,0,.3);display:none;"></div>'
            );
            $("body").append($panel);
        }

        $panel.text(text).stop(true, true).fadeIn(120);
    }

    function hideStatus(delay) {
        var $panel = $("#voice-command-status");
        if ($panel.length) {
            $panel.delay(delay || 0).fadeOut(200);
        }
    }

    /* ---------------------------------------------------------------- dispatch */

    function openReport(report, range) {
        var url = adminUrl(report.url);

        if (range.from && range.to) {
            url += "?from_date=" + encodeURIComponent(range.from) + "&to_date=" + encodeURIComponent(range.to);
        }

        notify(
            "success",
            STR.opening + report.label + (range.label ? " \u2013 " + range.label : "")
        );

        // New tab: the endpoint streams the PDF inline, and this keeps the user's
        // current screen (possibly a half-filled form) intact.
        var win = window.open(url, "_blank");

        if (!win) {
            // Popup blocked - fall back to the current tab rather than doing nothing.
            window.location.href = url;
        }
    }

    function runSearch(term) {
        var $input = $("#search_input");

        if ($input.length === 0) {
            notify("warning", STR.noSearchBox);
            return;
        }

        notify("success", STR.searching + '"' + term + '"');
        $input.val(term).trigger("focus");
        // The header search is bound to keyup in assets/js/main.js.
        $input.trigger($.Event("keyup", { which: 32 }));
    }

    /**
     * Match a transcript to an action and perform it.
     *
     * @return {boolean} true when something was recognised and acted on
     */
    function dispatch(transcript) {
        var raw = normalise(transcript);

        if (raw === "") {
            return false;
        }

        // Control commands are checked before anything is stripped, so "stop" cannot
        // be mistaken for a menu item.
        if (/^(?:help|what can (?:i|you) (?:say|do)|commands|show help)$/.test(raw)) {
            showHelp();
            return true;
        }

        if (/^(?:stop|cancel|never mind|nevermind|quiet|stop listening)$/.test(raw)) {
            cancelled = true;
            stopListening();
            notify("info", STR.stopped);
            return true;
        }

        if (/^(?:log ?out|sign ?out|log me out)$/.test(raw)) {
            window.location.href = adminUrl("authentication/logout");
            return true;
        }

        // Search, before prefix stripping so the term keeps its own wording.
        var search = raw.match(
            /^(?:search|search for|find|look for|look up|lookup)\s+(.+)$/
        );
        if (search) {
            runSearch(search[1].trim());
            return true;
        }

        var phrase = stripPrefixes(raw);
        var range = extractRange(phrase);
        var target = range.rest;

        // Reports. Longest phrase first so specific beats general.
        var candidates = [];
        REPORTS.forEach(function (report) {
            report.phrases.forEach(function (p) {
                if (target.indexOf(p) !== -1) {
                    candidates.push({ report: report, length: p.length });
                }
            });
        });

        if (candidates.length) {
            candidates.sort(function (a, b) {
                return b.length - a.length;
            });
            openReport(candidates[0].report, range);
            return true;
        }

        // Sidebar navigation. Uses the phrase without the date range removed, since
        // menu labels never contain one.
        var menuTarget = findMenuTarget(phrase);
        if (menuTarget) {
            notify("success", STR.opening + menuTarget.label);
            window.location.href = menuTarget.href;
            return true;
        }

        return false;
    }

    /* -------------------------------------------------------------- recognition */

    function speechApi() {
        return window.SpeechRecognition || window.webkitSpeechRecognition || null;
    }

    function secureEnough() {
        // isSecureContext covers HTTPS and localhost. Older browsers without it fall
        // back to an explicit check.
        if (typeof window.isSecureContext === "boolean") {
            return window.isSecureContext;
        }
        return (
            window.location.protocol === "https:" ||
            window.location.hostname === "localhost" ||
            window.location.hostname === "127.0.0.1"
        );
    }

    function build() {
        var Api = speechApi();
        var r = new Api();

        r.lang = $("html").attr("lang") || "en-US";
        // One command per click. Continuous listening would keep the microphone open
        // and pick up ordinary conversation.
        r.continuous = false;
        r.interimResults = false;
        r.maxAlternatives = 3;

        r.onstart = function () {
            listening = true;
            cancelled = false;
            setIcon("listening");
            showStatus(STR.listening);
        };

        r.onresult = function (event) {
            var results = event.results && event.results[0];
            if (!results) {
                return;
            }

            // Try each alternative, best first. Speech recognition often mishears a
            // domain word in the top result but gets it right in the second.
            var handled = false;
            var first = normalise(results[0].transcript);

            for (var i = 0; i < results.length; i++) {
                if (dispatch(results[i].transcript)) {
                    handled = true;
                    break;
                }
            }

            if (!handled) {
                showStatus(STR.heard + "\u201C" + first + "\u201D");
                notify("warning", STR.heard + '"' + first + '". ' + STR.unknown);
                hideStatus(2500);
                return;
            }

            hideStatus(600);
        };

        r.onerror = function (event) {
            listening = false;
            setIcon("idle");
            hideStatus(0);

            switch (event.error) {
                case "not-allowed":
                case "service-not-allowed":
                    notify("danger", STR.denied);
                    break;
                case "no-speech":
                    if (!cancelled) {
                        notify("warning", STR.noSpeech);
                    }
                    break;
                case "audio-capture":
                    notify("danger", STR.noMic);
                    break;
                case "aborted":
                    // User-initiated stop, or a navigation. Nothing to report.
                    break;
                default:
                    notify("danger", STR.genericError + event.error);
            }
        };

        r.onend = function () {
            listening = false;
            setIcon("idle");
            hideStatus(400);
        };

        return r;
    }

    function startListening() {
        if (!speechApi()) {
            notify("danger", STR.unsupported);
            return;
        }

        if (!secureEnough()) {
            notify("danger", STR.insecure);
            return;
        }

        if (!recognition) {
            recognition = build();
        }

        try {
            recognition.start();
        } catch (e) {
            // start() throws InvalidStateError when already running. Treat a click in
            // that state as a request to stop.
            stopListening();
        }
    }

    function stopListening() {
        if (recognition && listening) {
            try {
                recognition.stop();
            } catch (e) {
                /* already stopped */
            }
        }
        listening = false;
        setIcon("idle");
        hideStatus(0);
    }

    /* ------------------------------------------------------------------ exports */

    window.toggleVoiceCommand = function () {
        if (listening) {
            cancelled = true;
            stopListening();
            return false;
        }
        startListening();
        return false;
    };

    // Useful on its own, and referenced by the help modal.
    window.voiceCommandHelp = showHelp;

    $(function () {
        setIcon("idle");

        // Hide the button outright where the API can never work, rather than leaving a
        // control that always errors.
        if (!speechApi() || !secureEnough()) {
            var $btn = $("#voice-cmd-btn");
            $btn.attr(
                "title",
                !speechApi() ? STR.unsupported : STR.insecure
            );
            $btn.closest("li").attr("data-original-title", $btn.attr("title"));
            $("#voice-mic-icon").addClass("tw-opacity-40");
        }
    });
})(window, window.jQuery);
