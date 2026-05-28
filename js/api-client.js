/**
 * EduAttend Store API Client - Simplified Version
 * No backend - uses localStorage for data persistence
 */

class EduAttendAPI {
    constructor(baseURL = '/dee/api.php') {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('auth_token');
    }

    // Mock authentication using localStorage
    async login(username, password) {
        if (username && password.length >= 6) {
            this.token = 'mock_token_' + Date.now();
            localStorage.setItem('auth_token', this.token);
            localStorage.setItem('user', JSON.stringify({ username }));
            return { status: 'success', data: { user: { username } } };
        }
        return { status: 'error', message: 'Invalid credentials' };
    }

    async register(userData) {
        if (userData.username && userData.email && userData.password) {
            this.token = 'mock_token_' + Date.now();
            localStorage.setItem('auth_token', this.token);
            localStorage.setItem('user', JSON.stringify(userData));
            return { status: 'success', data: { user: userData } };
        }
        return { status: 'error', message: 'Invalid data' };
    }

    async logout() {
        this.token = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        return { status: 'success' };
    }

    getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    isLoggedIn() {
        return !!this.token && !!this.getCurrentUser();
    }

    // Mock product methods using localStorage
    async getProducts(params = {}) {
        const mockProducts = [
            { id: 1, name: 'Product 1', description: 'A great product', price: 50, category: 'electronics', image_url: 'https://images.unsplash.com/photo-1512499617640-c2f999018b72?w=300&h=200&fit=crop&crop=center' },
            { id: 2, name: 'Product 2', description: 'Amazing item', price: 75, category: 'fashion', image_url: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=300&h=200&fit=crop&crop=center' },
            { id: 3, name: 'Product 3', description: 'Must have', price: 100, category: 'home', image_url: 'https://images.unsplash.com/photo-1503602642458-232111445657?w=300&h=200&fit=crop&crop=center' },
        ];
        return Promise.resolve(mockProducts);
    }

    async addToCart(productId, quantity = 1) {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const item = cart.find(i => i.id === productId);
        if (item) {
            item.qty += quantity;
        } else {
            cart.push({ id: productId, qty: quantity });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        return { status: 'success', message: 'Added to cart' };
    }

    async isAuthenticated() {
        return !!this.token && !!this.getCurrentUser();
    }
}

// Helper functions for backward compatibility
async function login(username, password) {
    const api = new EduAttendAPI();
    const result = await api.login(username, password);
    return result.status === 'success' ? { success: true } : { success: false, error: result.message };
}

function showNotification(message, type = 'info') {
    alert(message); // Simple fallback
}

    showNotification(message, type = 'info') {
        // Simple notification system - can be enhanced with a proper notification library
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    handleError(error) {
        console.error('API Error:', error);
        this.showNotification(error.message || 'An error occurred', 'error');
    }

// Global API instance
const api = new EduAttendAPI();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EduAttendAPI;
}