# Mawqaay — Multi-Tenant Modest Fashion Commerce Platform

A SaaS platform where independent vendors of modest fashion (hijab wear) in Egypt each run their own branded online Store. The Platform collects every payment manually, keeps a Commission, and owes the remainder to the Vendor.

This is the canonical glossary for the product described in `PRD — Multi-Tenant Modest Fashion Commerce Platform.md` and `project-phases-ai-prompts.md`. It defines what terms **mean**, not how they are built. English is canonical; the Arabic label follows each term. The phase plan wins over the PRD when the two disagree.

> Note: the repo-root `CONTEXT.md` is the glossary of a different product (THOB, a B2B textile marketplace). Do not merge the two vocabularies; terms such as "Customer" and "Supplier" mean different things there.

## Language

### Tenancy and actors

**Platform** — <span dir="rtl">المنصة</span>
The operator of the whole system. It collects payments, takes Commission, and owes the rest to Vendors.
_Avoid_: Marketplace (there is no cross-store search), Company

**Platform Admin** — <span dir="rtl">أدمن المنصة</span>
A person on the Platform's team who creates Stores, reviews Payments, issues Refunds and records Payouts. The only actor who can see all Stores.
_Avoid_: Superadmin, Operator

**Store** — <span dir="rtl">المتجر</span>
The tenant: one Vendor's isolated shop with its own identity, Theme, catalog, customers and orders, reached at `/{store-slug}`. Every store-owned record belongs to exactly one Store.
_Avoid_: Shop, Tenant (in user-facing writing), Site

**Vendor** — <span dir="rtl">التاجر</span>
The business that owns and operates a Store and is owed money by the Platform. A Vendor is a party, not a place: the Store is the shop, the Vendor is the business behind it.
_Avoid_: Seller, Merchant, Store (when you mean the business)

**Vendor Owner** — <span dir="rtl">مالك المتجر</span>
The single person who logs in on behalf of a Vendor. Belongs to exactly one Store. Staff roles are V2.
_Avoid_: Vendor user, Merchant

**Customer** — <span dir="rtl">العميلة</span>
A person with an account inside one Store, who buys from that Store only. The same person in two Stores is two unrelated Customers.
_Avoid_: Buyer, User, Shopper

**Customer Identifier** — <span dir="rtl">معرّف العميلة</span>
The email or mobile number a Customer registers and logs in with. At least one is required, and each is unique within its Store.
_Avoid_: Username

**Store Slug** — <span dir="rtl">رابط المتجر</span>
The short URL name that identifies a Store. Fixed after launch; a change requires a Platform Admin and leaves a permanent redirect from the old one.
_Avoid_: Handle, Subdomain

**Reserved Slug** — <span dir="rtl">رابط محجوز</span>
A Store Slug that may never be used because it collides with a platform route.

**Store Status** — <span dir="rtl">حالة المتجر</span>
One of Draft (not public), Active (open), Suspended (maintenance page; new sales and payouts blocked, in-flight Orders continue).

### Surfaces

**Storefront** — <span dir="rtl">واجهة المتجر</span>
The public, themed shopping site of one Store. Arabic, right-to-left, mobile first.

**Admin Panel** — <span dir="rtl">لوحة الأدمن</span>
The Platform Admin's management area at `/admin`.

**Vendor Panel** — <span dir="rtl">لوحة التاجر</span>
The Vendor Owner's management area at `/vendor`, limited to their own Store.

**Theme** — <span dir="rtl">الثيم</span>
A complete visual layout for a Storefront, selectable per Store, that receives its data only through a fixed, versioned contract.
_Avoid_: Template, Skin

**Theme Settings** — <span dir="rtl">إعدادات الثيم</span>
The Vendor's customisations of a Theme: primary and secondary colour, fonts from an approved list, and corner style.

### Catalog

**Category** — <span dir="rtl">التصنيف</span>
A two-level grouping of Products inside one Store.

**Product** — <span dir="rtl">المنتج</span>
An item a Store sells, with name, description, images and price. Published, Draft, or hidden by the Platform Admin.

**Variant** — <span dir="rtl">الاختيار (مقاس/لون)</span>
A purchasable combination of size and colour of a Product, with its own SKU and stock.
_Avoid_: Option, SKU (the SKU is only the Variant's code)

**Stock Reservation** — <span dir="rtl">حجز المخزون</span>
Stock held for an Order until it is paid, released, or committed.
_Avoid_: Hold, Lock

### Selling

**Cart** — <span dir="rtl">السلة</span>
A Customer's or guest's pending selection of Variants inside one Store. Never spans Stores.

**Order** — <span dir="rtl">الطلب</span>
A Customer's purchase from exactly one Store, with a snapshot of prices, names and address taken at purchase time.
_Avoid_: Purchase, Transaction

**Order Item** — <span dir="rtl">بند الطلب</span>
One Variant and quantity in an Order, carrying its frozen Commission Rate and Commission Amount.

**Order Status** — <span dir="rtl">حالة الطلب</span>
Where an Order is in its lifecycle: pending payment, payment review, paid, processing, shipped, delivered, completed, cancelled, refunded.

**Shipping Fee** — <span dir="rtl">رسوم الشحن</span>
The amount the Vendor charges for delivery, flat or by governorate. Owned entirely by the Vendor.

**Shipment** — <span dir="rtl">الشحنة</span>
The Vendor's manual record of a courier name and tracking number for an Order.

**Refund** — <span dir="rtl">الاسترجاع</span>
Money returned to a Customer after payment, full or partial. Initiated only by a Platform Admin.
_Avoid_: Return (a Return is the goods coming back, which is not modelled)

**Return Window** — <span dir="rtl">فترة الاسترجاع</span>
The days after delivery during which a Vendor's money stays Pending. Starts at the first of the Vendor marking Delivered or the Customer confirming receipt.

**Refund Reason** — <span dir="rtl">سبب الاسترجاع</span>
One of a fixed list: wrong size, changed mind, defective, not delivered, other.

### Payment

**Payment Method** — <span dir="rtl">طريقة الدفع</span>
A Platform-owned account (Vodafone Cash or InstaPay) that Customers transfer to.

**Payment** — <span dir="rtl">الدفعة</span>
The Customer's claim that they transferred an Order's total to the Platform, supported by a Receipt.

**Receipt** — <span dir="rtl">الإيصال</span>
The image or PDF a Customer uploads as proof of transfer. Visible only to Platform Admins and the owning Customer, never to the Vendor.
_Avoid_: Proof, Slip

**Payment Attempt** — <span dir="rtl">محاولة الدفع</span>
One Receipt submission for an Order's Payment. An Order allows three in total.

**Payment Review** — <span dir="rtl">مراجعة الدفع</span>
The Platform Admin's decision to approve or reject a submitted Receipt.

**Reservation Window** — <span dir="rtl">مهلة الحجز</span>
How long an unpaid Order holds its Stock Reservation before it is cancelled and the stock released.

### Money

**Commission** — <span dir="rtl">العمولة</span>
The Platform's share of an Order, a percentage of product lines only (never Shipping Fee).

**Commission Rate** — <span dir="rtl">نسبة العمولة</span>
The percentage applied: the Store's own rate if set, otherwise the Platform default.

**Ledger Entry** — <span dir="rtl">قيد الدفتر</span>
An immutable debit or credit recording money moving between Platform accounts. Corrections are new reversing entries, never edits.
_Avoid_: Transaction, Balance row

**Payable** — <span dir="rtl">مستحق التاجر</span>
What the Platform owes a Vendor, in one of three states: Pending (not yet safe to pay), Available (payable now), Paid.

**Payout** — <span dir="rtl">الصرف</span>
A Vendor's request for Available money and the Platform Admin's manual transfer of it, recorded with a transfer reference.
_Avoid_: Withdrawal, Settlement

**Reconciliation** — <span dir="rtl">المطابقة</span>
The daily check that Platform cash equals Payables plus Commission revenue.

### Feedback

**Satisfaction Survey** — <span dir="rtl">استبيان الرضا</span>
A one-time rating form sent to a Customer a set number of days after delivery.

**Low Rating** — <span dir="rtl">تقييم منخفض</span>
An overall Survey rating of 2 or below, which alerts the Platform Admin and the Vendor.
