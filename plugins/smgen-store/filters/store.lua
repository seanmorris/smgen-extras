--- Render validated static checkout metadata at explicit Markdown markers.

--- Convert a Pandoc metadata value to plain text.
local function text(value)
  return pandoc.utils.stringify(value or '')
end

--- Read one key from a Pandoc metadata map and return an empty value if absent.
local function field(mapping, key)
  if mapping == nil then
    return ''
  end
  return text(mapping[key])
end

--- Escape text placed in HTML content or attribute values.
local function escape_html(value)
  return value
    :gsub('&', '&amp;')
    :gsub('<', '&lt;')
    :gsub('>', '&gt;')
    :gsub('"', '&quot;')
    :gsub("'", '&#39;')
end

--- Encode a string for one URL query value.
local function encode_query(value)
  return (value:gsub('[^%w%-._~]', function(character)
    return string.format('%%%02X', string.byte(character))
  end))
end

--- Build a root-relative URL for an asset copied by this plugin.
local function asset_url(path)
  local base_url = os.getenv('BASE_URL') or ''
  base_url = base_url:gsub('/+$', '')
  return base_url .. path
end

--- Report whether a Pandoc Div contains the checkout marker class.
local function is_checkout_marker(div)
  for _, class_name in ipairs(div.classes) do
    if class_name == 'smgen-store-checkout' then
      return true
    end
  end
  return false
end

--- Convert an arbitrary validated product identifier into a DOM identifier.
local function dom_id(product_id, index)
  return 'smgen-store-' .. product_id:gsub('[^A-Za-z0-9_-]', '-') .. '-' .. tostring(index)
end

--- Render the common hosted checkout anchor.
local function link_markup(url, label, extra_class, identifier)
  local id_attribute = ''
  if identifier ~= nil then
    id_attribute = ' id="' .. escape_html(identifier) .. '"'
  end
  return '<a' .. id_attribute .. ' class="smgen-store-button ' .. extra_class .. '" href="'
    .. escape_html(url) .. '" rel="noopener noreferrer">' .. escape_html(label) .. '</a>'
end

--- Render one checkout marker and record any provider scripts it requires.
local function checkout_markup(store, index, resources)
  local checkout = store.checkout
  local public = checkout.public or {}
  local provider = field(checkout, 'provider')
  local presentation = field(checkout, 'presentation')
  local url = field(checkout, 'url')
  local label = field(checkout, 'label')
  if label == '' then
    label = 'Buy ' .. field(store, 'name')
  end
  if presentation == 'link' then
    return link_markup(url, label, 'smgen-store-button--' .. provider, nil)
  end

  local identifier = dom_id(field(store, 'id'), index)
  local fallback_id = identifier .. '-fallback'
  local fallback = link_markup(url, label, 'smgen-store-fallback', fallback_id)
  if provider == 'stripe' then
    resources.stripe = true
    return '<div class="smgen-store-provider smgen-store-provider--stripe"><stripe-buy-button buy-button-id="'
      .. escape_html(field(public, 'buyButtonId')) .. '" publishable-key="'
      .. escape_html(field(public, 'publishableKey')) .. '"></stripe-buy-button>' .. fallback .. '</div>'
  end

  if presentation == 'embed' then
    resources.paypal_client_id = field(public, 'clientId')
    resources.paypal_currency = field(store.price, 'currency')
    local container_id = identifier .. '-button'
    return '<div class="smgen-store-provider smgen-store-provider--paypal" data-smgen-paypal-hosted data-hosted-button-id="'
      .. escape_html(field(public, 'hostedButtonId')) .. '"><div id="' .. escape_html(container_id)
      .. '" data-smgen-paypal-target></div>' .. fallback .. '</div>'
  end

  resources.paypal_merchant_id = field(public, 'merchantId')
  local add_id = identifier .. '-add'
  local cart_id = identifier .. '-view'
  return '<div class="smgen-store-provider smgen-store-provider--paypal-cart" data-smgen-paypal-cart data-cart-item-id="'
    .. escape_html(field(public, 'cartItemId')) .. '"><paypal-add-to-cart-button data-id="'
    .. escape_html(field(public, 'cartItemId')) .. '" id="' .. escape_html(add_id)
    .. '"></paypal-add-to-cart-button><paypal-cart-button data-id="pp-view-cart" id="'
    .. escape_html(cart_id) .. '"></paypal-cart-button>' .. fallback .. '</div>'
end

--- Append each required remote provider script exactly once per page.
local function append_scripts(blocks, resources)
  if resources.stripe then
    table.insert(blocks, pandoc.RawBlock('html', '<script async src="https://js.stripe.com/v3/buy-button.js"></script>'))
  end
  if resources.paypal_client_id ~= nil then
    local source = 'https://www.paypal.com/sdk/js?client-id=' .. encode_query(resources.paypal_client_id)
      .. '&amp;components=hosted-buttons&amp;currency=' .. encode_query(resources.paypal_currency)
    table.insert(blocks, pandoc.RawBlock('html', '<script src="' .. source .. '"></script>'))
  elseif resources.paypal_merchant_id ~= nil then
    table.insert(blocks, pandoc.RawBlock('html', '<script src="https://www.paypalobjects.com/ncp/cart/cart.js" data-merchant-id="'
      .. escape_html(resources.paypal_merchant_id) .. '"></script>'))
  end
  if resources.paypal_client_id ~= nil or resources.paypal_merchant_id ~= nil then
    table.insert(blocks, pandoc.RawBlock('html', '<script defer src="'
      .. escape_html(asset_url('/smgen-store/store.js')) .. '"></script>'))
  end
end

--- Walk the document once so marker replacement and resource injection share state.
function Pandoc(document)
  local store = document.meta.store
  if store == nil then
    return document
  end
  local resources = {stripe = false}
  local marker_count = 0
  document = document:walk({
    Div = function(div)
      if not is_checkout_marker(div) then
        return nil
      end
      marker_count = marker_count + 1
      return pandoc.RawBlock('html', checkout_markup(store, marker_count, resources))
    end,
  })
  append_scripts(document.blocks, resources)
  return document
end
