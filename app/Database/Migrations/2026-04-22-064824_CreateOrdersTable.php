<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
        'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
        'order_id'    => ['type' => 'VARCHAR', 'constraint' => 20],
        'produk'      => ['type' => 'VARCHAR', 'constraint' => 100],
        'total_bayar' => ['type' => 'INT', 'constraint' => 11], // Berisi Harga + Kode Unik
        'status'      => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'expired'], 'default' => 'pending'],
        'created_at'  => ['type' => 'DATETIME', 'null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->createTable('orders');    }

    public function down()
    {
        //
    }
}
