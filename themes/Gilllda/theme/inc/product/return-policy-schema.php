<?php
/**
 * Inject Return Policy, Shipping Details & Brand into Rank Math Product Schema (Fixed for Posts and WooCommerce)
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {

    // 🔴 شرط is_singular('product') حذف شد
    // حالا بررسی می‌کنیم: اگر رنک‌مث در حال ساخت اسکیمای "محصول" در این صفحه است، کد را اجرا کن
    if ( isset( $data['Product'] ) ) {

        // --- ۱. رفع هشدار برند ---
        if ( empty( $data['Product']['brand'] ) ) {
            $data['Product']['brand'] = [
                '@type' => 'Brand',
                'name'  => 'گیلدا' // نام برند شما
            ];
        }

        // --- ۲. فیلتر کردن دقیق Offers ---
        if ( isset( $data['Product']['offers'] ) ) {

            $return_policy = [
                '@type'                => 'MerchantReturnPolicy',
                'applicableCountry'    => 'IR',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays'   => 7, // مهلت ۷ روزه
                'returnMethod'         => 'https://schema.org/ReturnByMail',
                'returnFees'           => 'https://schema.org/CustomerResponsibility'
            ];

            $shipping_details = [
                '@type' => 'OfferShippingDetails',
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => 'IR'
                ],
                'shippingRate' => [
                    '@type'    => 'MonetaryAmount',
                    'value'    => 50000, // هزینه ارسال
                    'currency' => 'IRT'
                ],
                'deliveryTime' => [
                    '@type'        => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'd'
                    ],
                    'transitTime' => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 2,
                        'maxValue' => 4,
                        'unitCode' => 'd'
                    ]
                ]
            ];

            // تخصیص دادن اطلاعات به شکل رفرنسی (Reference) برای ویرایش مستقیم آرایه اصلی
            $offers = &$data['Product']['offers'];

            // بررسی هوشمند ساختار Offers در رنک‌مث
            if ( isset( $offers['@type'] ) && $offers['@type'] === 'AggregateOffer' && isset( $offers['offers'] ) && is_array( $offers['offers'] ) ) {

                // حالت اول: مقادیر متغیر (آرایه AggregateOffer)
                foreach ( $offers['offers'] as $k => $offer ) {
                    $offers['offers'][$k]['hasMerchantReturnPolicy'] = $return_policy;
                    $offers['offers'][$k]['shippingDetails'] = $shipping_details;
                }

            } elseif ( isset( $offers[0] ) && is_array( $offers[0] ) ) {

                // حالت دوم: لیست آرایه‌ای ساده از پیشنهادات
                foreach ( $offers as $k => $offer ) {
                    $offers[$k]['hasMerchantReturnPolicy'] = $return_policy;
                    $offers[$k]['shippingDetails'] = $shipping_details;
                }

            } else {

                // حالت سوم: محصول ساده (فقط یک پیشنهاد تک و منفرد)
                $offers['hasMerchantReturnPolicy'] = $return_policy;
                $offers['shippingDetails'] = $shipping_details;

            }
        }
    }

    return $data;
}, 99, 2 );