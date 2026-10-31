# Theme contract

How to build and register a Storefront Theme. Contract version: **1.5.0** (`Modules/Theming/Support/ThemeContract.php`). Only the Platform team changes the contract.

## What a Theme is

A folder under `resources/themes/{slug}/`:

```text
resources/themes/noor/
├── theme.json        manifest
├── preview.png       image shown in the Vendor's Appearance page
└── pages/
    ├── Home.vue
    ├── Category.vue
    ├── Product.vue
    ├── Search.vue          (optional)
    ├── Login.vue           (optional)
    ├── Register.vue        (optional)
    ├── ForgotPassword.vue  (optional)
    ├── ResetPassword.vue   (optional)
    ├── Addresses.vue       (optional)
    ├── OrderDetail.vue     (optional)
    ├── OrderConfirmation.vue (optional)
    ├── Survey.vue          (optional)
    ├── Cart.vue
    ├── Checkout.vue
    ├── Account.vue
    ├── Orders.vue
    └── NotFound.vue
```

Components, layouts and assets are up to the Theme; keep them inside its folder. Pages are loaded lazily, so a Store downloads only its own Theme.

## Registering

1. Create the folder and `theme.json`.
2. Run `php artisan theme:register noor` (or press "Re-scan themes folder" in the Admin Panel).
3. The Theme now appears in every Vendor's Appearance page. No migration and no change to core code.

`theme:list` shows what is registered. Registering again is safe: it refreshes the name, version and manifest and never changes whether the Theme is active. The Platform Admin can deactivate a Theme; Stores using it render the `default` Theme until it is reactivated. The `default` Theme cannot be deactivated.

## theme.json

| Key | Required | Meaning |
| --- | --- | --- |
| `name` | yes | Display name |
| `slug` | yes | Lowercase letters, digits and dashes; must equal the folder name |
| `version` | yes | Your Theme's semantic version |
| `contract_version` | yes | The contract version you built against |
| `preview` | yes | Image file in the Theme folder |
| `pages` | yes | The pages you provide. Each needs `pages/{Name}.vue`. Allowed: `Home`, `Category`, `Product`, `Cart`, `Checkout`, `Account`, `Orders`, `NotFound` (the `default` Theme must provide all of these; another Theme may omit any page and the default's is used) and `Search`, `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `Addresses`, `OrderDetail`, `OrderConfirmation` (optional) |
| `settings` | no | Extra Vendor-editable fields (below) |
| `defaults` | no | Override the default of a core setting: `primary_color`, `secondary_color`, `heading_font`, `body_font`, `corner_style` |

A page you leave out is rendered with the `default` Theme's page. Only the `default` Theme must provide all required pages.

### Settings fields

Every field has `key` (lowercase letters, digits, underscores; not a core key), `label`, `type` and `default`.

| type | extra keys | value |
| --- | --- | --- |
| `color` | | `#RRGGBB` |
| `select` | `options`: `{ "value": "Label" }` | one of the options |
| `range` | `min`, `max` | number between them |
| `toggle` | | true or false |

A Vendor's values reach your pages as CSS variables named `--theme-{key}` (underscores become dashes), for example `--theme-show-footer-social` is `"1"` or `"0"`.

## What the Vendor can change

Always: primary and secondary colour, heading and body font (from the approved Arabic list), corner style (`sharp` or `soft`), plus your own `settings`. Nothing else: spacing, type scale and system colours are fixed Platform tokens.

## Styling: CSS variables only

The Storefront sets these on the root; style with them and never with raw values:

`--color-primary`, `--color-on-primary`, `--color-secondary`, `--color-on-secondary`, `--color-bg`, `--color-surface`, `--color-text`, `--color-text-muted`, `--color-border`, `--color-success`, `--color-warning`, `--color-danger`, `--font-heading`, `--font-body`, `--radius-sm`, `--radius-md`, `--radius-lg`, plus your `--theme-*` variables.

`--color-on-primary` and `--color-on-secondary` are black or white, whichever reads better on the colour. Themes must be right-to-left, mobile-first and accessible (visible focus, 44px tap targets, Arabic labels).

## Page data

Every page receives plain props only. There are no models and a Theme never queries the database. Every page receives:

| Prop | Content |
| --- | --- |
| `contractVersion` | The contract version of this data |
| `store` | `name`, `slug`, `logoUrl`, `faviconUrl`, `footerText`, `contact { email, phone, whatsapp }`, `social { network: url }` |
| `customer` | `{ name }` or `null` (signed-in Customer) |
| `tokens` | The CSS variables above, as `{ "--name": "value" }`; set them on your root element |
| `urls` | Page addresses: `home`, `search`, `cart`, `login`, `register`, `forgotPassword`, `account`, `addresses`, `checkout`, `orders` |
| `actions` | Form endpoints: `login`, `register`, `logout` (POST), `forgotPassword`, `resetPassword`, `profile` (PUT), `password` (PUT), `addresses` (POST; `PUT`/`DELETE` on `{addresses}/{id}`; `POST {addresses}/{id}/default`), `cartItems` (POST to add; `PUT`/`DELETE` on `{cartItems}/{id}`), `acceptPrices` (POST), `checkout` (POST) |
| `status` | A one-off message from the last action, or `null` |
| `navigation` | `[{ name, url }]` top-level categories |
| `cartCount` | Number of items in the visitor's Cart |
| `seo` | `title`, `description`, `image` |

Plus, per page:

| Page | Props |
| --- | --- |
| `Home` | `latest` (product cards), `categories` (`[{ name, url }]`) |
| `Category` | `category { name, slug }`, `products` (page of cards), `sort`, `breadcrumbs` |
| `Product` | `product { name, description, price, originalPrice, images [{ thumb, large }], variants [{ id, size, color, price, available }] }`, `breadcrumbs` |
| `Search` | `query`, `products`, `breadcrumbs` |
| `Cart` | `items [{ id, variantId, name, sku, size, color, imageUrl, productUrl, quantity, available, unavailable, priceChanged, unitPrice, lineTotal, previousPrice }]`, `total`, `canCheckout`, `hasChanges`, `storeOpen` |
| `Checkout` | `ready`, `items [{ name, size, color, quantity, lineTotal }]`, `subtotal`, `addresses [{ id, label, recipientName, phone, governorateName, city, addressLine, isDefault, shippingFee, total }]` (`shippingFee` is `null` when the Store does not deliver there), `paymentMethods [{ value, label }]`, `governorates`, `maxAddresses`, `issues` (`store_paused`, `empty`, `cart_needs_review`, `shipping_not_set`) |
| `Account` | `customer { name, email, phone, emailVerified }` |
| `Login` | `redirect` (a safe same-Store address to return to, or `null`) |
| `Register` | `redirect` |
| `ForgotPassword` | nothing extra |
| `ResetPassword` | `token`, `email` |
| `Addresses` | `addresses [{ id, label, recipientName, phone, governorate, governorateName, city, addressLine, notes, isDefault }]`, `governorates [{ value, name }]`, `maxAddresses` |
| `Orders` | `orders [{ number, status, statusLabel, total, placedAt, url, canCancel }]` |
| `OrderDetail` | `order { number, status, statusLabel, total, subtotal, shippingFee, placedAt, expiresAt, paymentMethod, paymentMethodLabel, address, items [{ name, sku, size, color, quantity, unitPrice, lineTotal }], canCancel, cancelUrl, url, payment }` (see below) |
| `OrderConfirmation` | `order` (same shape as `OrderDetail`); show the Order number and the payment instructions |
| `NotFound` | nothing extra (rendered with status 404) |

A product card is `{ name, url, price, originalPrice, imageUrl }`; a page of cards is `{ data, prevUrl, nextUrl, currentPage, lastPage, total }`; a breadcrumb is `{ name, url | null }`. Prices are already formatted strings.

Order statuses are `pending_payment`, `payment_review`, `paid`, `processing`, `shipped`, `delivered`, `completed`, `cancelled` and `refunded`; `statusLabel` is already translated. 
`order.payment` is `{ state, amount, reference, account, expiresAt, attemptsUsed, attemptsLeft, maxAttempts, canSubmit, rejectionReason, receiptUrl, submitUrl, maxKilobytes, acceptedTypes, online }`. `state` is `awaiting_receipt`, `awaiting_online`, `under_review`, `rejected`, `exhausted`, `paid` or `closed`. `account` is `{ name, number, instructions }` to transfer to, or `null` when the Platform has no active account for the chosen method (say payment details are not available). `amount` is the exact total to transfer and `reference` the Order number to quote. `expiresAt` is `null` while the Order is under review (its timer is paused). `canSubmit` says whether a Receipt can be sent now; `receiptUrl` is a temporary link (a few minutes) to the Customer's latest Receipt, or `null`. `online` is `{ available, url }`: when `state` is `awaiting_online` the Order is paid on Fawaterk's hosted checkout, so show a button that POSTs (empty body) to `online.url`, which redirects the browser away. A Receipt is posted as multipart form data to `submitUrl` with `receipt` (JPG, PNG, WebP or PDF up to `maxKilobytes`), and optional `transfer_reference` and `sender_phone`; refusals come back under `errors.receipt`, `errors.sender_phone` and `errors.order`.

### Forms

Adding to the Cart posts `variant_id` and `quantity` to `actions.cartItems`; changing a line is a PUT with `quantity`; Checkout posts `address_id` and `payment_method`, and refusals come back under `errors.checkout` (with `errors.lines` naming the items). Submit forms to the `actions` URLs with the field names the Platform expects (Inertia's `useForm` works as is). Validation errors come back as the standard `errors` prop, keyed by field. Login takes `identifier` (an email or a mobile number), `password` and an optional `redirect`; Register takes `name`, `email`, `phone`, `password`, `redirect` (email or phone is required); ForgotPassword takes `email`; ResetPassword takes `token`, `email`, `password`, `password_confirmation`; profile takes `name`, `email`, `phone`; password takes `current_password`, `password`, `password_confirmation`; an address takes `label`, `recipient_name`, `phone`, `governorate`, `city`, `address_line`, `notes`, `is_default`.

### Changelog

- **1.3.0**: `OrderDetail` and `OrderConfirmation` gain `order.payment` (payment instructions, Receipt state and upload data). Additive only.
- **1.4.0**: `OrderDetail` and `OrderConfirmation` gain `order.timeline` (status steps with date), `order.shipment` (courier, tracking number and link, or null), `order.canConfirmReceipt`, `order.confirmReceiptUrl` and `order.storeContact`. Additive only.
- **1.5.0**: new optional page `Survey` (props `survey`: `state` of form, answered, thanks, expired or invalid, plus `orderNumber` and `submitUrl`). `OrderDetail` gains `order.survey` (`pending` and a signed `url`, or null). Additive only.
- **1.2.0**: Cart, Checkout and Orders carry real data; added the `OrderDetail` and `OrderConfirmation` pages, the `checkout` and `orders` URLs, and the `cartItems`, `acceptPrices` and `checkout` actions. Additive only.
- **1.1.0**: added the `Login`, `Register`, `ForgotPassword`, `ResetPassword` and `Addresses` pages, the `actions` and `status` shell data, more `urls`, and extra `Account` customer fields. Additive only: a 1.0.0 Theme keeps working and uses the default Theme for the new pages.

## Versioning

`contract_version` is a semantic version. A **minor** release only adds data, so a Theme keeps working. A **major** release breaks. The Platform serves the current major and the one before it. A Theme built for any other major is not served: every one of its pages falls back to the `default` Theme. The same fallback applies per page when a Theme lacks a page.

## Previewing

A Vendor Owner can open their own Store with `?preview_theme={slug}` (and optional `preview_settings[key]=value`) to see a Theme without publishing. Nobody else gets the preview; the published Theme renders for everyone else.
