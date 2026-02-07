/**
 * TinyMCE Initialization
 * This file handles the initialization of TinyMCE instances
 * Replaces CKEditor 5 with TinyMCE for better Google Translate compatibility
 */

// Global TinyMCE instances storage
window.TinyMCEInstances = {};

const defaultOptions = {
    plugins: 'lists wordcount',
    toolbar: 'bold italic underline | bullist numlist | removeformat ',
    height: 500,
    menubar: false,
    branding: false,
    browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
    setup: function (editor) {
        // Store instance globally
        window.TinyMCEInstances[editor.id] = editor;

        // Direct DOM access - Google Translate will work automatically
        editor.on('change', function () {
            // Content is directly in DOM, accessible to Google Translate
        });
    },
};

/**
 * Initialize TinyMCE on elements with the specified class
 * @param {string} className - CSS class name to target
 * @param {Object} options - Configuration options
 */
function initTinyMCE(className = 'content-tinymce', options = {}) {
    // Merge user options with defaults
    const config = { ...defaultOptions, ...options };
    config.selector = '.' + className;

    // Initialize TinyMCE
    tinymce.init(config);
}

/**
 * Initialize TinyMCE on a specific element by ID
 * @param {string} elementId - ID of the element to initialize
 * @param {Object} options - Configuration options
 */
function initTinyMCEById(elementId, options = {}) {
    const config = { ...defaultOptions, ...options };
    config.selector = '#' + elementId;

    tinymce.init(config);
}

/**
 * Get TinyMCE instance by element ID
 * @param {string} elementId - ID of the element
 * @returns {Object|null} TinyMCE instance or null
 */
function getTinyMCEInstance(elementId) {
    return window.TinyMCEInstances[elementId] || tinymce.get(elementId) || null;
}

/**
 * Get content from TinyMCE instance
 * @param {string} elementId - ID of the element
 * @returns {string} Editor content
 */
function getTinyMCEContent(elementId) {
    const editor = getTinyMCEInstance(elementId);
    return editor ? editor.getContent() : '';
}

/**
 * Set content in TinyMCE instance
 * @param {string} elementId - ID of the element
 * @param {string} content - Content to set
 */
function setTinyMCEContent(elementId, content) {
    const editor = getTinyMCEInstance(elementId);
    if (editor) {
        editor.setContent(content);
    }
}

// Make functions globally available
window.initTinyMCE = initTinyMCE;
window.initTinyMCEById = initTinyMCEById;
window.getTinyMCEInstance = getTinyMCEInstance;
window.getTinyMCEContent = getTinyMCEContent;
window.setTinyMCEContent = setTinyMCEContent;
