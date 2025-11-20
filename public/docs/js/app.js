// Documentation System with Multi-page Support
class DocumentationSystem {
    constructor() {
        this.currentPage = 'docs'; // docs, api, changelog
        this.currentVersion = 'latest';
        this.versions = {};
        this.init();
    }

    async init() {
        await this.loadVersions();
        this.setupEventListeners();
        this.loadPage(this.currentPage);
    }

    async loadVersions() {
        try {
            const response = await fetch('data/versions.json');
            this.versions = await response.json();
        } catch (error) {
            console.error('Error loading versions:', error);
        }
    }

    setupEventListeners() {
        // Page navigation
        document.querySelectorAll('.nav-item[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                this.setActivePage(page);
                this.loadPage(page);
            });
        });

        // Version selector
        document.getElementById('versionSelect').addEventListener('change', (e) => {
            this.currentVersion = e.target.value;
            this.loadPage(this.currentPage);
        });

        // Sidebar navigation
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

        // Footer links
        document.querySelectorAll('.footer-section a[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                this.setActivePage(page);
                this.loadPage(page);
                window.scrollTo({ top: 0, behavior: 'smooth' });
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
        contentArea.innerHTML = '<div class="loading">Loading...</div>';

        try {
            let data;
            if(page === 'docs') {
                const response = await fetch(`data/${this.currentVersion}.json`);
                data = await response.json();
            } else if(page === 'api') {
                const response = await fetch('data/api.json');
                data = await response.json();
            } else if(page === 'changelog') {
                data = await this.buildChangelogPage();
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
            contentArea.innerHTML = '<div class="alert alert-danger">Error loading content. Please try again.</div>';
        }
    }

    async buildChangelogPage() {
        const versions = Object.keys(this.versions).sort().reverse();
        const sections = [];

        for(const version of versions) {
            const versionData = this.versions[version];
            sections.push({
                id: `changelog-${version}`,
                title: `${version} - ${versionData.description}`,
                content: [
                    {
                        type: 'paragraph',
                        text: `<strong>Released:</strong> ${versionData.date} ${versionData.time}`
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
        // Resolve actual version string (map 'latest' to newest available)
        const available = Object.keys(this.versions || {});
        const newest = available.length ? available.sort().reverse()[0] : 'v1.3.0';
        const resolvedVersion = this.currentVersion === 'latest' ? newest : this.currentVersion;
        document.getElementById('footerVersion').textContent = resolvedVersion;

        // Prefer explicit page data.date, otherwise fall back to versions.json for the resolved version
        const dateFromPage = data && data.date ? data.date : null;
        const dateFromVersions = (this.versions && this.versions[resolvedVersion] && this.versions[resolvedVersion].date) ? this.versions[resolvedVersion].date : null;
        const finalDate = dateFromPage || dateFromVersions || '';
        if (finalDate) {
            document.getElementById('footerDate').textContent = finalDate;
        }
    }

    buildSidebar(data) {
        const sidebarNav = document.getElementById('sidebarNav');
        sidebarNav.innerHTML = '';

        if(data.sections && data.sections.length > 0) {
            data.sections.forEach(section => {
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
        if(data.sections) {
            data.sections.forEach(section => {
                html += this.renderSection(section);
            });
        }

        contentArea.innerHTML = html;
    }

    renderSection(section) {
        let html = `<section id="${this.escapeHtml(section.id)}" class="section">`;
        html += `<div style="padding: 0 2rem;"><h2>${this.escapeHtml(section.title)}</h2>`;
        
        if(section.content) {
            html += this.renderContentBlocks(section.content);
        }

        if(section.subsections) {
            section.subsections.forEach(subsection => {
                html += `<h3>${this.escapeHtml(subsection.title)}</h3>`;
                if(subsection.content) {
                    html += this.renderContentBlocks(subsection.content);
                }
            });
        }

        html += `</div></section>`;
        return html;
    }

    renderContentBlocks(content) {
        let html = '';
        
        content.forEach(block => {
            switch (block.type) {
                case 'paragraph':
                    html += `<p>${block.text}</p>`;
                    break;
                case 'list':
                    html += this.renderList(block);
                    break;
                case 'code':
                    const language = block.language || 'php';
                    html += `<pre><code class="language-${language}">${this.escapeHtml(block.code)}</code></pre>`;
                    break;
                case 'card':
                    html += this.renderCard(block);
                    break;
                case 'table':
                    html += this.renderTable(block);
                    break;
                case 'badge':
                    html += `<span class="badge badge-${block.variant || 'info'}">${this.escapeHtml(block.text)}</span>`;
                    break;
                case 'alert':
                    html += `<div class="alert alert-${block.variant || 'info'}">${block.text}</div>`;
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
        const variant = card.variant ? ` card-${card.variant}` : '';
        let html = `<div class="card${variant}">`;
        if(card.title) {
            html += `<div class="card-title">${this.escapeHtml(card.title)}</div>`;
        }
        const content = card.content
            .split('\n')
            .map(line => line.startsWith('•') ? `<div style="margin-bottom: 0.5rem;">• ${this.escapeHtml(line.substring(1).trim())}</div>` : `<div>${this.escapeHtml(line)}</div>`)
            .join('');
        html += `<div class="card-content">${content}</div>`;
        html += '</div>';
        return html;
    }

    renderTable(table) {
        let html = '<div class="table-container"><table>';
        if(table.headers) {
            html += '<thead><tr>';
            table.headers.forEach(header => {
                html += `<th>${this.escapeHtml(header)}</th>`;
            });
            html += '</tr></thead>';
        }
        if(table.rows) {
            html += '<tbody>';
            table.rows.forEach(row => {
                html += '<tr>';
                row.forEach(cell => {
                    html += `<td>${this.escapeHtml(cell)}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody>';
        }
        html += '</table></div>';
        return html;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DocumentationSystem();
});


