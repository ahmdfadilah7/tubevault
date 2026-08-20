/**
 * TubeVault Admin UI — confirm dialogs + detail drawers
 */
(() => {
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];

  const overlay = qs('[data-ui-overlay]');
  const confirmModal = qs('[data-ui-confirm]');
  const detailModal = qs('[data-ui-detail]');
  if (!overlay || !confirmModal || !detailModal) return;

  let pendingForm = null;

  function lockScroll(on) {
    document.documentElement.style.overflow = on ? 'hidden' : '';
  }

  function openOverlay() {
    overlay.hidden = false;
    overlay.classList.add('is-open');
    lockScroll(true);
  }

  function closeAll() {
    overlay.classList.remove('is-open');
    confirmModal.classList.remove('is-open');
    detailModal.classList.remove('is-open');
    overlay.hidden = true;
    lockScroll(false);
    pendingForm = null;
  }

  function openConfirm({ title, text, tone = 'danger', confirmLabel = 'Ya, lanjutkan' }) {
    qs('[data-confirm-title]', confirmModal).textContent = title || 'Konfirmasi';
    qs('[data-confirm-text]', confirmModal).textContent = text || 'Lanjutkan tindakan ini?';
    const icon = qs('[data-confirm-icon]', confirmModal);
    icon.dataset.tone = tone;
    icon.textContent = tone === 'warn' ? '⚠' : tone === 'accent' ? '✦' : '⌫';
    const btn = qs('[data-confirm-ok]', confirmModal);
    btn.textContent = confirmLabel;
    btn.className = `ui-btn ${tone === 'accent' ? 'ui-btn--accent' : tone === 'warn' ? 'ui-btn--warn' : 'ui-btn--danger'}`;
    openOverlay();
    confirmModal.classList.add('is-open');
    detailModal.classList.remove('is-open');
    btn.focus();
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderDetail(payload) {
    const titleEl = qs('[data-detail-title]', detailModal);
    const bodyEl = qs('[data-detail-body]', detailModal);
    const badgeEl = qs('[data-detail-badge]', detailModal);

    titleEl.textContent = payload.title || 'Detail';
    badgeEl.hidden = !payload.badge;
    if (payload.badge) {
      badgeEl.textContent = payload.badge;
      badgeEl.className = `ui-badge ${payload.badgeClass || ''}`.trim();
    }

    const media = payload.image
      ? `<div class="ui-detail__hero"><img src="${escapeHtml(payload.image)}" alt="" loading="lazy"></div>`
      : '';

    const rows = (payload.fields || [])
      .map((f) => {
        const val = f.html
          ? f.html
          : escapeHtml(f.value ?? '—').replace(/\n/g, '<br>');
        return `<div class="ui-detail__row">
          <dt>${escapeHtml(f.label)}</dt>
          <dd class="${f.mono ? 'mono' : ''} ${f.muted ? 'muted' : ''}">${val}</dd>
        </div>`;
      })
      .join('');

    const note = payload.note
      ? `<div class="ui-detail__note">${escapeHtml(payload.note)}</div>`
      : '';

    const actions = (payload.actions || [])
      .map((a) => {
        if (!a.href) return '';
        return `<a class="ui-btn ui-btn--ghost" href="${escapeHtml(a.href)}" target="${a.external ? '_blank' : '_self'}" rel="noopener">${escapeHtml(a.label)}</a>`;
      })
      .join('');

    bodyEl.innerHTML = `
      ${media}
      <dl class="ui-detail__grid">${rows}</dl>
      ${note}
      ${actions ? `<div class="ui-detail__actions">${actions}</div>` : ''}
    `;

    openOverlay();
    detailModal.classList.add('is-open');
    confirmModal.classList.remove('is-open');
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn || btn.disabled) return;

    const form = btn.closest('form');
    if (!form || btn.type !== 'submit') return;

    e.preventDefault();
    pendingForm = form;
    openConfirm({
      title: btn.getAttribute('data-confirm-title') || 'Konfirmasi tindakan',
      text: btn.getAttribute('data-confirm') || 'Apakah Anda yakin?',
      tone: btn.getAttribute('data-confirm-tone') || 'danger',
      confirmLabel: btn.getAttribute('data-confirm-label') || 'Ya, lanjutkan',
    });
  });

  qs('[data-confirm-ok]', confirmModal)?.addEventListener('click', () => {
    if (!pendingForm) return;
    const form = pendingForm;
    closeAll();
    // Native submit bypasses the click interceptor
    HTMLFormElement.prototype.submit.call(form);
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-detail]');
    if (!btn) return;
    e.preventDefault();

    let payload = {};
    const raw = btn.getAttribute('data-detail');
    try {
      payload = raw ? JSON.parse(raw) : {};
    } catch {
      payload = {
        title: btn.getAttribute('data-detail-title') || 'Detail',
        fields: [{ label: 'Info', value: raw }],
      };
    }
    renderDetail(payload);
  });

  qsa('[data-ui-close]', overlay).forEach((el) => el.addEventListener('click', closeAll));
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeAll();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeAll();
  });

  window.AdminUI = { detail: renderDetail, close: closeAll };
})();
