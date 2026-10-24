/* Payroll System - vanilla JS helpers (no framework). */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  // Icons
  if (window.lucide) lucide.createIcons();

  // Mobile sidebar
  const sidebar = $('#sidebar'), overlay = $('#overlay'), menuBtn = $('#menuBtn');
  function toggleMenu(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    overlay.classList.toggle('hidden', !open);
  }
  if (menuBtn) menuBtn.addEventListener('click', () => toggleMenu(sidebar.classList.contains('-translate-x-full')));
  if (overlay) overlay.addEventListener('click', () => toggleMenu(false));

  // Auto-dismiss success flashes
  $$('[data-flash]').forEach((el) => {
    if (el.className.includes('emerald')) setTimeout(() => el.remove(), 6000);
  });

  // Confirm dialogs:  <form data-confirm="Are you sure?">  or  <a data-confirm="...">
  document.addEventListener('submit', (e) => {
    const m = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (m && !confirm(m)) e.preventDefault();
  });
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-confirm]');
    if (a && !confirm(a.getAttribute('data-confirm'))) e.preventDefault();
    const p = e.target.closest('[data-print]');
    if (p) { e.preventDefault(); window.print(); }
  });

  // Live table filter:  <input data-filter="#tableId">
  $$('input[data-filter]').forEach((input) => {
    const rows = () => $$('tbody tr', $(input.dataset.filter));
    input.addEventListener('input', () => {
      const t = input.value.toLowerCase();
      rows().forEach((tr) => { tr.style.display = tr.textContent.toLowerCase().includes(t) ? '' : 'none'; });
    });
  });

  // Modals:  <button data-modal-open="#id" data-fill='{"field":"value"}'>  ...  <div class="modal-backdrop" id="id">
  function openModal(m, fill) {
    if (fill) {
      Object.entries(fill).forEach(([k, v]) => {
        const f = m.querySelector('[name="' + k + '"]');
        if (f) f.value = v;
        const t = m.querySelector('[data-fill-text="' + k + '"]');
        if (t) t.textContent = v;
      });
    }
    m.classList.add('open');
    const first = m.querySelector('input:not([type=hidden]),select,textarea');
    if (first) first.focus();
  }
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-modal-open]');
    if (o) {
      let fill = null;
      try { fill = o.dataset.fill ? JSON.parse(o.dataset.fill) : null; } catch (_) {}
      openModal($(o.dataset.modalOpen), fill);
    }
    if (e.target.closest('[data-modal-close]') || e.target.classList.contains('modal-backdrop')) {
      const m = e.target.closest('.modal-backdrop'); if (m) m.classList.remove('open');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') $$('.modal-backdrop.open').forEach((m) => m.classList.remove('open'));
  });

  // Leave form: live count of working days (Mon-Fri)
  const ls = $('#leave_start'), le = $('#leave_end'), lo = $('#leave_days');
  function workingDays() {
    if (!ls || !le || !lo || !ls.value || !le.value) return;
    let d = new Date(ls.value + 'T00:00:00'); const end = new Date(le.value + 'T00:00:00'); let n = 0;
    if (end < d) { lo.textContent = 'End date is before start date'; return; }
    for (; d <= end; d.setDate(d.getDate() + 1)) { const w = d.getDay(); if (w !== 0 && w !== 6) n++; }
    lo.textContent = n + ' working day' + (n === 1 ? '' : 's');
  }
  [ls, le].forEach((el) => el && el.addEventListener('change', workingDays));

  // Loan approval: show monthly instalment preview
  $$('[data-loan-form]').forEach((f) => {
    const amt = f.querySelector('[name=approved_amount]'), n = f.querySelector('[name=installments]'), out = f.querySelector('[data-per-month]');
    const calc = () => { const a = parseFloat(amt.value), k = parseInt(n.value, 10); out.textContent = a > 0 && k > 0 ? '≈ ' + (a / k).toFixed(2) + ' / month' : ''; };
    amt.addEventListener('input', calc); n.addEventListener('input', calc); calc();
  });

  // Employee form: show/hide portal-login password field
  const portal = $('#portal_login'), portalBox = $('#portal_fields');
  if (portal && portalBox) {
    const sync = () => portalBox.classList.toggle('hidden', !portal.checked);
    portal.addEventListener('change', sync); sync();
  }

  // Salary form: live change preview
  $$('[data-salary-form]').forEach((f) => {
    const nw = f.querySelector('[name=base_salary]'), out = f.querySelector('[data-salary-diff]');
    if (!nw || !out) return;
    nw.addEventListener('input', () => {
      const cur = parseFloat(f.dataset.current || '0'); // set when the modal is opened
      const v = parseFloat(nw.value);
      if (isNaN(v) || !cur) { out.textContent = ''; return; }
      const pct = ((v - cur) / cur * 100);
      out.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '% vs current base';
      out.className = 'text-xs mt-1 ' + (pct >= 0 ? 'text-emerald-600' : 'text-red-600');
    });
  });
})();
