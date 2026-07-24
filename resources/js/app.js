import './bootstrap';
import flatpickr from 'flatpickr';
import { Czech } from 'flatpickr/dist/l10n/cs.js';

// Range calendar: one click opens it, pick start + end with the range highlighted.
// Writes the two dates into sibling hidden inputs [name=start_date] / [name=end_date]
// and fires their `change` event so existing handlers (cart, product detail) refresh.
function initRangePickers() {
    document.querySelectorAll('[data-rangepicker]').forEach((el) => {
        if (el._flatpickr) return;
        const scope = el.closest('[data-term-scope]') || el.closest('form') || document;
        const startHidden = scope.querySelector('input[name="start_date"]');
        const endHidden = scope.querySelector('input[name="end_date"]');

        // Hidden inputs store Y-m-d; build real Date objects so Flatpickr (which
        // displays d.m.Y) reflects an already-selected term instead of the placeholder.
        const toDate = (s) => {
            const p = String(s).split('-').map(Number);
            return p.length === 3 ? new Date(p[0], p[1] - 1, p[2]) : null;
        };
        const initial = [];
        if (startHidden && startHidden.value) { const d = toDate(startHidden.value); if (d) initial.push(d); }
        if (endHidden && endHidden.value) { const d = toDate(endHidden.value); if (d) initial.push(d); }

        flatpickr(el, {
            mode: 'range',
            locale: Czech,
            dateFormat: 'd.m.Y',
            minDate: 'today',
            showMonths: window.matchMedia('(min-width: 700px)').matches ? 2 : 1,
            defaultDate: initial.length === 2 ? initial : null,
            onChange(dates, _str, fp) {
                if (dates.length !== 2) return;
                const s = fp.formatDate(dates[0], 'Y-m-d');
                const e = fp.formatDate(dates[1], 'Y-m-d');
                if (startHidden) startHidden.value = s;
                if (endHidden) endHidden.value = e;
                if (startHidden) startHidden.dispatchEvent(new Event('change', { bubbles: true }));
                if (endHidden) endHidden.dispatchEvent(new Event('change', { bubbles: true }));
            },
        });
    });
}

function boot() { initRangePickers(); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
window.initRangePickers = initRangePickers;
