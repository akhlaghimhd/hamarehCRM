<script>
(() => {
    const digits = { '0':'۰','1':'۱','2':'۲','3':'۳','4':'۴','5':'۵','6':'۶','7':'۷','8':'۸','9':'۹' };
    const excluded = 'SCRIPT,STYLE,NOSCRIPT,TEXTAREA,INPUT,PRE,CODE,[contenteditable="true"]';

    const localizeText = (root = document.body) => {
        if (!root) return;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                if (!/[0-9]/.test(node.nodeValue ?? '')) return NodeFilter.FILTER_REJECT;
                return node.parentElement?.closest(excluded)
                    ? NodeFilter.FILTER_REJECT
                    : NodeFilter.FILTER_ACCEPT;
            },
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            node.nodeValue = node.nodeValue.replace(/[0-9]/g, digit => digits[digit]);
        });
    };

    let scheduled = false;
    const schedule = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
            scheduled = false;
            localizeText();
        });
    };

    document.addEventListener('DOMContentLoaded', schedule);
    document.addEventListener('livewire:navigated', schedule);
    document.addEventListener('livewire:morphed', schedule);
    new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
    schedule();
})();
</script>
