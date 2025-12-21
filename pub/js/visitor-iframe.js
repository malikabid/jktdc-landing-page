// Insert the external counter widget into a sandboxed iframe to guarantee rendering
(function(){
  const container = document.getElementById('visitor-counter-embed');
  if (!container) return;

  const iframe = document.createElement('iframe');
  iframe.setAttribute('title', 'visitor-counter');
  iframe.setAttribute('aria-hidden', 'false');
  iframe.style.border = 'none';
  iframe.style.width = '140px';
  iframe.style.height = '56px';
  iframe.style.background = 'transparent';
  iframe.style.display = 'block';

  // Build HTML content for the iframe. It includes the two scripts and a fallback link.
  const html = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;background:transparent;">
    <script src="https://www.counters-free.net/count/j4lg"></script>
    <br>
    <a href="https://www.counters-free.net/" target="_blank" rel="noopener noreferrer">free Counters</a>
    <script src="https://whomania.com/ctr?id=5b8f61924510d4b1c684c42c2e1b4d9f8a4feb20"></script>
  </body></html>`;

  // Use srcdoc when possible
  try {
    iframe.srcdoc = html;
  } catch (e) {
    // Fallback: write into iframe after it's attached
    iframe.addEventListener('load', function(){
      try { iframe.contentWindow.document.open(); iframe.contentWindow.document.write(html); iframe.contentWindow.document.close(); } catch (e) { /* ignore */ }
    });
  }

  // Clear container and insert iframe
  container.innerHTML = '';
  container.appendChild(iframe);
})();