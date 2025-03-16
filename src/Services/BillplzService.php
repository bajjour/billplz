<?php
namespace Billplz\Services;

use Billplz\Contracts\BillplzInterface;
use Billplz\Exceptions\BillplzException;
use Billplz\Exceptions\BillplzNotSupportedInVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class BillplzService implements BillplzInterface
{
    private string $apiKey;
    private string $x_signature;
    private string $apiUrl;
    private string $apiVersion;

    public function __construct(string $apiKey, string $apiUrl, string $x_signature, string $apiVersion)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->x_signature = $x_signature;
        $this->apiVersion = $apiVersion;
    }

    /**
     * List Supported Payment Gateways
     */
    public function list_gateways(): array
    {
        if ($this->apiVersion === 'v3')
            return $this->get_payment_gateways_v3();

        return $this->get_payment_gateways_v5();
    }

    /**
     * Create a new Collection
     */
    public function createCollection(string $p_title, string $p_callback_url): array
    {
        if ($this->apiVersion === 'v3')
            return $this->create_collection_v3($p_title);

        return $this->create_collection_v5($p_title, $p_callback_url);

    }

    /**
     * Get Collection details
     */
    public function getCollection(string $p_collection_id): array
    {
        if ($this->apiVersion === 'v3')
            return $this->get_collection_v3($p_collection_id);

        return $this->get_collection_v5($p_collection_id);
    }

    /**
     * get Current Payment Order Limit
     * supported only in v5
     * @throws BillplzException
     */
    public function paymentOrderLimit(): array
    {
        if ($this->apiVersion === 'v3')
            throw new BillplzException('paymentOrderLimit not supported in v3');

        return $this->get_payment_order_limit_v5();
    }

    /**
     * Create a Payment Order
     * @throws BillplzException
     */
    public function createPayment(array $p_data): array
    {
        if ($this->apiVersion === 'v3')
            return $this->create_bill_v3($p_data);

        return $this->create_payment_v5($p_data);

    }

    /**
     * Get Collection details
     */
    public function getPayment(string $p_id): array
    {
        if ($this->apiVersion === 'v3')
            return $this->get_bill_v3($p_id);

        return $this->get_payment_v5($p_id);
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->acceptJson()
            ->{$method}("{$this->apiUrl}{$endpoint}", $data);

        return $response->json();

    }

    private function checkMissingKeys(array $data, array $requiredKeys): string
    {
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!Arr::has($data, $key)) {
                $missingKeys[] = $key;
            }
        }

        return implode(', ', $missingKeys);
    }

    private function get_payment_gateways_v3() {
        if (Cache::has('bpz_fpx_banks'))
            return Cache::get('bpz_fpx_banks');

        $res = $this->request('get', '/v3/fpx_banks', []);

        Cache::put('bpz_fpx_banks', $res, Carbon::now()->addDay());

        return Cache::get('bpz_fpx_banks');
    }

    private function get_payment_gateways_v5() {
        if (Cache::has('bpz_gateways_list'))
            return Cache::get('bpz_gateways_list');

        $epoch = strtotime(Carbon::now());
        $checksum = hash_hmac('sha512', $epoch, $this->x_signature);
        $data = [
            'epoch' => $epoch,
            'checksum' => $checksum,
        ];
        $res = $this->request('get', '/v5/payment_gateways', $data);

        Cache::put('bpz_gateways_list', $res, Carbon::now()->addDay());

        return Cache::get('bpz_gateways_list');
    }

    private function create_collection_v3($p_title): array
    {
        $data = ['title' => $p_title];
        return $this->request('POST', '/v3/collections', $data);
    }

    private function create_collection_v5($p_title, $p_callback_url): array
    {
        $epoch = strtotime(Carbon::now());
        $data_str = $p_title . $p_callback_url . $epoch;
        $checksum = hash_hmac('sha512', $data_str, $this->x_signature);

        $data = [
            'title' => $p_title,
            'callback_url' => $p_callback_url,
            'epoch' => $epoch,
            'checksum' => $checksum,
        ];

        return $this->request('POST', '/v5/payment_order_collections', $data);
    }

    private function get_collection_v3($p_collection_id): array
    {
        return $this->request('GET', '/v3/collections/' . $p_collection_id, []);
    }

    private function get_collection_v5($p_collection_id): array
    {
        $epoch = strtotime(Carbon::now());
        $data_str = $p_collection_id . $epoch;
        $checksum = hash_hmac('sha512', $data_str, $this->x_signature);

        $data = [
            'epoch' => $epoch,
            'checksum' => $checksum,
        ];

        return $this->request('GET', '/v5/payment_order_collections/' . $p_collection_id, $data);
    }

    private function get_payment_order_limit_v5() : array {
        $epoch = strtotime(Carbon::now());
        $checksum = hash_hmac('sha512', $epoch, $this->x_signature);

        $data = [
            'epoch' => $epoch,
            'checksum' => $checksum,
        ];

        return $this->request('GET', '/v5/payment_order_limit/', $data);
    }

    /**
     * @throws BillplzException
     */
    private function create_bill_v3($p_data): array
    {
        $missingKeys = $this->checkMissingKeys($p_data, [
            'collection_id', 'email', 'name', 'amount', 'callback_url', 'description'
        ]);

        if (!empty($missingKeys)) {
            throw new BillplzException($missingKeys . ' parameters are required');
        }

        return $this->request('POST', '/v3/bills', $p_data);
    }

    /**
     * @throws BillplzException
     */
    private function create_payment_v5($p_data): array
    {
        $missingKeys = $this->checkMissingKeys($p_data, [
            'payment_order_collection_id', 'bank_code', 'bank_account_number', 'name', 'description', 'total'
        ]);

        if (!empty($missingKeys)) {
            throw new BillplzException($missingKeys . ' parameters are required');
        }

        $epoch = strtotime(Carbon::now());
        $data_str = $p_data['payment_order_collection_id'] . $p_data['bank_account_number'] . $p_data['total'] . $epoch;
        $checksum = hash_hmac('sha512', $data_str, $this->x_signature);

        $p_data['epoch'] = $epoch;
        $p_data['checksum'] = $checksum;

        return $this->request('POST', '/v5/payment_orders', $p_data);
    }

    private function get_bill_v3($p_bill_id): array
    {
        return $this->request('GET', '/v3/bills/' . $p_bill_id, []);
    }

    private function get_payment_v5($p_payment_id): array
    {
        $epoch = strtotime(Carbon::now());
        $data_str = $p_payment_id . $epoch;
        $checksum = hash_hmac('sha512', $data_str, $this->x_signature);

        $data = [
            'epoch' => $epoch,
            'checksum' => $checksum,
        ];

        return $this->request('GET', '/v5/payment_orders/' . $p_payment_id, $data);
    }

}
