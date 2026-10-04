</main>
<footer class="app-footer d-flex align-items-center justify-content-center">
    <span class="text-muted small">&copy; <?= date('Y') ?> DemoIT CRM</span>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (LanguageService::currentCode() !== 'en'): ?>
<script>
    (() => {
        const translations = <?= json_encode(TranslationService::textMap(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        const prefixes = Object.entries(translations).filter(([source]) => source.endsWith(': ') || source.endsWith(' on '));
        const ignoredElements = 'script, style, noscript, textarea, input, code, pre, [data-no-translate]';
        const translateText = (node) => {
            const parent = node.parentElement;
            if (!parent || parent.closest(ignoredElements)) {
                return;
            }
            const original = node.nodeValue;
            const trimmed = original.trim();
            const exactTranslation = translations[trimmed];
            const prefix = exactTranslation ? null : prefixes.find(([source]) => trimmed.startsWith(source));
            const numberedText = exactTranslation || prefix ? null : trimmed.match(/^(\d+)(\s+)(.+)$/);
            const numberedTranslation = numberedText ? translations[numberedText[3]] : null;
            const translation = exactTranslation
                || (prefix ? `${prefix[1]}${trimmed.slice(prefix[0].length)}` : null)
                || (numberedTranslation ? `${numberedText[1]}${numberedText[2]}${numberedTranslation}` : null);
            if (!translation || translation === trimmed) {
                return;
            }
            const leading = original.match(/^\s*/)?.[0] || '';
            const trailing = original.match(/\s*$/)?.[0] || '';
            node.nodeValue = `${leading}${translation}${trailing}`;
        };
        const translateElement = (element) => {
            if (!(element instanceof Element) || element.matches(ignoredElements)) {
                return;
            }
            const attributes = ['aria-label', 'placeholder', 'title', 'alt'];
            if (element instanceof HTMLInputElement && ['button', 'submit', 'reset'].includes(element.type)) {
                attributes.push('value');
            }
            attributes.forEach((attribute) => {
                const value = element.getAttribute(attribute);
                if (value && translations[value]) {
                    element.setAttribute(attribute, translations[value]);
                }
            });
            const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
            while (walker.nextNode()) {
                translateText(walker.currentNode);
            }
        };

        translateElement(document.documentElement);
        new MutationObserver((changes) => {
            changes.forEach((change) => change.addedNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    translateText(node);
                } else {
                    translateElement(node);
                }
            }));
        }).observe(document.documentElement, { childList: true, subtree: true });
    })();
</script>
<?php endif; ?>
</body>
</html>
