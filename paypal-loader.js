// PayPal integration script for Appwrite video system
window.paypalInitialized = false;

// Function to load PayPal SDK
window.loadPayPalSDK = function(clientId) {
  // Only load the SDK once
  if (document.getElementById('paypal-js-sdk')) {
    return;
  }
  
  console.log('Loading PayPal SDK with client ID:', clientId);
  
  const script = document.createElement('script');
  script.id = 'paypal-js-sdk';
  script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=USD`;
  script.async = true;
  script.onload = function() {
    console.log('PayPal SDK loaded');
    window.paypalInitialized = true;
    
    // Check if we already have a video to render the button for
    const videoElement = document.getElementById('video-player');
    if (videoElement && videoElement.src) {
      const urlParams = new URLSearchParams(window.location.search);
      const videoId = urlParams.get('id');
      const videoTitle = document.getElementById('video-title').textContent;
      const priceElement = document.getElementById('video-price');
      const price = parseFloat(priceElement.textContent.replace('$', ''));
      
      if (videoId && !isNaN(price)) {
        window.renderPayPalButton(price, videoId, videoTitle);
      }
    }
  };
  
  document.body.appendChild(script);
};

// Function to render the PayPal button
window.renderPayPalButton = function(price, videoId, videoTitle) {
  if (!window.paypal) {
    console.error('PayPal SDK not loaded yet');
    return;
  }
  
  const container = document.getElementById('paypal-button-container');
  if (!container) {
    console.error('PayPal button container not found');
    return;
  }
  
  // Clear any existing buttons
  container.innerHTML = '';
  
  console.log('Rendering PayPal button for video:', videoId, 'at price:', price);
  
  // Render the PayPal button
  paypal.Buttons({
    style: {
      color: 'blue',
      shape: 'rect',
      label: 'pay',
      height: 45
    },
    
    // Set up the transaction
    createOrder: function(data, actions) {
      return actions.order.create({
        purchase_units: [{
          description: videoTitle || 'Video Content',
          amount: {
            currency_code: 'USD',
            value: price.toFixed(2)
          }
        }]
      });
    },
    
    // Handle successful transactions
    onApprove: function(data, actions) {
      return actions.order.capture().then(function(details) {
        // Show a success message to the buyer
        alert('Transaction completed! Thank you ' + details.payer.name.given_name + '!');
        
        // Redirect to the success page or provide access to the content
        window.location.href = 'pay.html?id=' + videoId + '&order=' + data.orderID;
      });
    },
    
    // Handle errors
    onError: function(err) {
      console.error('PayPal error:', err);
      alert('There was an error processing your payment. Please try again or contact support.');
    }
  }).render('#paypal-button-container');
};

// Initialize PayPal when the page loads
document.addEventListener('DOMContentLoaded', function() {
  // Get PayPal client ID from site configuration
  fetch('save_config.php')
    .then(response => response.json())
    .then(config => {
      if (config.paypalClientId) {
        window.loadPayPalSDK(config.paypalClientId);
      } else {
        console.warn('PayPal client ID not found in configuration');
      }
    })
    .catch(error => {
      console.error('Error loading PayPal configuration:', error);
    });
});