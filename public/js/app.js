class App {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('menuToggle');
        this.mainNav = document.getElementById('mainNav');
        this.init();
    }

    init() {
        // Load navigation
        this.loadNavigation();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Handle initial state
        this.handleResponsive();
    }

    setupEventListeners() {
        // Menu toggle
        if (this.menuToggle) {
            this.menuToggle.addEventListener('click', () => {
                this.sidebar.classList.toggle('open');
            });
        }

        // Window resize
        window.addEventListener('resize', () => {
            this.handleResponsive();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && 
                this.sidebar.classList.contains('open') &&
                !this.sidebar.contains(e.target) &&
                e.target !== this.menuToggle) {
                this.sidebar.classList.remove('open');
            }
        });
    }

    async loadNavigation() {
        try {
            //const response = await fetch('/new/tasks');
            //const navigation = await response.json();
            //console.log(navigation);
            //this.renderNavigation(navigation);
            const response = this.apiRequest('/new/tasks');
            console.log(response);
        } catch (error) {
            console.error('Failed to load navigation:', error);
            this.mainNav.innerHTML = '<div class="error">Failed to load menu</div>';
        }
    }

    renderNavigation(navigation) {
        if (!navigation || !navigation.items) {
            this.mainNav.innerHTML = '<div class="error">No menu items</div>';
            return;
        }

        let html = '<ul>';
        
        navigation.items.forEach(item => {
            const isActive = window.location.pathname === item.url;
            html += `
                <li>
                    <a href="${item.url}" class="${isActive ? 'active' : ''}">
                        ${item.icon ? `<span class="icon">${item.icon}</span>` : ''}
                        <span class="text">${item.text}</span>
                    </a>
                </li>
            `;
        });
        
        html += '</ul>';
        this.mainNav.innerHTML = html;
    }

    handleResponsive() {
        if (window.innerWidth >= 768) {
            if (this.sidebar && 'open' in this.sidebar.classList) {
                this.sidebar.classList.remove('open');
            }
        }
    }

    // Utility function to make AJAX requests
    async apiRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    // Function to load content dynamically
    async loadContent(url, container = null) {
        try {
            const content = await this.apiRequest(url);
            
            if (container) {
                document.getElementById(container).innerHTML = content.html;
            }
            
            return content;
        } catch (error) {
            console.error('Failed to load content:', error);
            throw error;
        }
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});