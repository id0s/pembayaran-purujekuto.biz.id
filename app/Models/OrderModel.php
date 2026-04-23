<?php namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model {
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $allowedFields = ['order_id', 'produk', 'total_bayar', 'status', 'created_at'];
}