<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->createMany([
            ['name' => 'Pastel Frago com Queijo', 'price' => 15.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Pastéis'],
            ['name' => 'Pastel Carne com Queijo', 'price' => 15.25, 'photo' => 'photos/placeholder.jpg', 'type' => 'Pastéis'],
            ['name' => 'Pastel de Frango com Catupiry', 'price' => 15.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Pastéis'],
            ['name' => 'Pastel de Carne com Catupiry', 'price' => 15.50, 'photo' => 'photos/placeholder.jpg', 'type' => 'Pastéis'],
            ['name' => 'Coxinha Tradicional', 'price' => 10.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Coxinhas'],
            ['name' => 'Coxinha de Frango com Catupiry', 'price' => 10.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Coxinhas'],
            ['name' => 'Coxinha de Carne Seca', 'price' => 10.25, 'photo' => 'photos/placeholder.jpg', 'type' => 'Coxinhas'],
            ['name' => 'Coxinha de Palmito', 'price' => 10.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Coxinhas'],
            ['name' => 'Hambúrguer Clássico', 'price' => 20.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Hambúrgueres'],
            ['name' => 'Hambúrguer Cheddar Bacon', 'price' => 25.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Hambúrgueres'],
            ['name' => 'Hambúrguer Vegano', 'price' => 22.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Hambúrgueres'],
            ['name' => 'Batata Frita Grande', 'price' => 15.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Acompanhamentos'],
            ['name' => 'Batata Frita Pequena', 'price' => 10.00, 'photo' => 'photos/placeholder.jpg', 'type' => 'Acompanhamentos'],
        ]);
    }
}
