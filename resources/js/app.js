import './bootstrap';
import flatpickr from 'flatpickr';
import { Czech } from 'flatpickr/dist/l10n/cs.js';

// Range calendar: one click opens it, pick start + end with the range highlighted.
// Writes the two dates into sibling hidden inputs [name=start_date] / [name=end_date]
// and fires their `change` event so existing handlers (cart, product detail) refresh.
// "Jump to month" select above the calendar, so a wedding a year ahead is one
// pick away instead of a dozen arrow clicks. Covers the next 24 months.
function addMonthJump(fp) {
    if (fp.calendarContainer.querySelector('.fp-jump')) return;
    const select = document.createElement('select');
    select.className = 'fp-jump';
    select.setAttribute('aria-label', 'Přejít na měsíc');
    const now = new Date();
    for (let i = 0; i < 24; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
        const opt = document.createElement('option');
        opt.value = `${d.getFullYear()}-${d.getMonth()}`;
        opt.textContent = `${fp.l10n.months.longhand[d.getMonth()]} ${d.getFullYear()}`;
        select.appendChild(opt);
    }
    select.addEventListener('change', () => {
        const [y, m] = select.value.split('-').map(Number);
        fp.changeYear(y);
        fp.changeMonth(m, false);
    });
    const sync = () => { select.value = `${fp.currentYear}-${fp.currentMonth}`; };
    fp.config.onMonthChange.push(sync);
    fp.config.onYearChange.push(sync);
    fp.config.onOpen.push(sync);
    const wrap = document.createElement('div');
    wrap.className = 'fp-jump-wrap';
    const label = document.createElement('span');
    label.textContent = 'Přejít na';
    wrap.append(label, select);
    fp.calendarContainer.prepend(wrap);
    sync();
}

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
            onReady(_d, _s, fp) { addMonthJump(fp); },
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
