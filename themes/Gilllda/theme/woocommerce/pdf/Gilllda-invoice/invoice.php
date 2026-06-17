<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>

    <div class="digikala-invoice">

        <table class="header-table">
            <tr>
                <td class="header-right">
                    <strong><?php bloginfo('name'); ?></strong><br>
                    وب‌سایت: <?php echo str_replace(array('https://', 'http://'), '', home_url()); ?><br>
                    ایمیل: <?php echo get_option('admin_email'); ?>
                </td>
                <td class="header-center">
                    <?php
                    // مهار قطعی اندازه لوگو
                    $logo_id = $this->get_header_logo_id();
                    if ( $logo_id ) {
                        $logo_src = wp_get_attachment_image_src( $logo_id, 'full' );
                        $logo_url = $logo_src ? $logo_src[0] : '';
                        if ( !empty($logo_url) ) {
                            echo '<img src="' . esc_url($logo_url) . '" height="40" style="height:40px; width:auto; display:block; margin:0 auto;" alt="Logo" />';
                        }
                    } else {
                        echo '<h2>' . $this->get_title() . '</h2>';
                    }
                    ?>
                </td>
                <td class="header-left">
                    تاریخ چاپ: <?php echo date_i18n('H:i d-m-Y'); ?><br>
                    شناسه سفارش: <?php $this->order_number(); ?>
                </td>
            </tr>
        </table>

        <div class="shop-address-bar">
            <strong>آدرس فروشنده:</strong>
            <?php
            $shop_state     = WC()->countries->get_base_state();
            $shop_states    = WC()->countries->get_states(WC()->countries->get_base_country());
            $shop_state_name= isset($shop_states[$shop_state]) ? $shop_states[$shop_state] : $shop_state;
            $shop_city      = WC()->countries->get_base_city();
            $shop_addr1     = WC()->countries->get_base_address();
            $shop_addr2     = WC()->countries->get_base_address_2();
            $shop_postcode  = WC()->countries->get_base_postcode();

            $shop_line = array_filter(array($shop_state_name, $shop_city, $shop_addr1, $shop_addr2));
            echo implode(' - ', $shop_line);
            if(!empty($shop_postcode)) { echo ' (کدپستی: ' . $shop_postcode . ')'; }
            ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>تلفن فروشگاه:</strong>
            <span dir="ltr">
			<?php
            // دریافت تلفن فروشگاه از تنظیمات افزونه یا تنظیمات عمومی ووکامرس
            $general_settings = get_option('wpo_wcpdf_settings_general');
            echo isset($general_settings['shop_phone']) ? $general_settings['shop_phone'] : get_option('woocommerce_store_phone', '-');
            ?>
		</span>
        </div>

        <div class="customer-info-bar">
            <strong>خریدار:</strong> <?php echo $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(); ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>آدرس:</strong>
            <?php
            $order = $this->order;
            $cust_state_code = $order->get_billing_state();
            $cust_states     = WC()->countries->get_states('IR');
            $cust_state_name = isset($cust_states[$cust_state_code]) ? $cust_states[$cust_state_code] : $cust_state_code;
            $cust_city       = $order->get_billing_city();
            $cust_addr1      = $order->get_billing_address_1();
            $cust_addr2      = $order->get_billing_address_2();
            $cust_postcode   = $order->get_billing_postcode();

            $customer_line = array_filter(array($cust_state_name, $cust_city, $cust_addr1, $cust_addr2));
            echo implode(' - ', $customer_line);
            if(!empty($cust_postcode)) { echo ' (کدپستی: ' . $cust_postcode . ')'; }
            ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>تلفن خریدار:</strong> <span dir="ltr" style="font-weight: bold;"><?php echo $order->get_billing_phone(); ?></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>تاریخ سفارش:</strong> <?php $this->order_date(); ?>
        </div>

        <?php do_action( 'wpo_wcpdf_before_order_details', $this->type, $this->order ); ?>

        <table class="order-details">
            <thead>
            <tr>
                <th style="width: 5%;">ردیف</th>
                <th style="width: 12%;">شناسه</th>
                <th style="width: 43%;">محصول</th>
                <th style="width: 12%;">قیمت واحد اصلی</th>
                <th style="width: 10%;">تخفیف</th>
                <th style="width: 8%;">مالیات</th>
                <th style="width: 4%;">تعداد</th>
                <th style="width: 12%;">مبلغ کل</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $items = $this->get_order_items();
            $i = 1;
            $total_qty = 0;
            if( sizeof( $items ) > 0 ) : foreach( $items as $item_id => $item ) :

                $product = $item['product'];
                $sku = $product ? $product->get_sku() : '-';
                $qty = $item['quantity'];
                $total_qty += $qty;
                $order_item = $item['item'];

                $line_total = (float) $order_item->get_total();
                $line_subtotal = (float) $order_item->get_subtotal();
                $tax = (float) $order_item->get_total_tax();

                // استخراج قیمت اصلی و محاسبات دقیق حراجی‌ها و کدهای تخفیف
                $regular_price = $product ? (float)$product->get_regular_price() : 0;
                if ( $regular_price == 0 ) {
                    $regular_price = $qty > 0 ? ($line_subtotal / $qty) : 0;
                }

                $total_discount = ($regular_price * $qty) - $line_total;
                if ( $total_discount < 0 ) $total_discount = 0;
                ?>
                <tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, $this->type, $this->order, $item_id ); ?>">
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td class="text-center"><?php echo $sku; ?></td>
                    <td style="line-height: 1.4;">
                        <span class="item-name" style="font-weight:bold;"><?php echo $item['name']; ?></span><br>
                        <span class="item-meta" style="font-size: 7.5pt; color: #555;"><?php echo $item['meta']; ?></span>
                    </td>
                    <td class="text-center"><?php echo wc_price($regular_price); ?></td>
                    <td class="text-center"><?php echo $total_discount > 0 ? wc_price($total_discount) : '۰'; ?></td>
                    <td class="text-center"><?php echo $tax > 0 ? wc_price($tax) : '۰'; ?></td>
                    <td class="text-center"><?php echo $qty; ?></td>
                    <td class="text-center"><strong><?php echo wc_price($line_total + $tax); ?></strong></td>
                </tr>
            <?php endforeach; endif; ?>

            <tr class="table-footer-row">
                <td colspan="6" style="text-align: left; padding-left:10px;"><strong>کل</strong></td>
                <td class="text-center"><strong><?php echo $total_qty; ?></strong></td>
                <td class="text-center"><strong><?php echo $this->order->get_formatted_order_total(); ?></strong></td>
            </tr>
            </tbody>
        </table>

        <table class="bottom-section" style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="width: 40%; vertical-align: top; text-align:right;">
                    <div style="font-size: 9pt;"><strong>تعداد کل آیتم‌ها: </strong> <?php echo $total_qty; ?></div>
                </td>
                <td style="width: 60%; vertical-align: top;">
                    <table class="totals-table" style="width: 100%; border-collapse: collapse; float: left;">
                        <?php foreach( $this->get_woocommerce_totals() as $key => $total ) : ?>
                            <tr class="<?php echo $key; ?>">
                                <td class="description" style="border: 1px solid #000; padding: 6px; text-align: center; background: #f0f0f0; width: 40%; font-weight:bold;"><?php echo $total['label']; ?></td>
                                <td class="price" style="border: 1px solid #000; padding: 6px; text-align: center; background: #fff; width: 60%;"><?php echo $total['value']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>
            </tr>
        </table>

        <table class="signatures" style="width: 100%; margin-top: 50px; font-size: 10pt;">
            <tr>
                <td style="text-align: right; width: 50%; padding-right: 20px;"><strong>امضا و مهر مشتری</strong></td>
                <td style="text-align: left; width: 50%; padding-left: 20px;"><strong>امضا و مهر فروشگاه</strong></td>
            </tr>
        </table>

    </div>

<?php do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>