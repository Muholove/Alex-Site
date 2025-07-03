// Function to safely load the PayPal SDK
window.loadPayPalSafely = function(clientId) {
  const container = document.getElementById('paypal-button-container');
  if (!container) {
    console.error('PayPal container not found');
    return;
  }
  
  container.innerHTML = '<div style="padding: 20px; text-align: center;">Loading payment options...</div>';
  
  // Clean up any existing PayPal scripts
  document.querySelectorAll('script[src*="paypal.com"]').forEach(s => s.remove());
  
  // Create a new script element with minimal parameters
  const script = document.createElement('script');
  script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}`;
  
  // Define what happens when the script loads
  script.onload = function() {
    console.log('PayPal script loaded successfully');
    if (typeof paypal !== 'undefined') {
      renderPayPalButtons();
    } else {
      showPayPalError('PayPal SDK loaded but not initialized correctly');
    }
  };
  
  // Define what happens if the script fails to load
  script.onerror = function() {
    showPayPalError('Failed to load PayPal SDK');
  };
  
  // Add the script to the page
  document.head.appendChild(script);
};

// Function to render PayPal buttons
function renderPayPalButtons() {
  const container = document.getElementById('paypal-button-container');
  container.innerHTML = '';
  
  try {
    paypal.Buttons({
      style: {
        layout: 'vertical',
        color: 'blue',
        shape: 'rect',
        label: 'pay'
      },
      createOrder: function(data, actions) {
        const contentId = new URLSearchParams(window.location.search).get('id');
        const content = cardsData.find(item => item.id == contentId);
        
        return actions.order.create({
          purchase_units: [{
            amount: {
              value: content ? content.price : 0
            }
          }]
        });
      },
      onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
          const contentId = new URLSearchParams(window.location.search).get('id');
          const content = cardsData.find(item => item.id == contentId);
          
          // Success message code here
          alert("Payment successful!");
        });
      }
    }).render('#paypal-button-container');
  } catch (err) {
    console.error('Error rendering PayPal buttons:', err);
    showPayPalError('Error initializing PayPal');
  }
}

// Function to show PayPal error message
function showPayPalError(message) {
  const container = document.getElementById('paypal-button-container');
  if (container) {
    container.innerHTML = `
      <div style="padding: 20px; text-align: center;">
        <p style="color: #e74c3c; margin-bottom: 15px;">${message}</p>
        <p>Please try another payment method or contact support.</p>
        <button onclick="window.loadPayPalSafely(siteConfig.paypalClientId)"
                style="background: #0070ba; color: white; border: none; padding: 10px 20px; 
                border-radius: 4px; margin-top: 15px; cursor: pointer;">
          Retry PayPal
        </button>
      </div>
    `;
  }
} 