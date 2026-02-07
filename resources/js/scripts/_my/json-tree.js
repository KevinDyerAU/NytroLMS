class JSONTree {
    constructor(container) {
        this.container = container;
        this.data = null;
        // Bind the click handler to the container
        this.container.addEventListener('click', e => {
            if (e.target.classList.contains('collapsible')) {
                e.target.classList.toggle('collapsed');
                const nextUl = e.target.closest('li').querySelector('ul');
                if (nextUl) {
                    nextUl.style.display = e.target.classList.contains(
                        'collapsed'
                    )
                        ? 'none'
                        : 'block';
                }
            }
        });
    }

    render(data) {
        this.data = data;
        this.container.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'json-tree-wrapper';
        wrapper.appendChild(this.renderValue(data));
        this.container.appendChild(wrapper);
    }

    renderValue(value) {
        if (value === null) return this.renderSimpleValue(value, 'null');
        if (typeof value === 'boolean')
            return this.renderSimpleValue(value, 'boolean');
        if (typeof value === 'number')
            return this.renderSimpleValue(value, 'number');
        if (typeof value === 'string')
            return this.renderSimpleValue(value, 'string');
        if (Array.isArray(value)) return this.renderArray(value);
        if (typeof value === 'object') return this.renderObject(value);
        return document.createTextNode(String(value));
    }

    renderSimpleValue(value, type) {
        const span = document.createElement('span');
        span.className = type;
        span.textContent = JSON.stringify(value);
        return span;
    }

    renderArray(array) {
        if (array.length === 0) {
            const span = document.createElement('span');
            span.textContent = '[]';
            return span;
        }

        const container = document.createElement('div');
        const toggleSpan = document.createElement('span');
        toggleSpan.className = 'collapsible';
        toggleSpan.textContent = '[';
        container.appendChild(toggleSpan);

        const ul = document.createElement('ul');
        ul.style.display = 'block'; // Start expanded

        array.forEach((value, index) => {
            const li = document.createElement('li');
            li.appendChild(this.renderValue(value));
            if (index < array.length - 1)
                li.appendChild(document.createTextNode(','));
            ul.appendChild(li);
        });

        container.appendChild(ul);
        const closeSpan = document.createElement('span');
        closeSpan.textContent = ']';
        container.appendChild(closeSpan);

        return container;
    }

    renderObject(obj) {
        if (Object.keys(obj).length === 0) {
            const span = document.createElement('span');
            span.textContent = '{}';
            return span;
        }

        const container = document.createElement('div');
        const toggleSpan = document.createElement('span');
        toggleSpan.className = 'collapsible';
        toggleSpan.textContent = '{';
        container.appendChild(toggleSpan);

        const ul = document.createElement('ul');
        ul.style.display = 'block'; // Start expanded

        Object.entries(obj).forEach(([key, value], index, entries) => {
            const li = document.createElement('li');
            const keySpan = document.createElement('span');
            keySpan.className = 'key';
            keySpan.textContent = `"${key}": `;
            li.appendChild(keySpan);
            li.appendChild(this.renderValue(value));
            if (index < entries.length - 1)
                li.appendChild(document.createTextNode(','));
            ul.appendChild(li);
        });

        container.appendChild(ul);
        const closeSpan = document.createElement('span');
        closeSpan.textContent = '}';
        container.appendChild(closeSpan);

        return container;
    }

    expandAll() {
        this.container.querySelectorAll('.collapsible').forEach(el => {
            el.classList.remove('collapsed');
            const ul = el.closest('div').querySelector('ul');
            if (ul) ul.style.display = 'block';
        });
    }

    collapseAll() {
        this.container.querySelectorAll('.collapsible').forEach(el => {
            el.classList.add('collapsed');
            const ul = el.closest('div').querySelector('ul');
            if (ul) ul.style.display = 'none';
        });
    }
}
