/**
 * Toast Notification System
 * Modern, styled toast notifications to replace browser alert()
 * 
 * Usage:
 *   Toast.success('Operation completed');
 *   Toast.error('Something went wrong');
 *   Toast.warning('Please check input');
 *   Toast.info('Just a heads up');
 */

const Toast = {
    container: null,

    init() {
        if (this.container) return;
        
        // Create container
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-3 pointer-events-none';
        this.container.style.cssText = 'max-width: 400px; width: 100%;';
        document.body.appendChild(this.container);
        
        // Add CSS animations
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes toast-slide-in {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes toast-slide-out {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .toast-enter { animation: toast-slide-in 0.3s ease-out forwards; }
                .toast-exit { animation: toast-slide-out 0.3s ease-in forwards; }
            `;
            document.head.appendChild(style);
        }
    },

    /**
     * Show a toast notification
     * @param {Object|string} options - Options object or message string
     * @param {string} options.type - 'success' | 'error' | 'warning' | 'info'
     * @param {string} options.message - Message to display
     * @param {number} options.duration - Duration in ms (0 = no auto-dismiss), default 5000
     */
    show(options) {
        this.init();
        
        // Handle string shorthand
        if (typeof options === 'string') {
            options = { message: options, type: 'info' };
        }
        
        const { type = 'info', message, duration = 5000 } = options;
        
        // Type configurations
        const config = {
            success: {
                bg: 'bg-gradient-to-r from-green-500 to-green-600',
                icon: 'fa-check-circle',
                title: 'Berhasil'
            },
            error: {
                bg: 'bg-gradient-to-r from-red-500 to-red-600',
                icon: 'fa-exclamation-circle',
                title: 'Error'
            },
            warning: {
                bg: 'bg-gradient-to-r from-yellow-500 to-orange-500',
                icon: 'fa-exclamation-triangle',
                title: 'Peringatan'
            },
            info: {
                bg: 'bg-gradient-to-r from-blue-500 to-blue-600',
                icon: 'fa-info-circle',
                title: 'Info'
            }
        };
        
        const { bg, icon, title } = config[type] || config.info;
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-enter pointer-events-auto ${bg} text-white p-4 rounded-lg shadow-2xl flex items-start gap-3`;
        toast.style.cssText = 'backdrop-filter: blur(10px); box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
        
        toast.innerHTML = `
            <div class="flex-shrink-0 mt-0.5">
                <i class="fas ${icon} text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm">${title}</p>
                <p class="text-sm opacity-95 mt-0.5 break-words">${this.escapeHtml(message)}</p>
            </div>
            <button class="flex-shrink-0 text-white/80 hover:text-white transition-colors" onclick="Toast.dismiss(this.parentElement)">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        this.container.appendChild(toast);
        
        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }
        
        return toast;
    },

    dismiss(toast) {
        if (!toast || toast.classList.contains('toast-exit')) return;
        
        toast.classList.remove('toast-enter');
        toast.classList.add('toast-exit');
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // Shorthand methods
    success(message, duration) {
        return this.show({ type: 'success', message, duration });
    },

    error(message, duration) {
        return this.show({ type: 'error', message, duration });
    },

    warning(message, duration) {
        return this.show({ type: 'warning', message, duration });
    },

    info(message, duration) {
        return this.show({ type: 'info', message, duration });
    }
};

// Auto-init when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Toast.init());
} else {
    Toast.init();
}
