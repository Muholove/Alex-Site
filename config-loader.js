// Load site configuration and apply it to the page
document.addEventListener('DOMContentLoaded', function() {
    // Load site configuration
    fetch('get_config.php')
        .then(response => response.json())
        .then(config => {
            console.log('Site configuration loaded:', config);
            
            // Store config globally so other scripts can access it
            window.siteConfig = config;
            
            // Update document title if site title is configured
            if (config.siteTitle) {
                document.title = config.siteTitle + ' - ' + document.title;
                
                // Update site name in header
                const siteHeader = document.querySelector('header h2');
                if (siteHeader) {
                    siteHeader.textContent = config.siteTitle;
                }
            }
            
            // Only load PayPal script if we're not on preview.html
            // This prevents double-loading conflicts
            if (config.paypalClientId && !window.location.pathname.includes('preview.html')) {
                // Remove existing PayPal script
                const existingScript = document.querySelector('script[src*="paypal.com/sdk/js"]');
                if (existingScript) {
                    existingScript.remove();
                }
                
                // Create new script with configured client ID
                // Enable card payments by adding enable-funding=card
                const script = document.createElement('script');
                script.src = `https://www.paypal.com/sdk/js?client-id=${config.paypalClientId}&currency=USD&enable-funding=card`;
                document.head.appendChild(script);
                
                console.log('PayPal script updated with client ID:', config.paypalClientId);
            }
            
            // Update Telegram links if configured
            if (config.telegramLink) {
                // Update all links to Telegram
                document.querySelectorAll('a[href*="t.me"]').forEach(link => {
                    link.href = config.telegramLink;
                });
                
                // Update all buttons that open Telegram links
                document.querySelectorAll('button[onclick*="t.me"]').forEach(button => {
                    button.onclick = function() { 
                        window.open(config.telegramLink, '_blank'); 
                    };
                });
                
                console.log('Telegram links updated to:', config.telegramLink);
            }
        })
        .catch(error => {
            console.error('Error loading site configuration:', error);
        });
}); 