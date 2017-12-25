<?php

class ControllerExtensionPaymentPaymaster extends Controller
{
    const STATUS_TAX_OFF = 'no_vat';
    const MAX_POS_IN_CHECK = 100;
    const BEGIN_POS_IN_CHECK = 0;

    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');
        $data['action'] = 'https://paymaster.ru/Payment/Init';

        $this->load->language('extension/payment/paymaster');

        $this->load->model('extension/payment/paymaster');


        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $order_products = $this->cart->getProducts();

        //Продукты в заказе
        // Сумма для продуктов
        $product_amount = 0;

        if ($order_products) {
            foreach ($order_products as $order_product) {
                $data['order_check'][] = array(
                    'name'     => $order_product['name'],
                    'price'    => $order_product['price'],
                    'quantity' => $order_product['quantity'],
                    'tax'      => $this->config->get('tax_status') ? $this->getTax($order_product['product_id']) : self::STATUS_TAX_OFF,
                );

                $product_amount += $order_product['price'] * $order_product['quantity'];

            }
        }

        // Доставка товара
        // Так как мы не нашли как получить сумму доставки из заказа пришлось ее вычислять из закака

        $data['order_check'][] = array(
            'name'     => $order['shipping_method'],
            'price'    => $order['total'] - $product_amount,
            'quantity' => 1,
            'tax'      => self::STATUS_TAX_OFF
        );


        if (count($data['order_check']) > self::MAX_POS_IN_CHECK) {
            $data['error_warning'] = $this->language->get('error_max_pos');
        }

        $data['pos'] = self::BEGIN_POS_IN_CHECK;

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['merchant_id'] = $this->config->get('paymaster_merchant_id');
        $data['email'] = $order_info['email'];
        $data['order_id'] = $this->session->data['order_id'];
        $data['amount'] = number_format($order_info['total'], 2, ".", "");
        $data['lmi_currency'] = $order_info['currency_code'];
        $secret_key = htmlspecialchars_decode($this->config->get('paymaster_secret_key'));
        $hash_alg = $this->config->get('paymaster_hash_alg');

        $plain_sign = $data['merchant_id'] . $data['order_id'] . $data['amount'] . $data['lmi_currency'] . $secret_key;
        $data['sign'] = base64_encode(hash($hash_alg, $plain_sign, true));
        $data['description'] = $this->language->get('text_order') . ' ' . $data['order_id'];
        $this->createLog(__METHOD__, $data);

        return $this->load->view('extension/payment/paymaster', $data);
    }

    public function createLog($method, $data = array(), $text = '')
    {
        if ($this->config->get('paymaster_log')) {
            if ($method == 'index') {
                $order_check = array();
                foreach ($data['order_check'] as $check) {
                    $order_check = array(
                        'LMI_SHOPPINGCART.ITEMS[' . $check['pos'] . '].NAME'  => $check['name'],
                        'LMI_SHOPPINGCART.ITEMS[' . $check['pos'] . '].QTY'   => $check['quantity'],
                        'LMI_SHOPPINGCART.ITEMS[' . $check['pos'] . '].PRICE' => $check['price'],
                        'LMI_SHOPPINGCART.ITEMS[' . $check['pos'] . '].TAX'   => $check['tax'],
                    );
                }

                $data = array_merge(array(
                    'LMI_MERCHANT_ID'    => $data['merchant_id'],
                    'LMI_PAYMENT_AMOUNT' => $data['amount'],
                    'LMI_CURRENCY'       => $data['lmi_currency'],
                    'LMI_PAYMENT_NO'     => $data['order_id'],
                    'LMI_PAYMENT_DESC'   => $data['description'],
                    'SIGN'               => $data['sign'],
                ), $order_check);
            }

            $this->log->write('---------PAYMASTER START LOG---------');
            $this->log->write('---Вызываемый метод: ' . $method . '---');
            $this->log->write('---Описание: ' . $text . '---');
            $this->log->write($data);
            $this->log->write('---------PAYMASTER END LOG---------');
        }

        return true;
    }

    protected function getTax($product_id)
    {
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        $tax_rule_id = 3;

        foreach ($this->config->get('paymaster_classes') as $i => $tax_rule) {
            if ($tax_rule['paymaster_nalog'] == $product_info['tax_class_id']) {
                $tax_rule_id = $tax_rule['paymaster_tax_rule'];
            }
        }

        $tax_rules = array(
            array(
                'id'   => 0,
                'name' => 'vat18'
            ),
            array(
                'id'   => 1,
                'name' => 'vat10'
            ),
            array(
                'id'   => 2,
                'name' => 'vat0'
            ),
            array(
                'id'   => 3,
                'name' => 'no_vat'
            ),
            array(
                'id'   => 4,
                'name' => 'vat118'
            ),
            array(
                'id'   => 5,
                'name' => 'vat110'
            )
        );

        return $tax_rules[$tax_rule_id]['name'];

    }

    public function fail()
    {
        $this->createLog(__METHOD__, '', 'Платеж не выполнен');
        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
        return true;
    }

    public function success()
    {

        $order_id = $this->request->post["LMI_PAYMENT_NO"];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ((int)$order_info["order_status_id"] == (int)$this->config->get('paymaster_order_status_id')) {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('paymaster_order_status_id'), 'PayMaster', true);
            $this->createLog(__METHOD__, $this->request->post, 'Платеж успешно завершен');
            $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
            return true;
        }

        return $this->fail();
    }

    public function callback()
    {
        if (isset($this->request->post)) {
            $this->createLog(__METHOD__, $this->request->post, 'Данные с сервиса PAYMASTER');
        }

        $order_id = $this->request->post["LMI_PAYMENT_NO"];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $amount = number_format($order_info['total'], 2, '.', '');
        $merchant_id = $this->config->get('paymaster_merchant_id');

        if (isset($this->request->post['LMI_PREREQUEST'])) {
            if ($this->request->post['LMI_MERCHANT_ID'] == $merchant_id && $this->request->post['LMI_PAYMENT_AMOUNT'] == $amount) {
                echo 'YES';
                exit;
            } else {
                echo 'FAIL';
                exit;
            }
        } else {
            if (isset($this->request->post['LMI_HASH'])) {
                $lmi_hash_post = $this->request->post['LMI_HASH'];
                $lmi_sign = $this->request->post['SIGN'];

                $hash_alg = $this->config->get('paymaster_hash_alg');
                $secret_key = htmlspecialchars_decode($this->config->get('paymaster_secret_key'));
                $plain_string = $this->request->post["LMI_MERCHANT_ID"] . ";" . $this->request->post["LMI_PAYMENT_NO"] . ";";
                $plain_string .= ($this->request->post["LMI_SYS_PAYMENT_ID"] . ";" . $this->request->post["LMI_SYS_PAYMENT_DATE"] . ";");
                $plain_string .= ($this->request->post["LMI_PAYMENT_AMOUNT"] . ";" . $this->request->post["LMI_CURRENCY"] . ";" . $this->request->post["LMI_PAID_AMOUNT"] . ";");
                $plain_string .= ($this->request->post["LMI_PAID_CURRENCY"] . ";" . $this->request->post["LMI_PAYMENT_SYSTEM"] . ";");
                $plain_string .= ($this->request->post["LMI_SIM_MODE"] . ";" . $secret_key);

                $hash = base64_encode(hash($hash_alg, $plain_string, true));

                $plain_sign = $this->request->post["LMI_MERCHANT_ID"] . $this->request->post["LMI_PAYMENT_NO"] . $this->request->post["LMI_PAYMENT_AMOUNT"] . $this->request->post["LMI_CURRENCY"] . $secret_key;
                $sign = base64_encode(hash($hash_alg, $plain_sign, true));

                if ($lmi_hash_post == $hash && $lmi_sign == $sign) {
                    if ($order_info['order_status_id'] == 0) {
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('paymaster_order_status_id'), 'Оплачено через PayMaster');
                        exit;
                    }

                    if ($order_info['order_status_id'] != $this->config->get('paymaster_order_status_id')) {
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('paymaster_order_status_id'), 'PayMaster', true);
                    }
                } else {
                    $this->log->write("PayMaster sign is not correct!");
                }
            }
        }
    }
}

?>
