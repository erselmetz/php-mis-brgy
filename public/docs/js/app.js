// Documentation System with Multi-page Support
class DocumentationSystem {
    constructor() {
        this.currentPage = 'changelog'; // docs, api, changelog
        this.currentVersion = 'v1.4.0';
        this.versions = {};
        this.listenersSetup = false;
        this.sidebarListenerAdded = false;
    }

    async init() {
        try {
            await this.loadVersions();
            this.setupEventListeners();
            this.loadPage(this.currentPage);
        } catch (error) {
            console.error('Error initializing documentation system:', error);
        }
    }

    async loadVersions() {
        try {
            // Prefer embedded versions JSON when available (works for file:// and local previews)
            const embedded = document.getElementById('docVersions');
            if (embedded && embedded.textContent) {
                try {
                    this.versions = JSON.parse(embedded.textContent);
                    return;
                } catch (parseErr) {
                    console.error('Failed to parse embedded versions JSON:', parseErr);
                    // fallback to fetch
                }
            }

            const response = await fetch('data/versions.json');
            if (!response.ok) throw new Error('Failed to fetch versions.json');
            this.versions = await response.json();
        } catch (error) {
            console.error('Error loading versions:', error);
            this.versions = {};
        }
    }

    setupEventListeners() {
        // Check if already set up
        if (this.listenersSetup) {
            return;
        }
        this.listenersSetup = true;

        // Page navigation
        document.querySelectorAll('.nav-item[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                if (page) {
                    this.setActivePage(page);
                    this.loadPage(page);
                }
            });
        });

        // Sidebar navigation - use event delegation on document (only one listener)
        // This is safe to add multiple times as it uses event delegation
        if (!this.sidebarListenerAdded) {
            document.addEventListener('click', (e) => {
                if(e.target.closest('.sidebar-nav a')) {
                    const link = e.target.closest('.sidebar-nav a');
                    const sectionId = link.getAttribute('href')?.substring(1);
                    if(sectionId) {
                        this.highlightNavLink(sectionId);
                        setTimeout(() => {
                            const section = document.getElementById(sectionId);
                            if(section) {
                                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }, 100);
                    }
                }
            });
            this.sidebarListenerAdded = true;
        }

        // Footer links
        document.querySelectorAll('.footer-section a[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                if (page) {
                    this.setActivePage(page);
                    this.loadPage(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }

    setActivePage(page) {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`.nav-item[data-page="${page}"]`)?.classList.add('active');
        this.currentPage = page;
    }

    async loadPage(page) {
        const contentArea = document.getElementById('contentArea');
        if (!contentArea) {
            console.error('Content area not found');
            return;
        }
        contentArea.innerHTML = '<div class="loading">Loading...</div>';

        try {
            let data;
            if(page === 'api') {
                console.log('Loading API documentation');
                const response = await fetch('./data/api.json');
                if (!response.ok) {
                    throw new Error(`Failed to load API documentation (HTTP ${response.status})`);
                }
                data = await response.json();
                console.log('API documentation loaded successfully');
            } else if(page === 'changelog') {
                console.log('Building changelog page');
                data = await this.buildChangelogPage();
                console.log('Changelog built successfully');
            }

            if (!data) {
                throw new Error('No data received');
            }

            this.renderPage(data);
            this.updateVersionInfo(data);
            this.buildSidebar(data);

            // Highlight code blocks
            document.querySelectorAll('pre code').forEach(block => {
                hljs.highlightElement(block);
            });
        } catch (error) {
            console.error('Error loading page:', error);
            console.error('Page:', page, 'Version:', this.currentVersion);
            const errorMessage = `
                <div class="alert alert-danger">
                    <h4>Error loading content</h4>
                    <p><strong>Details:</strong> ${error.message}</p>
                    <p><strong>Page:</strong> ${page}</p>
                    ${page === 'docs' ? `<p><strong>Version:</strong> ${this.currentVersion}</p>` : ''}
                    <p>Please check the browser console for more details.</p>
                </div>
            `;
            contentArea.innerHTML = errorMessage;
        }
    }

    async buildChangelogPage() {
        if (!this.versions || Object.keys(this.versions).length === 0) {
            return {
                title: 'Changelog',
                description: 'Version history and changes',
                sections: [{
                    id: 'changelog-empty',
                    title: 'No versions available',
                    content: [{
                        type: 'paragraph',
                        text: 'Version history is not available at this time.'
                    }]
                }]
            };
        }

        const versions = Object.keys(this.versions).sort().reverse();
        const sections = [];

        for(const version of versions) {
            const versionData = this.versions[version];
            if (!versionData) continue;
            
            sections.push({
                id: `changelog-${version}`,
                title: `${version} - ${versionData.description || 'No description'}`,
                content: [
                    {
                        type: 'paragraph',
                        html: `<strong>Released:</strong> ${versionData.date || 'Unknown'} ${versionData.time || ''}`
                    },
                    {
                        type: 'list',
                        items: versionData.changes || []
                    }
                ]
            });
        }

        return {
            title: 'Changelog',
            description: 'Version history and changes',
            sections: sections
        };
    }

    updateVersionInfo(data) {
        // Use current version
        const footerVersion = document.getElementById('footerVersion');
        if (footerVersion) {
            footerVersion.textContent = this.currentVersion;
        }

        // Prefer explicit page data.date, otherwise fall back to versions.json for the current version
        const dateFromPage = data && data.date ? data.date : null;
        const dateFromVersions = (this.versions && this.versions[this.currentVersion] && this.versions[this.currentVersion].date) ? this.versions[this.currentVersion].date : null;
        const finalDate = dateFromPage || dateFromVersions || '';
        if (finalDate) {
            const footerDate = document.getElementById('footerDate');
            if (footerDate) {
                footerDate.textContent = finalDate;
            }
        }
    }

    buildSidebar(data) {
        const sidebarNav = document.getElementById('sidebarNav');
        if (!sidebarNav) {
            return;
        }
        sidebarNav.innerHTML = '';

        if(data && data.sections && data.sections.length > 0) {
            data.sections.forEach(section => {
                if (!section || !section.id || !section.title) return;
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = `#${section.id}`;
                a.className = 'sidebar-nav-link';
                a.textContent = section.title;
                li.appendChild(a);
                sidebarNav.appendChild(li);
            });
        }
    }

    highlightNavLink(sectionId) {
        document.querySelectorAll('.sidebar-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.querySelector(`.sidebar-nav-link[href="#${sectionId}"]`);
        if(activeLink) {
            activeLink.classList.add('active');
        }
    }

    renderPage(data) {
        const contentArea = document.getElementById('contentArea');
        if (!contentArea) {
            console.error('Content area not found');
            return;
        }
        
        if (!data) {
            contentArea.innerHTML = '<div class="alert alert-danger">No data available.</div>';
            return;
        }

        let html = '';

        // Add intro section if exists
        if(data.description && data.title) {
            html += `
                <section class="section" style="padding: 2rem; border-bottom: 1px solid #dee2e6; margin-bottom: 0;">
                    <h2>${this.escapeHtml(data.title)}</h2>
                    <p>${this.escapeHtml(data.description)}</p>
                </section>
            `;
        }

        // Render all sections
        if(data.sections && Array.isArray(data.sections)) {
            data.sections.forEach(section => {
                if (section) {
                    html += this.renderSection(section);
                }
            });
        }

        contentArea.innerHTML = html;
    }

    renderSection(section) {
        if (!section || !section.id || !section.title) {
            return '';
        }
        
        let html = `<section id="${this.escapeHtml(section.id)}" class="section">`;
        html += `<div style="padding: 0 2rem;"><h2>${this.escapeHtml(section.title)}</h2>`;
        
        if(section.content && Array.isArray(section.content)) {
            html += this.renderContentBlocks(section.content);
        }

        if(section.subsections && Array.isArray(section.subsections)) {
            section.subsections.forEach(subsection => {
                if (subsection && subsection.title) {
                    html += `<h3>${this.escapeHtml(subsection.title)}</h3>`;
                    if(subsection.content && Array.isArray(subsection.content)) {
                        html += this.renderContentBlocks(subsection.content);
                    }
                }
            });
        }

        html += `</div></section>`;
        return html;
    }

    renderContentBlocks(content) {
        if (!content || !Array.isArray(content)) {
            return '';
        }
        
        let html = '';
        
        content.forEach(block => {
            if (!block || !block.type) return;
            
            switch (block.type) {
                case 'paragraph':
                    // Allow HTML if html property exists, otherwise escape text
                    if (block.html !== undefined) {
                        html += `<p>${block.html}</p>`;
                    } else {
                        html += `<p>${this.escapeHtml(block.text || '')}</p>`;
                    }
                    break;
                case 'list':
                    html += this.renderList(block);
                    break;
                case 'code':
                    const language = block.language || 'php';
                    html += `<pre><code class="language-${language}">${this.escapeHtml(block.code || '')}</code></pre>`;
                    break;
                case 'card':
                    html += this.renderCard(block);
                    break;
                case 'table':
                    html += this.renderTable(block);
                    break;
                case 'badge':
                    html += `<span class="badge badge-${block.variant || 'info'}">${this.escapeHtml(block.text || '')}</span>`;
                    break;
                case 'alert':
                    html += `<div class="alert alert-${block.variant || 'info'}">${this.escapeHtml(block.text || '')}</div>`;
                    break;
            }
        });

        return html;
    }

    renderList(list) {
        const tag = list.ordered ? 'ol' : 'ul';
        let html = `<${tag}>`;
        (list.items || []).forEach(item => {
            html += `<li>${this.escapeHtml(item)}</li>`;
        });
        html += `</${tag}>`;
        return html;
    }

    renderCard(card) {
        if (!card) return '';
        
        const variant = card.variant ? ` card-${card.variant}` : '';
        let html = `<div class="card${variant}">`;
        if(card.title) {
            html += `<div class="card-title">${this.escapeHtml(card.title)}</div>`;
        }
        if (card.content) {
            const content = String(card.content)
                .split('\n')
                .map(line => line.startsWith('•') ? `<div style="margin-bottom: 0.5rem;">• ${this.escapeHtml(line.substring(1).trim())}</div>` : `<div>${this.escapeHtml(line)}</div>`)
                .join('');
            html += `<div class="card-content">${content}</div>`;
        }
        html += '</div>';
        return html;
    }

    renderTable(table) {
        if (!table) return '';
        
        let html = '<div class="table-container"><table>';
        if(table.headers && Array.isArray(table.headers)) {
            html += '<thead><tr>';
            table.headers.forEach(header => {
                html += `<th>${this.escapeHtml(header)}</th>`;
            });
            html += '</tr></thead>';
        }
        if(table.rows && Array.isArray(table.rows)) {
            html += '<tbody>';
            table.rows.forEach(row => {
                if (Array.isArray(row)) {
                    html += '<tr>';
                    row.forEach(cell => {
                        html += `<td>${this.escapeHtml(cell)}</td>`;
                    });
                    html += '</tr>';
                }
            });
            html += '</tbody>';
        }
        html += '</table></div>';
        return html;
    }

    escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
// Prevent multiple initializations
window.docSystemInstance = null;

function initializeDocs() {
    if (window.docSystemInstance) {
        return; // Already initialized
    }
    
    // Check if required elements exist
    if (!document.getElementById('contentArea')) {
        console.error('Required DOM elements not found');
        return;
    }
    
    console.log('Initializing documentation system...');
    window.docSystemInstance = new DocumentationSystem();
    window.docSystemInstance.init();
}

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDocs);
} else {
    // DOM already loaded
    initializeDocs();
}


