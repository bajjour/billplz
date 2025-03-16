<?php
namespace Billplz\Contracts;

interface BillplzInterface
{
    public function list_gateways(): array;
    public function createCollection(string $p_title, string $p_callback_url): array;
    public function getCollection(string $p_collection_id): array;
    public function paymentOrderLimit(): array;
    public function createPayment(array $p_data): array;
    public function getPayment(string $p_id): array;
}
