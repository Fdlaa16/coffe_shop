<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            [
                'name'  => 'Iced Tea',
                'qty'   => '20',
                'type'  => 'drink',
                'price' => '10000'
            ],
            [
                'name'  => 'Matcha',
                'qty'   => '10',
                'type'  => 'drink',
                'price' => '12000'
            ],
            [
                'name'  => 'Chocolate',
                'qty'   => '30',
                'type'  => 'drink',
                'price' => '8000'
            ],
            [
                'name'  => 'Crossaint',
                'qty'   => '40',
                'type'  => 'food',
                'price' => '5000'
            ],
            [
                'name'  => 'Bread',
                'qty'   => '10',
                'type'  => 'food',
                'price' => '4000'
            ],
            [
                'name'  => 'Chicken Noodle',
                'qty'   => '30',
                'type'  => 'food',
                'price' => '20000'
            ]
        ];

        foreach ($menus as $index => $menu) {
            Menu::create($menu);
        }
    }
}
