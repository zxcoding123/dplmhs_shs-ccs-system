/* =========================================================
   Admin UI layer — behaviour shared by every page in admin/.

   Load at the end of <body>, after jQuery/DataTables when the page uses
   them. Everything is progressive: pages without jQuery, DataTables or
   modals simply skip those parts.
   ========================================================= */
(function () {
    'use strict';

    var $ = window.jQuery;

    // ---------- Sidebar clock ----------
    function tickClock() {
        var clock = document.getElementById('currentTime');
        if (clock) {
            clock.textContent = new Date().toLocaleString('en-PH', {
                weekday: 'short', month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
        }
    }
    tickClock();
    setInterval(tickClock, 15000);

    // ---------- Helpers ----------
    function cellText(html) {
        var div = document.createElement('div');
        div.innerHTML = String(html == null ? '' : html).replace(/<br\s*\/?>/gi, ' ');
        div.querySelectorAll('button, .ui-acts, script').forEach(function (el) { el.remove(); });
        return (div.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function isActionsHeader(text) {
        return /^(actions?|manage)$/i.test(String(text).trim());
    }

    function slug(text) {
        return String(text).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'export';
    }

    function pageTitle() {
        var h1 = document.querySelector('.ui-page-title h1');
        return h1 ? h1.textContent.trim() : document.title;
    }

    function escapeRegex(text) {
        return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Visible columns (minus Actions) and the rows that match the current search
    function tableSnapshot(api) {
        var columns = [];
        api.columns().every(function (index) {
            var title = cellText(this.header().innerHTML);
            if (!isActionsHeader(title) && this.visible()) columns.push({ index: index, title: title });
        });
        var rows = [];
        api.rows({ search: 'applied', order: 'applied' }).every(function () {
            var node = this.node();
            var data = this.data();
            rows.push(columns.map(function (col) {
                if (node && node.cells && node.cells[col.index]) return cellText(node.cells[col.index].innerHTML);
                return cellText(Array.isArray(data) ? data[col.index] : data[col.index]);
            }));
        });
        return { columns: columns, rows: rows };
    }

    function exportCsv(api) {
        var snap = tableSnapshot(api);
        var quote = function (value) { return '"' + String(value).replace(/"/g, '""') + '"'; };
        var lines = [snap.columns.map(function (c) { return quote(c.title); }).join(',')];
        snap.rows.forEach(function (row) { lines.push(row.map(quote).join(',')); });

        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = slug(pageTitle()) + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        setTimeout(function () { URL.revokeObjectURL(link.href); link.remove(); }, 0);
    }

    function printTable(api) {
        var snap = tableSnapshot(api);
        var esc = function (value) {
            var div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        };
        var title = esc(pageTitle());
        var html = '<!doctype html><html><head><meta charset="utf-8"><title>' + title + '</title><style>' +
            'body{font-family:"Segoe UI",Arial,sans-serif;color:#1f2430;margin:24px}' +
            'h1{font-size:18px;margin:0 0 4px;color:#061E4E}p{margin:0 0 16px;color:#5d6475;font-size:12px}' +
            'table{width:100%;border-collapse:collapse;font-size:12px}' +
            'th{background:#061E4E;color:#fff;text-align:left;padding:6px 8px}' +
            'td{padding:6px 8px;border-bottom:1px solid #e6e1d3}tr:nth-child(even) td{background:#faf9f5}' +
            '</style></head><body><h1>DPLMHS-SHS · ' + title + '</h1><p>' + snap.rows.length +
            ' record(s) · printed ' + esc(new Date().toLocaleString()) + '</p><table><thead><tr>' +
            snap.columns.map(function (c) { return '<th>' + esc(c.title) + '</th>'; }).join('') +
            '</tr></thead><tbody>' +
            snap.rows.map(function (row) {
                return '<tr>' + row.map(function (v) { return '<td>' + esc(v) + '</td>'; }).join('') + '</tr>';
            }).join('') +
            '</tbody></table></body></html>';

        var win = window.open('', '_blank');
        if (!win) return;
        win.document.open();
        win.document.write(html);
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 250);
    }

    // ---------- DataTables ----------
    if ($ && $.fn && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            dom: '<"ui-dt-top"l<"ui-dt-tools"f>>rt<"ui-dt-bottom"ip>',
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            language: {
                search: '',
                searchPlaceholder: 'Search records…',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty: 'No records',
                infoFiltered: '(filtered from _MAX_)',
                zeroRecords: 'No records match your search.',
                emptyTable: 'Nothing here yet.',
                paginate: { previous: '‹', next: '›' }
            }
        });

        $(document).on('init.dt', function (e, settings) {
            var api = new $.fn.dataTable.Api(settings);
            var table = api.table().node();
            var wrapper = $(api.table().container());

            // Mark the Actions column so it centres and is left out of exports
            api.columns().every(function () {
                if (isActionsHeader(cellText(this.header().innerHTML))) {
                    $(this.header()).addClass('ui-col-actions');
                    $(this.nodes()).addClass('ui-col-actions');
                }
            });

            if (!$(table).parent().hasClass('ui-table-scroll')) {
                $(table).wrap('<div class="ui-table-scroll"></div>');
            }

            var tools = wrapper.find('.ui-dt-tools');
            if (!tools.length) {
                tools = $('<div class="ui-dt-tools"></div>');
                var filter = wrapper.find('.dataTables_filter');
                filter.length ? filter.after(tools) : wrapper.prepend(tools);
            }
            if (!tools.find('.ui-dt-export').length) {
                var csv = $('<button type="button" class="btn btn-sm btn-ui-ghost ui-dt-export"><i class="bi bi-filetype-csv"></i> Export</button>');
                var print = $('<button type="button" class="btn btn-sm btn-ui-ghost"><i class="bi bi-printer"></i> Print</button>');
                csv.on('click', function () { exportCsv(api); });
                print.on('click', function () { printTable(api); });
                tools.append(csv, print);
            }
        });

        // Re-mark Actions cells after paging/redraws
        $(document).on('draw.dt', function (e, settings) {
            var api = new $.fn.dataTable.Api(settings);
            api.columns().every(function () {
                if ($(this.header()).hasClass('ui-col-actions')) $(this.nodes()).addClass('ui-col-actions');
            });
        });
    }

    // ---------- Stat tiles that filter the page's table ----------
    document.addEventListener('click', function (e) {
        var tile = e.target.closest('[data-ui-filter]');
        if (!tile || !$ || !$.fn.dataTable) return;

        var tableEl = document.querySelector(tile.getAttribute('data-ui-table') || 'table.dataTable');
        if (!tableEl || !$.fn.dataTable.isDataTable(tableEl)) return;
        var api = $(tableEl).DataTable();

        var wasActive = tile.classList.contains('is-active');
        document.querySelectorAll('[data-ui-filter].is-active').forEach(function (t) { t.classList.remove('is-active'); });
        api.columns().search('');
        api.search('');

        var value = tile.getAttribute('data-ui-filter');
        var columnName = tile.getAttribute('data-ui-filter-col');
        if (!wasActive && value !== '') {
            tile.classList.add('is-active');
            if (columnName) {
                api.columns().every(function () {
                    if (cellText(this.header().innerHTML).toLowerCase() === columnName.toLowerCase()) {
                        this.search('^\\s*' + escapeRegex(value) + '\\s*$', true, false);
                    }
                });
            } else {
                api.search(value);
            }
        }
        api.draw();
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // ---------- Forms inside modals ----------
    function enhanceForms(root) {
        // Required markers
        root.querySelectorAll('.modal [required]').forEach(function (field) {
            if (field.type === 'hidden' || field.closest('[style*="display:none"], [style*="display: none"]')) return;
            var group = field.closest('.mb-3, .col-md-6, .col-6, .col');
            var label = (field.id && root.querySelector('label[for="' + CSS.escape(field.id) + '"]')) ||
                (group && group.querySelector('label.form-label, label.bold'));
            if (label && !label.querySelector('.ui-req')) {
                label.insertAdjacentHTML('beforeend', ' <span class="ui-req" aria-hidden="true">*</span>');
            }
        });

        // Show/hide toggle for password fields
        root.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.closest('.ui-pw')) return;
            var wrap = document.createElement('div');
            wrap.className = 'ui-pw';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ui-pw-toggle';
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = '<i class="bi ' + (show ? 'bi-eye-slash' : 'bi-eye') + '"></i>';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
            wrap.appendChild(btn);
        });
    }

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    ready(function () {
        enhanceForms(document);

        // Focus the first editable field when a modal opens
        document.addEventListener('shown.bs.modal', function (e) {
            var field = e.target.querySelector(
                '.modal-body input:not([type=hidden]):not([readonly]):not([disabled]):not([type=checkbox]):not([type=radio]):not([type=file]),' +
                '.modal-body select:not([disabled]):not([readonly]), .modal-body textarea:not([readonly]):not([disabled])'
            );
            if (field && field.offsetParent !== null) field.focus();
        });

        // "/" jumps to the table search box
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
            var tag = (document.activeElement && document.activeElement.tagName) || '';
            if (/input|textarea|select/i.test(tag) || document.activeElement.isContentEditable) return;
            if (document.querySelector('.modal.show')) return;
            var search = document.querySelector('.dataTables_filter input, [data-ui-search]');
            if (search) {
                e.preventDefault();
                search.focus();
            }
        });
    });

    // Prevent double submits: once a form really submits, lock its buttons.
    // Runs after the page's own handlers, so validation that cancels the
    // submit (preventDefault / return false) leaves the buttons alone.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || form.hasAttribute('data-ui-no-lock')) return;
        setTimeout(function () {
            form.querySelectorAll('button[type=submit], input[type=submit], button:not([type])').forEach(function (btn) {
                if (btn.disabled) return;
                btn.disabled = true;
                if (btn.tagName === 'BUTTON') {
                    btn.dataset.uiLabel = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Saving…';
                } else {
                    btn.dataset.uiLabel = btn.value;
                    btn.value = 'Saving…';
                }
            });
        }, 0);
    });

    // Buttons stay locked if the browser restores the page from its back/forward cache
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('[data-ui-label]').forEach(function (btn) {
            btn.disabled = false;
            if (btn.tagName === 'BUTTON') btn.innerHTML = btn.dataset.uiLabel;
            else btn.value = btn.dataset.uiLabel;
        });
    });

    window.AdminUI = { exportCsv: exportCsv, printTable: printTable, cellText: cellText };
})();
