// Documentation System JavaScript
class DocumentationSystem {
    constructor() {
        this.currentVersion = 'v1.2.0';
        this.versions = {};
        this.currentSection = 'overview';
        this.init();
    }

    async init() {
        await this.loadVersions();
        this.setupEventListeners();
        this.loadContent(this.currentVersion);
    }

    async loadVersions() {
        try {
            const response = await fetch('data/versions.json');
            this.versions = await response.json();
            this.populateVersionSelector();
        } catch (error) {
            console.error('Error loading versions:', error);
            // Fallback to default version
            this.versions = {
                'v1.0.0': {
                    date: '2024-01-01',
                    time: '00:00:00',
                    description: 'Initial Release'
                }
            };
        }
    }

    populateVersionSelector() {
        const select = document.getElementById('versionSelect');
        select.innerHTML = '';
        
        Object.keys(this.versions).sort().reverse().forEach(version => {
            const option = document.createElement('option');
            option.value = version;
            option.textContent = `${version} - ${this.versions[version].description}`;
            if (version === this.currentVersion) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    setupEventListeners() {
        // Version selector
        document.getElementById('versionSelect').addEventListener('change', (e) => {
            this.currentVersion = e.target.value;
            this.loadContent(this.currentVersion);
        });

        // Navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.getAttribute('href').substring(1);
                this.showSection(section);
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    async loadContent(version) {
        try {
            const response = await fetch(`data/${version}.json`);
            const data = await response.json();
            
            this.updateVersionInfo(data);
            this.renderContent(data);
            this.updateNavigation(data.sections);
        } catch (error) {
            console.error('Error loading content:', error);
            document.getElementById('contentArea').innerHTML = 
                '<div class="error">Error loading documentation. Please try again later.</div>';
        }
    }

    updateVersionInfo(data) {
        document.getElementById('currentVersion').textContent = this.currentVersion;
        document.getElementById('versionDate').textContent = data.date || this.versions[this.currentVersion]?.date || 'N/A';
        document.getElementById('lastUpdated').textContent = 
            `${data.date || 'N/A'} ${data.time || this.versions[this.currentVersion]?.time || '00:00:00'}`;
        // Update page title
        document.title = `MIS Barangay - Documentation ${this.currentVersion}`;
    }

    updateNavigation(sections) {
        const navMenu = document.getElementById('navMenu');
        navMenu.innerHTML = '';
        
        sections.forEach(section => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = `#${section.id}`;
            a.className = 'nav-link';
            a.textContent = section.title;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection(section.id);
            });
            li.appendChild(a);
            navMenu.appendChild(li);
        });
    }

    showSection(sectionId) {
        // Update active nav link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${sectionId}`) {
                link.classList.add('active');
            }
        });

        // Scroll to section
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    renderContent(data) {
        const contentArea = document.getElementById('contentArea');
        let html = '';

        data.sections.forEach(section => {
            html += this.renderSection(section);
        });

        contentArea.innerHTML = html;
    }

    renderSection(section) {
        let html = `<section id="${section.id}" class="section">`;
        html += `<h2>${this.processText(section.title)}</h2>`;
        
        if (section.content) {
            html += this.renderContentBlocks(section.content);
        }

        if (section.subsections) {
            section.subsections.forEach(subsection => {
                html += `<h3>${this.processText(subsection.title)}</h3>`;
                if (subsection.content) {
                    html += this.renderContentBlocks(subsection.content);
                }
            });
        }

        html += `</section>`;
        return html;
    }

    renderContentBlocks(content) {
        let html = '';
        
        content.forEach(block => {
            switch (block.type) {
                case 'paragraph':
                    html += `<p>${this.processText(block.text)}</p>`;
                    break;
                case 'list':
                    html += this.renderList(block);
                    break;
                case 'code':
                    html += `<pre><code>${this.escapeHtml(block.code)}</code></pre>`;
                    break;
                case 'card':
                    html += this.renderCard(block);
                    break;
                case 'table':
                    html += this.renderTable(block);
                    break;
                case 'badge':
                    html += `<span class="badge badge-${block.variant || 'info'}">${block.text}</span>`;
                    break;
            }
        });

        return html;
    }

    renderList(list) {
        const tag = list.ordered ? 'ol' : 'ul';
        let html = `<${tag} class="custom-list">`;
        list.items.forEach(item => {
            html += `<li>${this.processText(item)}</li>`;
        });
        html += `</${tag}>`;
        return html;
    }

    renderCard(card) {
        let html = '<div class="card">';
        if (card.title) {
            html += `<div class="card-title">${this.processText(card.title)}</div>`;
        }
        html += `<div class="card-content">${this.processText(card.content)}</div>`;
        html += '</div>';
        return html;
    }

    renderTable(table) {
        let html = '<div class="table-container"><table>';
        if (table.headers) {
            html += '<thead><tr>';
            table.headers.forEach(header => {
                html += `<th>${this.processText(header)}</th>`;
            });
            html += '</tr></thead>';
        }
        if (table.rows) {
            html += '<tbody>';
            table.rows.forEach(row => {
                html += '<tr>';
                row.forEach(cell => {
                    html += `<td>${this.processText(cell)}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody>';
        }
        html += '</table></div>';
        return html;
    }

    processText(text) {
        if (!text) return '';
        
        // First escape HTML to prevent XSS, but preserve our markdown patterns
        let escaped = this.escapeHtml(text);
        
        // Convert markdown-style formatting (after escaping to prevent XSS)
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        escaped = escaped.replace(/`(.*?)`/g, '<code>$1</code>');
        
        // Convert line breaks
        escaped = escaped.replace(/\n/g, '<br>');
        
        // Convert bullet points
        escaped = escaped.replace(/^• /gm, '<span class="bullet">•</span> ');
        
        // Convert emojis to badges
        escaped = escaped.replace(/✅/g, '<span class="badge badge-success">✓</span>');
        escaped = escaped.replace(/❌/g, '<span class="badge badge-error">✗</span>');
        escaped = escaped.replace(/⚠️/g, '<span class="badge badge-warning">⚠</span>');
        escaped = escaped.replace(/ℹ️/g, '<span class="badge badge-info">ℹ</span>');
        
        return escaped;
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

