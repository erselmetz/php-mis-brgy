// Theme switcher utility
// Usage:
// Theme.apply(themeObject) -> applies and saves to localStorage
// Theme.reset() -> resets to default extracted theme
// Theme.loadStored() -> loads stored theme (called automatically on DOMContentLoaded)

(function(window){
  const defaultTheme = {
    '--theme-color-1': '#ffffff',
    '--theme-color-2': '#91b2fd',
    '--theme-color-3': '#514a5c',
    '--theme-color-4': '#e9eaea',
    '--theme-color-5': '#ececec',
    '--theme-color-6': '#446c3e',
    // prefer green as primary/secondary so utilities render green by default
    '--theme-primary': 'var(--theme-color-6)',
    '--theme-primary-dark': '#355a30', // sample darker green
    '--theme-secondary': 'var(--theme-color-6)',
    '--theme-accent': 'var(--theme-color-3)',
  };

  function apply(themeObj, persist=true){
    if (!themeObj) return;
    const root = document.documentElement;
    Object.keys(themeObj).forEach(k => {
      try { root.style.setProperty(k, themeObj[k]); } catch(e) { console.warn('Invalid theme property', k, e); }
    });
    // also update common Tailwind color tokens so utilities pick up the theme
    try {
      // prefer semantic mappings
      const primary = themeObj['--theme-primary'] || themeObj['--theme-color-1'];
      const secondary = themeObj['--theme-secondary'] || themeObj['--theme-color-2'];
      const accent = themeObj['--theme-accent'] || themeObj['--theme-color-3'];
      if (primary) root.style.setProperty('--color-blue-400', primary);
      if (secondary) root.style.setProperty('--color-blue-500', secondary);
      if (accent) root.style.setProperty('--color-blue-600', accent);
      if (accent) root.style.setProperty('--color-blue-700', accent);
      if (accent) root.style.setProperty('--color-indigo-600', accent);
    } catch(e) { console.warn('Failed to map tailwind tokens', e); }
    if (persist) localStorage.setItem('site-theme', JSON.stringify(themeObj));
  }

  function loadStored(){
    try{
      const raw = localStorage.getItem('site-theme');
      if (!raw) return null;
      const obj = JSON.parse(raw);
      apply(obj, false);
      return obj;
    }catch(e){ console.warn('Failed to load stored theme', e); return null; }
  }

  function reset(){
    localStorage.removeItem('site-theme');
    apply(defaultTheme, false);
  }

  // expose API
  window.Theme = { apply, loadStored, reset, defaultTheme };

  document.addEventListener('DOMContentLoaded', ()=>{
    // apply stored theme if present, otherwise apply defaultTheme to ensure variables exist inline
    if (!loadStored()) apply(defaultTheme, false);
    // runtime: replace remaining hardcoded blue hex literals in stylesheets and inline styles
    try {
      const blueHexes = ['#93c5fd','#60a5fa','#3b82f6','#2563eb','#1d4ed8','#1e40af','#1e3a8a','#172554'];
      const replacement = getComputedStyle(document.documentElement).getPropertyValue('--theme-color-6') || '#446c3e';
      function replaceInStylesheets() {
        for (const sheet of Array.from(document.styleSheets)) {
          try {
            for (const rule of Array.from(sheet.cssRules || [])) {
              if (!rule || !rule.cssText) continue;
              let txt = rule.cssText;
              let changed = false;
              for (const h of blueHexes) if (txt.includes(h)) { txt = txt.split(h).join(replacement); changed = true; }
              if (changed) {
                try { sheet.deleteRule(Array.prototype.indexOf.call(sheet.cssRules, rule)); } catch(e) {}
                try { sheet.insertRule(txt, sheet.cssRules.length); } catch(e) {}
              }
            }
          } catch(e) { /* ignore cross-origin or read-only sheets */ }
        }
      }
      function replaceInlineStyles() {
        for (const el of Array.from(document.querySelectorAll('*'))) {
          const s = el.getAttribute && el.getAttribute('style');
          if (!s) continue;
          let out = s;
          for (const h of blueHexes) if (out.includes(h)) out = out.split(h).join(replacement);
          if (out !== s) el.setAttribute('style', out);
        }
      }
      // run once after DOM ready, schedule a follow-up in case styles are injected later
      replaceInStylesheets(); replaceInlineStyles(); setTimeout(() => { replaceInStylesheets(); replaceInlineStyles(); }, 1200);
    } catch(e){ console.warn('Theme override failed', e); }
  });

})(window);
