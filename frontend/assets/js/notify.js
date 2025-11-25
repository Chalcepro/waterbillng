(function(){
  if (window.notify) return;

  const containerId = 'wb-toast-container';
  const ensureContainer = () => {
    let c = document.getElementById(containerId);
    if (!c) {
      c = document.createElement('div');
      c.id = containerId;
      c.setAttribute('role','region');
      c.setAttribute('aria-live','polite');
      c.style.position = 'fixed';
      c.style.zIndex = '2147483000';
      c.style.top = '16px';
      c.style.right = '16px';
      c.style.display = 'flex';
      c.style.flexDirection = 'column';
      c.style.gap = '10px';
      c.style.pointerEvents = 'none';
      document.body.appendChild(c);
      // Mobile: pin to bottom
      const mq = window.matchMedia('(max-width: 600px)');
      const setPos = () => {
        if (mq.matches) {
          c.style.top = '';
          c.style.right = '8px';
          c.style.left = '8px';
          c.style.bottom = '12px';
          c.style.alignItems = 'stretch';
        } else {
          c.style.top = '16px';
          c.style.right = '16px';
          c.style.left = '';
          c.style.bottom = '';
          c.style.alignItems = 'flex-end';
        }
      };
      setPos();
      mq.addEventListener('change', setPos);
    }
    return c;
  };

  const baseStyles = (type) => {
    const palette = {
      success: { bg: 'linear-gradient(135deg,#16a34a,#0e7a39)', icon: '✔️' },
      error:   { bg: 'linear-gradient(135deg,#ef4444,#b91c1c)', icon: '⛔' },
      info:    { bg: 'linear-gradient(135deg,#2563eb,#1d4ed8)', icon: 'ℹ️' },
      warn:    { bg: 'linear-gradient(135deg,#f59e0b,#b45309)', icon: '⚠️' },
    }[type] || { bg: 'linear-gradient(135deg,#374151,#1f2937)', icon: '•' };

    return palette;
  };

  const createToast = (message, opts={}) => {
    const { type='info', title='', duration=3500, dismissible=true } = opts;
    const { bg, icon } = baseStyles(type);
    const wrap = document.createElement('div');
    wrap.className = 'wb-toast';
    wrap.role = 'status';
    wrap.style.pointerEvents = 'auto';
    wrap.style.color = '#fff';
    wrap.style.background = bg;
    wrap.style.borderRadius = '12px';
    wrap.style.boxShadow = '0 10px 24px rgba(0,0,0,.25)';
    wrap.style.padding = '12px 14px';
    wrap.style.maxWidth = '360px';
    wrap.style.display = 'flex';
    wrap.style.alignItems = 'flex-start';
    wrap.style.gap = '10px';
    wrap.style.transform = 'translateY(-8px)';
    wrap.style.opacity = '0';
    wrap.style.transition = 'transform .25s ease, opacity .25s ease';

    const ic = document.createElement('div');
    ic.textContent = icon;
    ic.style.fontSize = '18px';
    ic.style.lineHeight = '1';

    const content = document.createElement('div');
    const titleEl = document.createElement('div');
    if (title) {
      titleEl.textContent = title;
      titleEl.style.fontWeight = '700';
      titleEl.style.marginBottom = '2px';
      titleEl.style.letterSpacing = '.2px';
    }
    const msgEl = document.createElement('div');
    msgEl.innerHTML = message;
    msgEl.style.fontSize = '0.95rem';

    content.appendChild(titleEl);
    content.appendChild(msgEl);

    const close = document.createElement('button');
    close.setAttribute('aria-label','Close');
    close.textContent = '✖';
    close.style.background = 'transparent';
    close.style.border = '0';
    close.style.color = '#fff';
    close.style.cursor = 'pointer';
    close.style.marginLeft = '8px';
    close.style.fontSize = '14px';
    close.style.opacity = '.8';

    if (!dismissible) close.style.display = 'none';

    wrap.appendChild(ic);
    wrap.appendChild(content);
    wrap.appendChild(close);

    const container = ensureContainer();
    container.appendChild(wrap);

    requestAnimationFrame(() => {
      wrap.style.transform = 'translateY(0)';
      wrap.style.opacity = '1';
    });

    let hideTimer = null;
    const remove = () => {
      wrap.style.transform = 'translateY(-8px)';
      wrap.style.opacity = '0';
      setTimeout(() => wrap.remove(), 250);
    };

    if (duration > 0) hideTimer = setTimeout(remove, duration);

    close.addEventListener('click', () => {
      if (hideTimer) clearTimeout(hideTimer);
      remove();
    });

    return wrap;
  };

  window.notify = {
    success: (msg, opts={}) => createToast(msg, { ...opts, type:'success' }),
    error:   (msg, opts={}) => createToast(msg, { ...opts, type:'error' }),
    info:    (msg, opts={}) => createToast(msg, { ...opts, type:'info' }),
    warn:    (msg, opts={}) => createToast(msg, { ...opts, type:'warn' }),
  };
})();
