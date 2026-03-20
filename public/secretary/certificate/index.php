<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Issuance — MIS Barangay</title>
    <?php loadAllAssets(); echo showDialogReloadScript(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
:root {
    --paper:      #fdfcf9;
    --ink:        #1a1a1a;
    --ink-muted:  #5a5a5a;
    --ink-faint:  #a0a0a0;
    --rule:       #d8d4cc;
    --rule-dark:  #b0aba0;
    --bg:         #edeae4;
    --accent:     var(--theme-primary, #2d5a27);
    --accent-lt:  color-mix(in srgb, var(--accent) 8%, white);
    --font-serif: 'Source Serif 4', Georgia, serif;
    --font-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
    --font-mono:  'Courier New', monospace;
    --card-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.05);
    --lift-shadow: 0 4px 20px rgba(0,0,0,.12), 0 1px 4px rgba(0,0,0,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body,input,button,select,textarea{font-family:var(--font-sans);}

/* shimmer */
@keyframes shimmer{0%{background-position:-600px 0}100%{background-position:600px 0}}
.skel{background:linear-gradient(90deg,#e8e4de 25%,#dedad4 50%,#e8e4de 75%);background-size:600px 100%;animation:shimmer 1.4s infinite;border-radius:3px;}

/* slide in */
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.su{animation:slideUp .26s ease both;}
.su1{animation-delay:.06s;}
.su2{animation-delay:.13s;}

/* page */
.cert-main{background:var(--bg);min-height:100%;}

/* dept header */
.dept-bar{
    background:var(--accent);
    padding:0 28px;
    display:flex;align-items:center;gap:0;
    border-bottom:3px solid color-mix(in srgb,var(--accent) 65%,black);
    min-height:68px;
}
.dept-seal-wrap{display:flex;align-items:center;gap:14px;padding:14px 24px 14px 0;border-right:1px solid rgba(255,255,255,.2);margin-right:24px;}
.dept-seal{width:42px;height:42px;border-radius:50%;border:2px solid rgba(255,255,255,.45);background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.dept-republic{font-size:8.5px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;opacity:.6;color:#fff;margin-bottom:3px;}
.dept-name{font-family:var(--font-serif);font-size:15px;font-weight:700;color:#fff;letter-spacing:.15px;line-height:1;}
.dept-sub{font-size:9.5px;opacity:.6;color:#fff;letter-spacing:.8px;text-transform:uppercase;margin-top:3px;}
.dept-bar-right{margin-left:auto;font-family:var(--font-mono);font-size:9.5px;color:rgba(255,255,255,.45);text-align:right;line-height:1.6;}

/* search bar */
.search-bar{background:var(--paper);border-bottom:1px solid var(--rule);padding:18px 28px;position:sticky;top:0;z-index:60;box-shadow:0 2px 10px rgba(0,0,0,.07);}
.search-eyebrow{font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:9px;display:flex;align-items:center;gap:8px;}
.search-eyebrow::before{content:'';display:inline-block;width:16px;height:2px;background:var(--accent);}
.search-wrap{position:relative;max-width:580px;}
.search-wrap input{width:100%;padding:11px 44px 11px 15px;border:1.5px solid var(--rule-dark);border-radius:3px;font-family:var(--font-sans);font-size:14px;color:var(--ink);background:#fff;outline:none;transition:border-color .18s,box-shadow .18s;}
.search-wrap input:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
.search-wrap input::placeholder{color:var(--ink-faint);font-style:italic;}
.s-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--ink-faint);font-size:17px;pointer-events:none;transition:color .18s;}
.search-wrap input:focus~.s-icon{color:var(--accent);}

/* dropdown */
.search-dd{position:absolute;top:calc(100% + 5px);left:0;right:0;background:#fff;border:1.5px solid var(--rule-dark);border-radius:3px;box-shadow:var(--lift-shadow);overflow:hidden;z-index:200;display:none;}
.search-dd.open{display:block;}
.dd-hdr{padding:7px 13px;background:#f5f3ee;border-bottom:1px solid var(--rule);font-size:8.5px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:var(--ink-muted);}
.dd-row{display:grid;grid-template-columns:44px 1fr 16px;align-items:center;gap:12px;padding:10px 13px;cursor:pointer;border-bottom:1px solid #f3f0eb;transition:background .1s;}
.dd-row:last-child{border-bottom:none;}
.dd-row:hover{background:var(--accent-lt);}
.dd-row:hover .dd-id{border-color:var(--accent);color:var(--accent);}
.dd-id{width:40px;height:38px;border:1.5px solid var(--rule-dark);border-radius:2px;display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:10px;font-weight:700;color:var(--ink-muted);flex-shrink:0;transition:all .12s;}
.dd-name{font-size:13px;font-weight:600;color:var(--ink);margin-bottom:2px;}
.dd-addr{font-size:11px;color:var(--ink-faint);}
.dd-arr{color:var(--ink-faint);font-size:13px;}
.dd-empty{padding:18px 13px;text-align:center;font-size:12.5px;color:var(--ink-faint);font-style:italic;}

/* body */
.cert-body{padding:24px 28px 56px;}

/* idle */
.idle-wrap{background:var(--paper);border:1.5px dashed var(--rule-dark);border-radius:3px;padding:80px 32px;text-align:center;}
.idle-doc{display:inline-block;width:50px;height:62px;border:2px solid var(--ink-faint);border-radius:2px;position:relative;margin-bottom:22px;opacity:.25;}
.idle-doc::before,.idle-doc::after{content:'';position:absolute;left:10px;right:10px;height:2px;background:var(--ink-faint);top:14px;opacity:.6;box-shadow:0 8px 0 rgba(0,0,0,.4),0 16px 0 rgba(0,0,0,.4);}
.idle-fold{position:absolute;top:0;right:0;width:14px;height:14px;background:var(--bg);border-left:2px solid var(--ink-faint);border-bottom:2px solid var(--ink-faint);}
.idle-title{font-family:var(--font-serif);font-size:17px;font-weight:600;color:var(--ink);margin-bottom:8px;}
.idle-rule{width:36px;height:2px;background:var(--accent);margin:10px auto 14px;}
.idle-sub{font-size:13px;color:var(--ink-faint);max-width:320px;margin:0 auto;line-height:1.75;}
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include '../layout/sidebar.php'; ?>
        <main class="flex-1 h-screen overflow-y-auto cert-main">

            <!-- Dept header -->
            <div class="dept-bar">
                <div class="dept-seal-wrap">
                    <div class="dept-seal">🏛️</div>
                    <div>
                        <div class="dept-republic">Republic of the Philippines · Province of Rizal</div>
                        <div class="dept-name">Barangay Bombongan, Morong</div>
                        <div class="dept-sub">Office of the Barangay Secretary</div>
                    </div>
                </div>
                <div class="dept-bar-right">
                    CERTIFICATE ISSUANCE SYSTEM<br><?= date('l, F j, Y') ?>
                </div>
            </div>

            <!-- Search bar -->
            <div class="search-bar">
                <div class="search-eyebrow">Resident Lookup</div>
                <div class="search-wrap">
                    <input id="residentSearch" type="text"
                        placeholder="Type a resident name or address to begin…"
                        autocomplete="off" spellcheck="false">
                    <span class="s-icon">⌕</span>
                    <div id="searchDD" class="search-dd">
                        <div class="dd-hdr">Search Results</div>
                        <div id="searchDDInner"></div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="cert-body">
                <div id="residentDetails">
                    <div class="idle-wrap">
                        <div class="idle-doc"><div class="idle-fold"></div></div>
                        <div class="idle-title">No Resident on Record</div>
                        <div class="idle-rule"></div>
                        <p class="idle-sub">Use the lookup field above to find a resident by name or address, then process their certificate request.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <div id="dialog-message" title="" style="display:none;"><p id="dialog-text"></p></div>

    <script>
    $(function () {
        $('body').show();

        const params = new URLSearchParams(window.location.search);
        if (params.has('id')) loadResident(params.get('id'));

        let timer;
        const $inp = $('#residentSearch'), $dd = $('#searchDD'), $ddi = $('#searchDDInner');

        $inp.on('input', function () {
            clearTimeout(timer);
            const q = $.trim($(this).val());
            if (q.length < 2) { $dd.removeClass('open'); $ddi.empty(); return; }
            timer = setTimeout(() => {
                $.getJSON('search_residents.php', { q }, data => {
                    $ddi.empty();
                    if (!data.length) {
                        $ddi.html('<div class="dd-empty">No residents match that query.</div>');
                        $dd.addClass('open'); return;
                    }
                    data.forEach(r => {
                        const num  = String(r.id).padStart(4,'0');
                        const name = [r.first_name, r.middle_name, r.last_name].filter(Boolean).join(' ');
                        $ddi.append(`<div class="dd-row" data-id="${r.id}">
                            <div class="dd-id">${esc(num)}</div>
                            <div>
                                <div class="dd-name">${esc(name)}</div>
                                <div class="dd-addr">📍 ${esc(r.address||'No address on file')}</div>
                            </div>
                            <div class="dd-arr">›</div>
                        </div>`);
                    });
                    $dd.addClass('open');
                });
            }, 260);
        });

        $(document).on('click', '.dd-row', function () {
            const id = $(this).data('id');
            $dd.removeClass('open'); $inp.val('');
            loadResident(id);
            history.pushState({}, '', '?id=' + id);
        });

        $(document).on('click', e => {
            if (!$(e.target).closest('#residentSearch,#searchDD').length)
                $dd.removeClass('open');
        });

        function loadResident(id) {
            $('#residentDetails').html(`
                <div class="skel su"  style="height:148px;margin-bottom:16px;"></div>
                <div class="skel su1" style="height:36px;width:50%;margin-bottom:18px;"></div>
                <div class="skel su2" style="height:400px;"></div>
            `);
            $.ajax({ url:'load_resident_details.php', method:'GET', data:{id},
                success: html => { $('#residentDetails').html(html); initUI(id); },
                error:   ()   => {
                    $('#residentDetails').html(`<div class="idle-wrap">
                        <div class="idle-title">⚠ Load Failed</div>
                        <div class="idle-rule"></div>
                        <p class="idle-sub">Could not retrieve resident data. Please try again.</p>
                    </div>`);
                }
            });
        }

        function initUI(rid) {
            $(document).off('click.ct').on('click.ct', '.cert-type-option', function () {
                $('.cert-type-option').removeClass('is-selected');
                $(this).addClass('is-selected');
                $('#selectedCertType').val($(this).data('type'));
            });
            $(document).off('submit.cf').on('submit.cf', '#certRequestForm', function (e) {
                e.preventDefault();
                const type    = $.trim($('#selectedCertType').val());
                const purpose = $.trim($('#certPurposeInput').val());
                if (!type)    { fmsg('Please select a certificate type.','error'); return; }
                if (!purpose) { fmsg('Please state the purpose.','error'); return; }
                const $btn = $('#certSubmitBtn').prop('disabled',true).text('Processing…');
                $.post('certificate_request_submit.php', {
                    resident_id: $('[name="resident_id"]').val(),
                    certificate_type: type, purpose
                }, res => {
                    if (res.status === 'success') {
                        fmsg('Request recorded successfully.','success');
                        setTimeout(() => loadResident(rid), 900);
                    } else {
                        fmsg(res.message||'Submission failed.','error');
                        $btn.prop('disabled',false).text('Submit Request');
                    }
                }, 'json').fail(() => {
                    fmsg('Server error. Please try again.','error');
                    $btn.prop('disabled',false).text('Submit Request');
                });
            });
        }

        function fmsg(msg, type) {
            const $m = $('#formMessage');
            $m.attr('data-type', type).text(msg).show();
            if (type==='success') setTimeout(()=>$m.fadeOut(),2800);
        }
        function esc(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

        window.printCertificate = id => window.open(`print.php?id=${id}`,'_blank','width=900,height=700,scrollbars=yes');
    });
    </script>
</body>
</html>