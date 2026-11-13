<script>
(function () {
  const map = {'0':'۰','1':'۱','2':'۲','3':'۳','4':'۴','5':'۵','6':'۶','7':'۷','8':'۸','9':'۹'};
  function faDigits(s) {
    return String(s).replace(/[0-9]/g, d => map[d]);
  }
  function walk(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      if (/[0-9]/.test(node.nodeValue)) node.nodeValue = faDigits(node.nodeValue);
      return;
    }
    if (node.closest && node.closest('script, style')) return;
    for (const child of [...node.childNodes]) walk(child);
  }
  function run() {
    document.querySelectorAll('.fi-pagination, .fi-ta-pagination, .fi-ta-selection-indicator').forEach(el => walk(el));
  }
  document.addEventListener('DOMContentLoaded', run);
  document.addEventListener('livewire:navigated', run);
  if (window.Livewire && Livewire.hook) {
    Livewire.hook('morph.updated', () => run());
  }
  setTimeout(run, 0);
})();
</script>
