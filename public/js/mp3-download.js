/**
 * TubeVault — tombol Unduh MP3 di halaman watch / playlist watch
 * Menyimpan file audio ke komputer pengguna via API download-audio.
 */
(() => {
  const TOKEN_KEY = 'tubevault_token';
  const BTN_ID = 'tv-mp3-download-btn';

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
      #${BTN_ID} {
        position: fixed;
        right: 16px;
        bottom: 88px;
        z-index: 60;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cff, #5b2fd6);
        color: #fff;
        font: 600 13px/1 "DM Sans", system-ui, sans-serif;
        box-shadow: 0 12px 32px rgba(91, 47, 214, 0.45);
        cursor: pointer;
        transition: transform .15s ease, filter .15s ease, opacity .15s ease;
      }
      #${BTN_ID}:hover { filter: brightness(1.08); transform: translateY(-1px); }
      #${BTN_ID}:disabled { opacity: .65; cursor: wait; transform: none; }
      #${BTN_ID}[hidden] { display: none !important; }
      #tv-mp3-toast {
        position: fixed;
        right: 16px;
        bottom: 148px;
        z-index: 61;
        max-width: min(320px, calc(100vw - 32px));
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(18, 20, 30, 0.96);
        border: 1px solid rgba(255,255,255,.12);
        color: #eef0f6;
        font: 500 12px/1.4 system-ui, sans-serif;
        box-shadow: 0 10px 30px rgba(0,0,0,.35);
      }
      #tv-mp3-toast[data-tone="error"] { border-color: rgba(240,113,120,.4); color: #ffc1c5; }
      #tv-mp3-toast[hidden] { display: none !important; }
      @media (max-width: 640px) {
        #${BTN_ID} { bottom: 76px; right: 12px; padding: 11px 14px; font-size: 12px; }
        #tv-mp3-toast { bottom: 132px; right: 12px; }
      }
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

  function syncButton() {
    ensureStyles();
    const route = parseRoute();
    let btn = document.getElementById(BTN_ID);

    if (!route) {
      if (btn) btn.hidden = true;
      return;
    }

    if (!btn) {
      btn = document.createElement('button');
      btn.id = BTN_ID;
      btn.type = 'button';
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
      document.body.appendChild(btn);
    }

    btn.hidden = false;
    btn.dataset.videoId = route.videoId;
    if (route.playlistId) btn.dataset.playlistId = route.playlistId;
    else delete btn.dataset.playlistId;
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncButton);
  } else {
    syncButton();
  }
  setInterval(syncButton, 1500);
})();
