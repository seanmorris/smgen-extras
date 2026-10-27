/**
 * Mount one hosted PayPal button and hide its link after initialization starts.
 *
 * @param {HTMLElement} checkout Checkout wrapper emitted by the Pandoc filter.
 * @returns {void}
 */
function mountHostedButton(checkout) {
  const target = checkout.querySelector('[data-smgen-paypal-target]');
  const fallback = checkout.querySelector('.smgen-store-fallback');
  const hostedButtonId = checkout.dataset.hostedButtonId;
  const paypal = globalThis.paypal;

  if (!(target instanceof HTMLElement) || !(fallback instanceof HTMLElement)
    || !hostedButtonId || !paypal?.HostedButtons) {
    return;
  }

  try {
    paypal.HostedButtons({hostedButtonId}).render(`#${target.id}`);
    fallback.hidden = true;
  } catch {
    fallback.hidden = false;
  }
}

/**
 * Mount PayPal Add to Cart and View Cart controls for one product wrapper.
 *
 * @param {HTMLElement} checkout Checkout wrapper emitted by the Pandoc filter.
 * @returns {void}
 */
function mountCartButtons(checkout) {
  const fallback = checkout.querySelector('.smgen-store-fallback');
  const itemId = checkout.dataset.cartItemId;
  const cart = globalThis.cartPaypal;

  if (!(fallback instanceof HTMLElement) || !itemId || !cart?.AddToCart || !cart?.Cart) {
    return;
  }

  try {
    cart.AddToCart({id: itemId});
    cart.Cart({id: 'pp-view-cart'});
    fallback.hidden = true;
  } catch {
    fallback.hidden = false;
  }
}

document.querySelectorAll('[data-smgen-paypal-hosted]').forEach((checkout) => {
  if (checkout instanceof HTMLElement) {
    mountHostedButton(checkout);
  }
});

document.querySelectorAll('[data-smgen-paypal-cart]').forEach((checkout) => {
  if (checkout instanceof HTMLElement) {
    mountCartButtons(checkout);
  }
});
