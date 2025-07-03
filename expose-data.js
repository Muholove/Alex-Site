// Script to ensure cardsData is available globally for PayPal
document.addEventListener('DOMContentLoaded', function() {
  // Check if cardsData already exists
  if (typeof cardsData !== 'undefined' && Array.isArray(cardsData)) {
    console.log('Exposing cardsData to window object');
    
    // Expose to window for PayPal to access
    window.cardsData = cardsData;
  } else {
    console.log('cardsData not yet defined, waiting...');
    
    // Set up a timer to check again after data might be loaded
    setTimeout(function() {
      if (typeof cardsData !== 'undefined' && Array.isArray(cardsData)) {
        console.log('cardsData found after waiting, exposing to window');
        window.cardsData = cardsData;
      } else {
        console.error('cardsData still not available after waiting');
      }
    }, 2000);
  }
}); 