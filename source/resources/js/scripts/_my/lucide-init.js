/**
 * Simplified Lucide Icon Initialization
 * Fixes click event issues with dashboard icons
 */

// Global Lucide initialization function
window.initializeLucideIcons = function (container = document) {
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        try {
            // Only process icons that haven't been processed yet
            const unprocessedIcons = container.querySelectorAll(
                '[data-lucide]:not([data-lucide-processed])'
            );

            if (unprocessedIcons.length > 0) {
                // Mark icons as being processed to prevent duplicate processing
                unprocessedIcons.forEach(icon => {
                    icon.setAttribute('data-lucide-processed', 'true');
                });

                // Initialize icons in the specified container
                lucide.createIcons({
                    root: container,
                });
            }

            // Handle deprecated icon-name attribute
            const deprecatedIcons = container.querySelectorAll(
                '[icon-name]:not([data-lucide-processed])'
            );
            if (deprecatedIcons.length > 0) {
                deprecatedIcons.forEach(function (element) {
                    const iconName = element.getAttribute('icon-name');
                    element.setAttribute('data-lucide', iconName);
                    element.removeAttribute('icon-name');
                    element.setAttribute('data-lucide-processed', 'true');
                });

                lucide.createIcons({
                    root: container,
                });
            }

            return true;
        } catch (error) {
            console.error('Error initializing Lucide icons:', error);
            return false;
        }
    }
    return false;
};

// Single initialization on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        window.initializeLucideIcons();
    });
} else {
    // DOM already loaded
    window.initializeLucideIcons();
}

// Handle dynamic content with MutationObserver
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function (mutations) {
        let hasNewIcons = false;

        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (
                            (node.hasAttribute &&
                                node.hasAttribute('data-lucide') &&
                                !node.hasAttribute('data-lucide-processed')) ||
                            (node.querySelectorAll &&
                                node.querySelectorAll(
                                    '[data-lucide]:not([data-lucide-processed])'
                                ).length > 0)
                        ) {
                            hasNewIcons = true;
                        }
                    }
                });
            }
        });

        if (hasNewIcons) {
            clearTimeout(window.lucideReinitTimeout);
            window.lucideReinitTimeout = setTimeout(function () {
                window.initializeLucideIcons();
            }, 50);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        });
    } else {
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }
}

// Add event delegation to handle clicks on Lucide-generated SVG icons
document.addEventListener('click', function (e) {
    // Check if the clicked element is an SVG icon inside an anchor tag
    let target = e.target;

    // Traverse up to find if we're inside an anchor tag
    while (target && target !== document) {
        if (target.tagName === 'A') {
            // We're inside an anchor tag, let the default behavior happen
            return;
        }
        if (
            target.tagName === 'SVG' &&
            target.parentElement &&
            target.parentElement.tagName === 'A'
        ) {
            // We clicked on an SVG inside an anchor, trigger the anchor's click
            target.parentElement.click();
            return;
        }
        target = target.parentElement;
    }
});
