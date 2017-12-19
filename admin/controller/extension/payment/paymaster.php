<?php
/**
 * Платежная система PayMaster - онлайн касса и интеграция различных способов оплаты
 *
 * @cms    Opencart
 * @author     dev@agaxx.ru (Alexey Agafonov)
 * @version    3.1.0
 * @license
 * @copyright  Copyright (c) 2017 Wallet One (http://www.paymaster.ru)
 */


class ControllerExtensionPaymentPayMaster extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/paymaster');
        $this->document->setTitle = $this->language->get('heading_title');
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('paymaster', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
        }
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_card'] = $this->language->get('text_card');

        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_secret_key'] = $this->language->get('entry_secret_key');
        $data['entry_hash_alg'] = $this->language->get('entry_hash_alg');

        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_tax'] = $this->language->get('entry_tax');
        $data['entry_log'] = $this->language->get('entry_log');
        $data['entry_class_tax'] = $this->language->get('entry_class_tax');
        $data['entry_text_tax'] = $this->language->get('entry_text_tax');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['merchant_id'])) {
            $data['error_merchant_id'] = $this->error['merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/paymaster', 'token=' . $this->session->data['token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/paymaster', 'token=' . $this->session->data['token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);


        if (isset($this->request->post['paymaster_merchant_id'])) {
            $data['paymaster_merchant_id'] = $this->request->post['paymaster_merchant_id'];
        } else {
            $data['paymaster_merchant_id'] = $this->config->get('paymaster_merchant_id');
        }

        if (isset($this->request->post['paymaster_secret_key'])) {
            $data['paymaster_secret_key'] = $this->request->post['paymaster_secret_key'];
        } else {
            $data['paymaster_secret_key'] = $this->config->get('paymaster_secret_key');
        }

        if (isset($this->request->post['paymaster_hash_alg'])) {
            $data['paymaster_hash_alg'] = $this->request->post['paymaster_hash_alg'];
        } else {
            $data['paymaster_hash_alg'] = $this->config->get('paymaster_hash_alg');
        }

        if (isset($this->request->post['paymaster_order_status_id'])) {
            $data['paymaster_order_status_id'] = $this->request->post['paymaster_order_status_id'];
        } else {
            $data['paymaster_order_status_id'] = $this->config->get('paymaster_order_status_id');
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['paymaster_geo_zone_id'])) {
            $data['paymaster_geo_zone_id'] = $this->request->post['paymaster_geo_zone_id'];
        } else {
            $data['paymaster_geo_zone_id'] = $this->config->get('paymaster_geo_zone_id');
        }

        if (isset($this->request->post['paymaster_log'])) {
            $data['paymaster_log'] = $this->request->post['paymaster_log'];
        } else {
            $data['paymaster_log'] = $this->config->get('paymaster_log');
        }

        if (isset($this->request->post['paymaster_classes'])) {
            $data['paymaster_classes'] = $this->request->post['paymaster_classes'];
        } elseif ($this->config->get('paymaster_classes')) {
            $data['paymaster_classes'] = $this->config->get('paymaster_classes');
        } else {
            $data['paymaster_classes'] = array(
                array(
                    'paymaster_nalog' => 1,
                    'paymaster_tax_rule' => 1
                )
            );
        }

        $data['tax_rules'] = array(
            array(
                'id' => 0,
                'name' => '18'
            ),
            array(
                'id' => 1,
                'name' => '10'
            ),
            array(
                'id' => 2,
                'name' => '0'
            ),
            array(
                'id' => 3,
                'name' => 'no'
            ),
            array(
                'id' => 4,
                'name' => '18/118'
            ),
            array(
                'id' => 5,
                'name' => '10/110'
            )
        );

        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();


        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['paymaster_status'])) {
            $data['paymaster_status'] = $this->request->post['paymaster_status'];
        } else {
            $data['paymaster_status'] = $this->config->get('paymaster_status');
        }

        if (isset($this->request->post['paymaster_sort_order'])) {
            $data['paymaster_sort_order'] = $this->request->post['paymaster_sort_order'];
        } else {
            $data['paymaster_sort_order'] = $this->config->get('paymaster_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paymaster.tpl', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/paymaster')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['paymaster_merchant_id']) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }

        if (!$this->request->post['paymaster_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

