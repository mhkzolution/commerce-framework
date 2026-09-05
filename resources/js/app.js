import './storefront/header.js';
import './storefront/blog.js';
import './storefront/cart.js';
import { initWishlist } from './storefront/wishlist.js';
import { initCustomerExperience } from './storefront/customer-experience.js';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initWishlist();
        initCustomerExperience();
    });
} else {
    initWishlist();
    initCustomerExperience();
}
