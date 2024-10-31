<?php

class Segmetrics_Membermouse_Api {

    public function query( $request )
    {
        // Check for a custom function
        // ---------------------------------------------
        if(method_exists($this, 'get_'.$request['table'])){
            $_method = 'get_'.$request['table'];
            return $this->{$_method}( $request );
        }

        http_response_code(404);
        echo '404 Not Found';
        die();
    }

    private function get_products( $request )
    {
        $table = MM_TABLE_PRODUCTS;

        return $this->runQuery("SELECT id, name FROM {$table}");
    }

    /**
     * @deprecated
     *
     * We do this in get_purchases now. This is here for v1 backwards compatibility.
     *
     * @param $request
     * @return array
     */
    private function get_coupons( $request )
    {
        $table = MM_TABLE_COUPONS;

        return $this->runQuery("SELECT * FROM {$table}");
    }

    /**
     * @deprecated
     *
     * We do this in get_purchases now. This is here for v1 backwards compatibility.
     *
     * @param $request
     * @return array
     */
    private function get_coupon_usage( $request )
    {
        $table = MM_TABLE_COUPON_USAGE;

        return $this->runQuery("SELECT * FROM {$table}");
    }

    /**
     * Get the subscriptions and status
     * A lot of this logic is pulled straight from membermouse source.
     * @see subscirptionview.php:getViewData()
     *
     * @param $request
     * @return array
     */
    private function get_subscriptions( $request )
    {
        $transactionsTable      = MM_TABLE_TRANSACTION_LOG;
        $ordersTable          	= MM_TABLE_ORDERS;
        $orderItemsTable      	= MM_TABLE_ORDER_ITEMS;
        $orderItemAccessTable 	= MM_TABLE_ORDER_ITEM_ACCESS;
        $membershipLevelsTable 	= MM_TABLE_MEMBERSHIP_LEVELS;
        $bundlesTable 		   	= MM_TABLE_BUNDLES;
        $productsTable 			= MM_TABLE_PRODUCTS;
        $userDataTable          = MM_TABLE_USER_DATA;

        $accessTypeMembership = MM_OrderItemAccess::$ACCESS_TYPE_MEMBERSHIP;
        $accessTypeBundle = MM_OrderItemAccess::$ACCESS_TYPE_BUNDLE;

        $query = "
        SELECT
    oi.id as order_item_id,
    oi.is_recurring,
    oi.rebill_frequency,
    oi.status,
    oi.trial_duration,
    oi.trial_frequency,
    o.date_added,
    oi.item_id,
    oi.order_id,
    oi.recurring_amount,
    oi.quantity,
    oi.rebill_period,

    t.last_bill_date,

    ud.wp_user_id as user_id,
    ud.status as user_status,
    ud.became_active as user_became_active,
    ud.status_updated as user_status_updated,
    ud.cancellation_date as user_cancellation_date,
    ud.expiration_date as user_expiration_date,
    ud.last_updated as user_last_updated
FROM {$orderItemsTable} as oi
         INNER JOIN {$ordersTable} o ON (oi.order_id = o.id)
         LEFT JOIN {$productsTable} p ON (oi.item_id = p.id)
         LEFT JOIN {$orderItemAccessTable} oia ON (oi.id = oia.order_item_id)
         LEFT JOIN {$membershipLevelsTable} m ON ((oia.access_type = '{$accessTypeMembership}') AND (oia.access_type_id = m.id))
         LEFT JOIN {$bundlesTable} b ON ((oia.access_type = '{$accessTypeBundle}') AND (oia.access_type_id = b.id))

         INNER JOIN (
            SELECT    MAX(transaction_date) last_bill_date, order_id
            FROM      {$transactionsTable}
            GROUP BY  order_item_id
        ) t ON (t.order_id = oi.order_id)
        INNER JOIN {$userDataTable} ud on (ud.wp_user_id = o.user_id)
WHERE (oi.is_recurring=1) AND (oi.item_type=1)
";

        $allowedConditions = ['created_after'];
        $cond_query = $cond_values = [];

        foreach($this->filterConditions($request, $allowedConditions) as $key => $val){
            switch($key){
                case 'created_after':
                    $cond_query[] = "o.date_added >= %s";
                    $cond_values[] = $val;
                    break;
            }
        }

        if(!empty($cond_query)){
            $query .= ' AND ' . implode(' AND ', $cond_query) . ' ';
        }
        $query .= 'ORDER BY o.date_added ASC LIMIT 500';

        $data = $this->runQuery($query, array_values($cond_values));

        return $data;
    }

    /**
     * Get Invoices & Purchases from the Transaction table
     *
     * A lot of this logic is pulled straight from membermouse source.
     * @see transactionhistoryview.php:getViewData()
     *
     * @param $request
     * @return array
     */
    private function get_purchases( $request )
    {
        global $wpdb;

        $transactionsTable    = MM_TABLE_TRANSACTION_LOG;
        $paymentServicesTable = MM_TABLE_PAYMENT_SERVICES;
        $ordersTable          = MM_TABLE_ORDERS;
        $orderItemsTable      = MM_TABLE_ORDER_ITEMS;
        $userDataTable        = MM_TABLE_USER_DATA;

        $desiredTransactionTypes = implode(",",array(MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT,MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT));
        $refundTransactionType = MM_TransactionLog::$TRANSACTION_TYPE_REFUND;

        // First gather all the transactions we need.
        $query = "
SELECT o.id                                                                       as order_id,
       o.order_number,
       t.id,
       t.is_test,
       ABS(t.amount)                                                              as amount,
       t.currency,
       t.transaction_type,
       t.transaction_date,
       ps.name                                                                    as payment_service_name,
       ps.token                                                                   as payment_service_token,
       t.refund_id,
       if(((t.refund_id IS NOT NULL) AND (t.transaction_type != {$refundTransactionType})), true, false) as is_refunded,

       u.user_email,

       t.order_item_id,
       ifnull(oi.description, 'multiple products')                                as description,
       oi.item_id                                                                 as item_id,
       oi.quantity,
       oi.is_recurring,
       oi.max_rebills,
       ABS(oi.recurring_discount)                                                 as recurring_discount,
       ABS(o.shipping)                                                            as shipping,
       ABS(o.discount)                                                            as discount,
       ABS(o.tax)                                                                 as tax
FROM {$transactionsTable} t
         LEFT JOIN {$paymentServicesTable} ps on (t.payment_service_id = ps.id)
         LEFT JOIN {$ordersTable} o on (t.order_id = o.id)
         LEFT JOIN {$orderItemsTable} oi on (t.order_item_id = oi.id)
         INNER JOIN {$userDataTable} ud on (o.user_id = ud.wp_user_id)
         INNER JOIN {$wpdb->prefix}users u on (ud.wp_user_id = u.ID)
WHERE (t.transaction_type IN ({$desiredTransactionTypes}))
";


        $allowedConditions = ['created_after', 'email'];
        $cond_query = $cond_values = [];

        foreach($this->filterConditions($request, $allowedConditions) as $key => $val){
            switch($key){
                case 'created_after':
                    $cond_query[] = "t.transaction_date >= %s";
                    $cond_values[] = $val;
                    break;
                case 'email':
                    $cond_query[] = "u.user_email = %s";
                    $cond_values[] = $val;
                    break;
            }
        }

        if(!empty($cond_query)){
            $query .= ' AND ' . implode(' AND ', $cond_query) . ' ';
        }
        $query .= 'ORDER BY t.transaction_date ASC LIMIT 500';

        $data = $this->runQuery($query, array_values($cond_values));


        if(count($data['data'])) {
            $orderIndex = [];
            foreach ($data['data'] as $rowObject)
            {
                if (!is_null($rowObject->order_id))
                {
                    $orderIndex[$rowObject->order_id][] = $rowObject;
                }
            }

            $orderIdstring = implode(",",array_keys($orderIndex));

            // Get all coupons for the orders.
            $couponsTable = MM_TABLE_COUPONS;
            $couponType = MM_OrderItem::$ORDER_ITEM_TYPE_COUPON;

            $couponSql = "SELECT oi.order_id, oi.total as discount_amount, c.coupon_name, c.recurring_billing_setting FROM {$orderItemsTable} oi LEFT JOIN {$couponsTable} c ".
                "ON (oi.item_id = c.id) WHERE (oi.item_type = '{$couponType}') AND (oi.order_id IN ({$orderIdstring}))";
            $couponResults = $wpdb->get_results($couponSql);

            foreach ($couponResults as $additionalCouponData)
            {
                $currentOrderId = $additionalCouponData->order_id;
                foreach ($orderIndex[$currentOrderId] as $currentTransaction)
                {
                    switch ($currentTransaction->transaction_type)
                    {
                        case MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT:
                            $currentTransaction->coupon_applied = true;
                            $currentTransaction->coupon_name = $additionalCouponData->coupon_name;
                            $currentTransaction->coupon_discount = abs(floatval($additionalCouponData->discount_amount));
                            break;
                        case MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT:
                            if ($additionalCouponData->recurring_billing_setting == "all")
                            {
                                $currentTransaction->coupon_applied = true;
                                $currentTransaction->coupon_name = $additionalCouponData->coupon_name;
                                $currentTransaction->coupon_discount = "";

                                if(!is_null($currentTransaction->recurring_discount))
                                {
                                    $currentTransaction->coupon_discount = $currentTransaction->recurring_discount;
                                }
                                else
                                {
                                    $product = new MM_Product($currentTransaction->item_id);

                                    if($product->isValid())
                                    {
                                        $currentTransaction->coupon_discount = $product->getPrice(false) - abs(floatval($currentTransaction->amount));
                                    }
                                }
                                break;
                            }
                        //break is intentionally in the if. If that condition isnt matched, we want it to go to the default
                        default:
                            $currentTransaction->coupon_applied = false;
                            $currentTransaction->coupon_name = "";
                            $currentTransaction->coupon_discount = 0.00;
                            break;
                    }
                }
            }

            // Get all prorations for the orders.
            $prorationType = MM_OrderItem::$ORDER_ITEM_TYPE_PRORATION;
            $otherDiscountType  = MM_OrderItem::$ORDER_ITEM_TYPE_DISCOUNT;
            $prorationDiscountSql = "SELECT oi.order_id, oi.total as discount_amount,oi.item_type FROM {$orderItemsTable} oi WHERE (oi.item_type IN ('{$prorationType}','{$otherDiscountType}')) AND (oi.order_id IN ({$orderIdstring}))";
            $prorationDiscountResults = $wpdb->get_results($prorationDiscountSql);

            foreach ($prorationDiscountResults as $additionalTransactionData)
            {
                $currentOrderId = $additionalTransactionData->order_id;
                foreach ($orderIndex[$currentOrderId] as $currentTransaction)
                {
                    switch ($currentTransaction->transaction_type)
                    {
                        case MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT:
                            if ($additionalTransactionData->item_type == $prorationType)
                            {
                                $currentTransaction->proration_applied = true;
                                $currentTransaction->proration_amount = abs(floatval($additionalTransactionData->discount_amount));
                            }
                            else if ($additionalTransactionData->item_type == $otherDiscountType)
                            {
                                $currentTransaction->other_discount_applied = true;
                                $currentTransaction->other_discount_amount = abs(floatval($additionalTransactionData->discount_amount));
                            }
                            break;

                        case MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT:
                        default:
                            $currentTransaction->proration_applied = false;
                            $currentTransaction->proration_amount = 0.00;
                            $currentTransaction->other_discount_applied = false;
                            $currentTransaction->other_discount_amount = 0.00;
                            break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Run the query and return the required data
     * @param       $queryString
     * @param array $prepareValues
     */
    private function runQuery($queryString, array $prepareValues = [])
    {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare($queryString, $prepareValues));
        return [
            'data'     => $results,
            'count'    => $wpdb->num_rows,
            'has_more' => $wpdb->num_rows == 500,
            'api_version' => SEGMETRICS_MEMBERMOUSE_VERSION
        ];
    }

    /**
     * Filter out the conditions to only use the allowed ones
     *
     * @param WP_REST_Request $request
     * @param array           $validConditions
     * @return array|int
     */
    private function filterConditions(WP_REST_Request $request, array $validConditions)
    {
        return array_filter($request->get_params(), function($key) use ($validConditions){
            return in_array($key, $validConditions);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Check Authorization
     * @return bool
     */
    public function authorize()
    {
        $auth = get_option('seg_auth', [] );

        // Is Data Set
        if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW']) || empty($auth)){ return false; }
        return $auth['account_hash'] == $_SERVER['PHP_AUTH_USER'] && $auth['api_key'] == $_SERVER['PHP_AUTH_PW'];
    }
}