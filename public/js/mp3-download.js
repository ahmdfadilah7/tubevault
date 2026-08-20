/**
 * TubeVault — tombol Unduh MP3 di bawah video player (baris aksi)
 */
(() => {
  const TOKEN_KEY = 'tubevault_token';
  const BTN_ID = 'tv-mp3-download-btn';
  const WRAP_ID = 'tv-mp3-actions-wrap';

  function getToken() {
    return localStorage.getItem(TOKEN_KEY) || '';
  }

  function parseRoute() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    let m = path.match(/^\/watch\/(\d+)$/);
    if (m) return { videoId: m[1], playlistId: null };

    m = path.match(/^\/playlists\/(\d+)\/watch\/(\d+)$/);
    if (m) return { playlistId: m[1], videoId: m[2] };

    return null;
  }

  function ensureStyles() {
    if (document.getElementById('tv-mp3-style')) return;
    const style = document.createElement('style');
    style.id = 'tv-mp3-style';
    style.textContent = `
      #${WRAP_ID} {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .75rem;
      }
      #${BTN_ID} {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        flex: auto;
        min-width: min(100%, 140px);
        padding: .65rem 1rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cff, #5b2fd6);
        color: #fff;
        font: 600 .88rem/1.1 system-ui, sans-serif;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(91, 47, 214, 0.28);
        transition: filter .15s ease, transform .12s ease, opacity .15s ease;
      }
      #${BTN_ID}:hover { filter: brightness(1.07); }
      #${BTN_ID}:active { transform: translateY(1px); }
      #${BTN_ID}:disabled { opacity: .65; cursor: wait; }
      #${BTN_ID}[hidden] { display: none !important; }
      .meta__actions #${BTN_ID} {
        margin: 0;
      }
      #tv-mp3-toast {
        position: fixed;
        left: 50%;
        bottom: 24px;
        transform: translateX(-50%);
        z-index: 70;
        max-width: min(420px, calc(100vw - 24px));
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(18, 20, 30, 0.96);
        border: 1px solid rgba(255,255,255,.12);
        color: #eef0f6;
        font: 500 12px/1.4 system-ui, sans-serif;
        box-shadow: 0 10px 30px rgba(0,0,0,.35);
        text-align: center;
      }
      #tv-mp3-toast[data-tone="error"] { border-color: rgba(240,113,120,.4); color: #ffc1c5; }
      #tv-mp3-toast[hidden] { display: none !important; }
    `;
    document.head.appendChild(style);
  }

  function toast(message, tone = 'ok') {
    let el = document.getElementById('tv-mp3-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'tv-mp3-toast';
      document.body.appendChild(el);
    }
    el.dataset.tone = tone;
    el.hidden = false;
    el.textContent = message;
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.hidden = true; }, 4200);
  }

  function filenameFromDisposition(header, fallback) {
    if (!header) return fallback;
    const utf = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf?.[1]) return decodeURIComponent(utf[1]);
    const plain = header.match(/filename="?([^";]+)"?/i);
    return plain?.[1] || fallback;
  }

  async function downloadMp3(videoId, playlistId) {
    const token = getToken();
    if (!token) {
      toast('Masuk dulu untuk mengunduh MP3.', 'error');
      window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
      return;
    }

    const params = new URLSearchParams({ format: 'mp3' });
    if (playlistId) params.set('playlist_id', playlistId);

    const res = await fetch(`/api/v1/videos/${videoId}/download-audio?${params}`, {
      method: 'GET',
      headers: {
        Accept: 'audio/mpeg, application/octet-stream, application/json',
        Authorization: `Bearer ${token}`,
      },
    });

    const contentType = (res.headers.get('content-type') || '').toLowerCase();

    if (!res.ok) {
      let message = 'Gagal mengunduh audio.';
      if (contentType.includes('application/json')) {
        try {
          const data = await res.json();
          message = data.message || message;
        } catch {}
      }
      throw new Error(message);
    }

    const blob = await res.blob();
    const filename = filenameFromDisposition(
      res.headers.get('content-disposition'),
      `tubevault-${videoId}.mp3`,
    );

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function findMountPoint() {
    // Prefer action row under title (+ Playlist / Kembali / Hapus)
    const actions = document.querySelector('.meta__actions, .header__actions, .watch .meta .meta__actions');
    if (actions) return { parent: actions, mode: 'actions' };

    // Fallback: directly under player / meta block
    const meta = document.querySelector('.watch .meta, .page.watch .meta, .meta');
    if (meta) return { parent: meta, mode: 'meta' };

    const player = document.querySelector('.player, .video-player, iframe[src*="youtube"], iframe[src*="nocookie"]');
    if (player) {
      const host = player.closest('.player, .video-player, section, article, div') || player.parentElement;
      if (host?.parentElement) return { parent: host.parentElement, mode: 'after-player', after: host };
    }

    return null;
  }

  function createButton() {
    const btn = document.createElement('button');
    btn.id = BTN_ID;
    btn.type = 'button';
    btn.className = 'btn';
    btn.innerHTML = '<span aria-hidden="true">♪</span><span data-label>Unduh MP3</span>';
    btn.addEventListener('click', async () => {
      const current = parseRoute();
      if (!current || btn.disabled) return;

      const label = btn.querySelector('[data-label]');
      btn.disabled = true;
      if (label) label.textContent = 'Menyiapkan…';
      toast('Mengonversi & mengunduh audio…');

      try {
        await downloadMp3(current.videoId, current.playlistId);
        toast('Berhasil! File disimpan ke komputer Anda.');
      } catch (err) {
        toast(err?.message || 'Unduhan gagal.', 'error');
      } finally {
        btn.disabled = false;
        if (label) label.textContent = 'Unduh MP3';
      }
    });
    return btn;
  }

  function mountButton(route) {
    const mount = findMountPoint();
    if (!mount) return false;

    let btn = document.getElementById(BTN_ID);
    if (!btn) btn = createButton();

    btn.hidden = false;
    btn.dataset.videoId = route.videoId;
    if (route.playlistId) btn.dataset.playlistId = route.playlistId;
    else delete btn.dataset.playlistId;

    if (mount.mode === 'actions') {
      // Keep inside existing action row, after Playlist / before or after others
      if (btn.parentElement !== mount.parent) {
        mount.parent.appendChild(btn);
      }
      const orphanWrap = document.getElementById(WRAP_ID);
      if (orphanWrap && !orphanWrap.contains(btn)) orphanWrap.remove();
      return true;
    }

    let wrap = document.getElementById(WRAP_ID);
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = WRAP_ID;
    }

    if (wrap.parentElement !== mount.parent) {
      if (mount.mode === 'after-player' && mount.after?.nextSibling) {
        mount.parent.insertBefore(wrap, mount.after.nextSibling);
      } else if (mount.mode === 'after-player' && mount.after) {
        mount.after.insertAdjacentElement('afterend', wrap);
      } else {
        mount.parent.appendChild(wrap);
      }
    }

    if (btn.parentElement !== wrap) wrap.appendChild(btn);
    return true;
  }

  function syncButton() {
    ensureStyles();
    const route = parseRoute();
    const btn = document.getElementById(BTN_ID);
    const wrap = document.getElementById(WRAP_ID);

    if (!route) {
      if (btn) btn.hidden = true;
      if (wrap) wrap.hidden = true;
      return;
    }

    if (wrap) wrap.hidden = false;
    if (!mountButton(route) && btn) {
      // DOM belum siap (SPA masih render) — biarkan interval berikutnya
      btn.hidden = true;
    }
  }

  const _push = history.pushState;
  const _replace = history.replaceState;
  history.pushState = function (...args) {
    _push.apply(this, args);
    queueMicrotask(syncButton);
  };
  history.replaceState = function (...args) {
    _replace.apply(this, args);
    queueMicrotask(syncButton);
  };
  window.addEventListener('popstate', () => queueMicrotask(syncButton));

  const obs = new MutationObserver(() => syncButton());
  obs.observe(document.documentElement, { childList: true, subtree: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncButton);
  } else {
    syncButton();
  }
  setInterval(syncButton, 1200);
})();
