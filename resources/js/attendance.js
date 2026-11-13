import Alpine from 'alpinejs';

/**
 * Attendance Tracking page state (docs/attendance-tracking.md).
 *
 * One component drives BOTH the calendar and the list view so ticks survive
 * toggling between them. Dates are plain 'YYYY-MM-DD' strings throughout
 * (never Date objects in state) — that sidesteps browser-timezone bugs, and
 * lexical comparison of such strings is also chronological comparison.
 *
 * "today" and "maxDate" come from the SERVER (Singapore time), not the
 * browser clock. All Tailwind classes live in the Blade views (Tailwind does
 * not scan resources/js), this file only decides STATES and numbers.
 *
 * Day states: null (not attended) | 'attended' (editable) | 'billed' (locked)
 * | 'paid' (locked).
 */
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const MON_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const DAY_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MAX_LIST_DAYS = 92;

const pad = (n) => String(n).padStart(2, '0');
const ymd = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`; // m is 0-based
const fromDate = (dt) => ymd(dt.getFullYear(), dt.getMonth(), dt.getDate());
const parts = (s) => {
    const [y, m, d] = s.split('-').map(Number);
    return { y, m: m - 1, d };
};
const daysIn = (y, m) => new Date(y, m + 1, 0).getDate();
const isDate = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);
const addDays = (s, n) => {
    const { y, m, d } = parts(s);
    return fromDate(new Date(y, m, d + n));
};
const daysBetween = (a, b) => {
    const A = parts(a);
    const B = parts(b);
    return Math.round((Date.UTC(B.y, B.m, B.d) - Date.UTC(A.y, A.m, A.d)) / 86400000);
};

Alpine.data('attendancePage', (cfg) => {
    const start = parts(/^\d{4}-\d{2}$/.test(cfg.month || '') ? `${cfg.month}-01` : `${cfg.today.slice(0, 7)}-01`);
    const defaultFrom = ymd(start.y, start.m, 1);
    const defaultTo = ymd(start.y, start.m, daysIn(start.y, start.m));
    const hasFilter = isDate(cfg.from) && isDate(cfg.to) && cfg.from <= cfg.to;
    const initialFilter = hasFilter
        ? (cfg.from === cfg.to ? { mode: 'date', date: cfg.from, from: cfg.from, to: cfg.to } : { mode: 'range', date: '', from: cfg.from, to: cfg.to })
        : { mode: 'range', date: '', from: defaultFrom, to: defaultTo };

    return {
        // ---- config / server truth -------------------------------------
        readonly: !!cfg.readonly,
        today: cfg.today,
        maxDate: cfg.maxDate,
        studentId: cfg.studentId,
        saved: Object.fromEntries((cfg.rows || []).map((r) => [r.date, r.status])),

        // ---- pending (unsaved) edits -----------------------------------
        added: {},   // date -> true  (newly ticked)
        removed: {}, // date -> true  (saved-as-attended, now unticked)

        // ---- view state --------------------------------------------------
        view: cfg.view === 'list' ? 'list' : 'calendar',
        cy: start.y,
        cm: start.m,
        weekdays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        applied: initialFilter,           // the filter the list is showing
        draftFilter: { ...initialFilter }, // what the popover edits
        filterOpen: false,
        filterError: '',

        // ---- spinner + pop-ups --------------------------------------------
        busy: false,
        stage: '',
        submitting: false,
        saveWarnOpen: false,
        billBlockOpen: false,
        flagFuture: false,
        popupDates: [],

        // ---- draft recovery (419/500 bounce) -------------------------------
        draft: null,

        init() {
            if (this.readonly) return;

            window.addEventListener('beforeunload', (e) => {
                if (this.hasPending && !this.submitting) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Draft recovery. A successful save/billing clears it; otherwise offer to restore.
            const key = this.draftKey;
            try {
                if (cfg.justSaved) {
                    sessionStorage.removeItem(key);
                } else {
                    const raw = sessionStorage.getItem(key);
                    if (raw) {
                        const d = JSON.parse(raw);
                        const added = (d.added || []).filter((x) => isDate(x) && !this.saved[x]);
                        const removed = (d.removed || []).filter((x) => isDate(x) && this.saved[x] === 'attended');
                        if (added.length || removed.length) {
                            this.draft = { added, removed };
                        } else {
                            sessionStorage.removeItem(key);
                        }
                    }
                }
            } catch (e) { /* storage unavailable (private mode etc.) — page works without it */ }
        },

        get draftKey() { return `attendance-draft-${this.studentId}`; },

        restoreDraft() {
            if (!this.draft) return;
            this.draft.added.forEach((d) => { this.added[d] = true; });
            this.draft.removed.forEach((d) => { this.removed[d] = true; });
            this.draft = null;
        },

        discardDraft() {
            this.draft = null;
            try { sessionStorage.removeItem(this.draftKey); } catch (e) { /* ignore */ }
        },

        // ---- date helpers exposed to Blade --------------------------------
        fmtRow(d) {
            const { y, m, d: day } = parts(d);
            return `${day} ${MON_SHORT[m]} ${y}, ${DAY_SHORT[new Date(y, m, day).getDay()]}`;
        },
        fmtFull(d) {
            const { y, m, d: day } = parts(d);
            return `${day} ${MONTHS[m]} ${y}`;
        },
        get monthLabel() { return `${MONTHS[this.cm]} ${this.cy}`; },
        get monthKey() { return `${this.cy}-${pad(this.cm + 1)}`; },

        // ---- day state ------------------------------------------------------
        st(d) {
            const s = this.saved[d];
            if (s === 'billed' || s === 'paid') return s;
            if (this.removed[d]) return null;
            if (this.added[d]) return 'attended';
            return s || null;
        },
        isLocked(d) { const s = this.saved[d]; return s === 'billed' || s === 'paid'; },
        isFuture(d) { return d > this.today; },
        isTooFar(d) { return d > this.maxDate; },
        canToggle(d) { return !this.readonly && !this.isLocked(d) && !this.isTooFar(d); },
        flagged(d) { return this.flagFuture && this.isFuture(d) && this.st(d) === 'attended'; },

        toggle(d) {
            if (!this.canToggle(d)) return;
            if (this.st(d) === 'attended') {
                if (this.saved[d] === 'attended') this.removed[d] = true; else delete this.added[d];
            } else if (this.saved[d] === 'attended') {
                delete this.removed[d];
            } else {
                this.added[d] = true;
            }
            if (this.futureTicked().length === 0) this.flagFuture = false;
        },

        // ---- calendar --------------------------------------------------------
        get grid() {
            const first = new Date(this.cy, this.cm, 1).getDay();
            const total = Math.ceil((first + daysIn(this.cy, this.cm)) / 7) * 7;
            const cells = [];
            for (let i = 0; i < total; i++) {
                const dt = new Date(this.cy, this.cm, 1 - first + i);
                cells.push({ date: fromDate(dt), day: dt.getDate(), outside: dt.getMonth() !== this.cm });
            }
            return cells;
        },
        get canPrev() { return !(this.cy <= 2000 && this.cm === 0); },
        get canNext() { return this.monthKey < this.maxDate.slice(0, 7); },
        prevMonth() { if (!this.canPrev) return; if (this.cm === 0) { this.cm = 11; this.cy--; } else { this.cm--; } },
        nextMonth() { if (!this.canNext) return; if (this.cm === 11) { this.cm = 0; this.cy++; } else { this.cm++; } },

        // ---- list + filter ---------------------------------------------------
        get listRange() {
            const f = this.applied;
            return f.mode === 'date' ? [f.date, f.date] : [f.from, f.to];
        },
        get listDates() {
            const [a, b] = this.listRange;
            const out = [];
            for (let d = b, n = 0; d >= a && n < MAX_LIST_DAYS; d = addDays(d, -1), n++) out.push(d);
            return out; // newest first
        },
        get filterLabel() {
            const f = this.applied;
            return f.mode === 'date' ? this.fmtFull(f.date) : `${this.fmtFull(f.from)} – ${this.fmtFull(f.to)}`;
        },
        openFilter() {
            this.draftFilter = { ...this.applied };
            this.filterError = '';
            this.filterOpen = !this.filterOpen;
        },
        applyFilter() {
            const f = this.draftFilter;
            if (f.mode === 'date') {
                if (!isDate(f.date)) { this.filterError = 'Pick a date.'; return; }
                this.applied = { mode: 'date', date: f.date, from: f.date, to: f.date };
            } else {
                if (!isDate(f.from) || !isDate(f.to)) { this.filterError = 'Pick both a start and an end date.'; return; }
                if (f.from > f.to) { this.filterError = 'The start date must be on or before the end date.'; return; }
                if (daysBetween(f.from, f.to) + 1 > MAX_LIST_DAYS) { this.filterError = `Please pick a range of ${MAX_LIST_DAYS} days or fewer.`; return; }
                this.applied = { mode: 'range', date: '', from: f.from, to: f.to };
            }
            this.filterError = '';
            this.filterOpen = false;
        },
        clearFilter() {
            this.applied = { mode: 'range', date: '', from: defaultFrom, to: defaultTo };
            this.draftFilter = { ...this.applied };
            this.filterError = '';
            this.filterOpen = false;
        },

        // ---- counts (always scoped to what the current view shows) ----------------
        get windowRange() {
            if (this.view === 'calendar') {
                return [ymd(this.cy, this.cm, 1), ymd(this.cy, this.cm, daysIn(this.cy, this.cm))];
            }
            return this.listRange;
        },
        get knownDates() {
            return [...new Set([...Object.keys(this.saved), ...Object.keys(this.added)])];
        },
        datesWith(status, inWindowOnly = true) {
            const [a, b] = this.windowRange;
            return this.knownDates
                .filter((d) => this.st(d) === status && (!inWindowOnly || (d >= a && d <= b)))
                .sort();
        },
        get count() { return this.datesWith('attended').length; },
        get billedCount() { return this.datesWith('billed').length; },
        get paidCount() { return this.datesWith('paid').length; },
        get hasPending() { return Object.keys(this.added).length + Object.keys(this.removed).length > 0; },
        get pendingCount() { return Object.keys(this.added).length + Object.keys(this.removed).length; },
        get addedList() { return Object.keys(this.added).sort(); },
        get removedList() { return Object.keys(this.removed).sort(); },

        /** Ticked dates later than today. `inWindow` = only the dates the current view shows. */
        futureTicked(inWindow = false) {
            return this.datesWith('attended', inWindow).filter((d) => this.isFuture(d));
        },

        // ---- actions ------------------------------------------------------------
        requestSave() {
            if (!this.hasPending || this.busy) return;
            const future = this.futureTicked(false);
            if (future.length) {
                this.popupDates = future;
                this.flagFuture = true;
                this.saveWarnOpen = true;
                return;
            }
            this.doSave();
        },
        confirmSave() {
            this.saveWarnOpen = false;
            this.doSave();
        },
        doSave() {
            this.beginSubmit('Saving current attendance…');
            this.$nextTick(() => this.$refs.saveForm.submit());
        },

        requestBilling() {
            if (this.count === 0 || this.busy) return;
            const future = this.futureTicked(true);
            if (future.length) {
                this.popupDates = future;
                this.flagFuture = true;
                this.billBlockOpen = true;
                return;
            }
            this.beginSubmit('Adding to bill…');
            this.$nextTick(() => this.$refs.billForm.submit());
        },

        beginSubmit(message) {
            this.submitting = true; // silences the beforeunload prompt
            this.stage = message;
            this.busy = true;
            try {
                sessionStorage.setItem(this.draftKey, JSON.stringify({ added: this.addedList, removed: this.removedList }));
            } catch (e) { /* ignore */ }
        },

        // Hidden-form values (window = the dates "Add to Billing" considers).
        get windowFrom() { return this.windowRange[0]; },
        get windowTo() { return this.windowRange[1]; },
        // Only echo the list filter back to the server when it isn't the default whole-month one.
        get filterIsDefault() { return this.applied.mode === 'range' && this.applied.from === defaultFrom && this.applied.to === defaultTo; },
        get fromField() { return this.filterIsDefault ? '' : this.applied.from; },
        get toField() { return this.filterIsDefault ? '' : this.applied.to; },
    };
});
