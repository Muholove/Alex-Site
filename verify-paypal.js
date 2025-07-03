// Script to verify PayPal integration

// This can be included in the page temporarily to debug PayPal issues
(function() {
    console.log('PayPal verification script running...');
    
    // Check if we're on preview.html
    const isPreviewPage = window.location.pathname.includes('preview.html');
    console.log('Is preview page:', isPreviewPage);
    
    // Count PayPal scripts
    const paypalScripts = document.querySelectorAll('script[src*="paypal.com/sdk/js"]');
    console.log('PayPal scripts found:', paypalScripts.length);
    paypalScripts.forEach((script, index) => {
        console.log(`Script ${index + 1} src:`, script.src);
    });
    
    // Check PayPal container
    const paypalContainer = document.getElementById('paypal-button-container');
    console.log('PayPal container found:', !!paypalContainer);
    if (paypalContainer) {
        console.log('PayPal container content:', paypalContainer.innerHTML);
    }
    
    // Check if PayPal SDK is loaded
    console.log('PayPal SDK loaded:', typeof window.paypal !== 'undefined');
    
    // Add listener to detect when PayPal SDK is loaded
    window.addEventListener('load', function() {
        setTimeout(() => {
            console.log('Window loaded, checking PayPal status:');
            console.log('PayPal SDK loaded after window load:', typeof window.paypal !== 'undefined');
            
            if (typeof window.paypal !== 'undefined') {
                console.log('PayPal SDK version:', window.paypal.version || 'unknown');
                console.log('PayPal Buttons available:', typeof window.paypal.Buttons === 'function');
            }
        }, 2000);
    });
    
    // Report any errors that might affect PayPal
    window.addEventListener('error', function(event) {
        console.log('Caught error that might affect PayPal:', event.error);
    });
})(); 