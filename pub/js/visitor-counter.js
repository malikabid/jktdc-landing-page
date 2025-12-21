// CountAPI-based visitor counter
// Increments a site-level counter and displays the total visits in the footer
// Uses namespace 'malikabid' and key 'jktdc-visits' — change if you want a custom namespace

(async function () {
  const el = () => document.getElementById('visitor-count');
  if (!document || !el()) return;

  async function updateCount() {
    try {
      // 'hit' increments the counter and returns the updated value
      const res = await fetch('https://api.countapi.xyz/hit/malikabid/jktdc-visits');
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      const target = el();
      if (target) target.textContent = (data.value || 0).toLocaleString();
    } catch (err) {
      const target = el();
      if (target) target.textContent = 'N/A';
      // silently fail, do not break the page
      console.debug('Visitor counter error:', err.message || err);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateCount);
  } else {
    updateCount();
  }
})();