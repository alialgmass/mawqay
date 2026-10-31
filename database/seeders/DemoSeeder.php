<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Auth\Enums\UserRole;
use Modules\Backoffice\Models\BlogPost;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;

/**
 * Local-only demo data: a Platform Admin, a demo Store and its Vendor Owner.
 * Every account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $store = Store::query()->updateOrCreate(
            ['slug' => 'demo-store'],
            ['name' => 'Demo Store', 'status' => StoreStatus::Active],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@mawqaay.test'],
            ['name' => 'Platform Admin', 'password' => 'password', 'role' => UserRole::Admin, 'store_id' => null],
        );

        User::query()->updateOrCreate(
            ['email' => 'owner@mawqaay.test'],
            ['name' => 'Demo Vendor Owner', 'password' => 'password', 'role' => UserRole::VendorOwner, 'store_id' => $store->id],
        );

        $this->seedBlogPosts();
    }

    private function seedBlogPosts(): void
    {
        $posts = [
            [
                'title' => 'Meet Mawqaay: the platform behind your modest-fashion store',
                'title_ar' => 'تعرف على مَوقعي: المنصة التي تقف خلف متجرك للأزياء المحتشمة',
                'slug' => 'meet-mawqaay',
                'excerpt' => 'One dashboard for products, orders, payments and payouts — built for merchants selling modest fashion in Arabic and English.',
                'excerpt_ar' => 'لوحة واحدة للمنتجات والطلبات والدفعات والأرباح — مصممة للتجار في مجال الأزياء المحتشمة بالعربية والإنجليزية.',
                'body' => "Mawqaay gives every merchant a bilingual storefront at their own address. Your customers browse in Arabic or English, while you run everything from one dashboard.\n\nProducts, categories and themes are managed with a few clicks. Manual bank transfers, reservations and payout statements are built in.\n\nNo plugins to install and no servers to babysit: you focus on fabric, sizes and seasonal collections.",
                'body_ar' => "تمنح مَوقعي كل تاجر واجهة متجر ثنائية اللغة على عنوانه الخاص. يتصفح زبائنك بالعربية أو الإنجليزية، بينما تدير كل شيء من لوحة واحدة.\n\nالمنتجات والفئات وقوالب التصميم تُدار بنقرات قليلة، والدفع عبر التحويل البنكي اليدوي والحجوزات وكشوف الأرباح مدمجة داخل المنصة.\n\nلا إضافات لتثبيتها ولا سيرفرات لإدارتها: عنك الاهتمام بالأقمشة والمقاسات ومجموعات المواسم.",
                'category' => 'product',
                'author_name' => 'The Mawqaay Team',
                'is_published' => true,
                'published_at' => now()->subDays(21),
            ],
            [
                'title' => 'From invitation email to first sale in one afternoon',
                'title_ar' => 'من رسالة الدعوة إلى أول عملية بيع في ظهيرة واحدة',
                'slug' => 'first-sale-afternoon',
                'excerpt' => 'A merchant story: how Nora opened her abaya store on Mawqaay, published a catalogue and got paid without touching any code.',
                'excerpt_ar' => 'قصة تجارة: كيف فتحت نور متجرها للعبايات على مَوقعي، ونشرت منتجاتها ووصلها أول دفعة دون كتابة أي سطر برمجي.',
                'body' => "Nora had beige cotton abayas stacked at home and buyers in three cities. Her problem was never the product — it was the plumbing: an Instagram inbox, spreadsheets and WhatsApp invoices.\n\nOn Mawqaay she picked a theme, uploaded 14 products with sizes and colors, and connected her bank account for manual transfers. The reservation timer does the chasing for her.\n\nHer first sale arrived the same evening. The money is tracked, the order is shipped, the customer is happy — and every payout is on the statement.",
                'body_ar' => "كانت لدى نور عبايات من القطن القواف والأبيس في بيتها ووظائف في ثلاث مدن. المشكلة لم تكن في المنتج، بل في التنظيم: صندوق إنستغرام، وجداول حسابات، وفواتير عبر واتساب.\n\nعلى مَوقعي اختارت نور قالب التصميم ورفعت 14 منتجاً بمقاساتها وألوانها وربطت حسابها البنكي للتحويلات اليدوية. فيساعدها مؤقت الحجز تلقائياً.\n\nوصلت أول عملية بيع بليلة واحدة. والتمويل مسجل والطلب مشحون والزبون من جنية السراء، وكل ربح على كشف الحساب.",
                'category' => 'merchant_story',
                'author_name' => 'Nora',
                'is_published' => true,
                'published_at' => now()->subDays(9),
            ],
            [
                'title' => 'Understanding manual payment reviews (and what speeds them up)',
                'title_ar' => 'فهم مراجعة الدفع اليدوي (وكيف تُسرّع العملية)',
                'slug' => 'manual-payment-reviews',
                'excerpt' => 'The reservation timer pauses while we review a receipt. Here is what that means for you and five habits that get orders paid faster.',
                'excerpt_ar' => 'يتوقف مؤقت الحجز أثناء مراجعة إثبات الدفع — إليك معناه لك وخمس عادات تجعل الطلبات يتم الدفع لها أسرع.',
                'body' => "Manual bank transfers keep you in control of your cash, and Mawqaay's payment review is what keeps them safe: every receipt is checked against the exact amount and reference.\n\nWhile the review runs, the stock reservation timer pauses. Nothing expires, nothing is snatched away — the reservation resumes when the review is done.\n\nTo move faster: quote the order number, upload a sharp photo of the transfer within minutes, and keep your phone number current.",
                'body_ar' => "التحويل البنكي اليدوي يبقيك متحكماً في أموالك، ومراجعة الدفع على مَوقعي هي التي تحفظ الأمان: كل إثبات تحويل يطابق مبلغاً محدداً ورقم قيد.\n\nأثناء المراجعة، يتوقف مؤقت الحجز على المنتجات: لا ينتهي الحجز ويُستبق أمرا ما، بل يعود مؤقت الحجز إلى العمل مرت فور انتهاء المراجعة.\n\nولتص حيث أسرع: اكتب رقم الطلب، وصور واضحة للتحويل خلال دقائق، وتأكد أن رقم هاتفك محفوظ صحيحاً.",
                'category' => 'guide',
                'author_name' => 'The Mawqaay Team',
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
        ];

        foreach ($posts as $attributes) {
            BlogPost::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);
        }
    }
}
