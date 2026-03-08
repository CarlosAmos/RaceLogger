<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\World;
use App\Models\Driver;
use App\Models\Country;

class F224DriverSeeder extends Seeder
{
    public function run()
    {
        $worlds = World::all();
        if (!$worlds) return;

        $drivers = [            
            ['Victor','Martins','FRA'],
            ['Zak',"O'Sullivan",'GBR'],
            ['Luke','Browning','GBR'],
            ['Oliver','Bearman','GBR'],
            ['Gabriele','Minì','ITA'],
            ['Andrea Kimi','Antonelli','ITA'],
            ['Zane','Maloney','BRB'],
            ['Leonardo','Fornaroli','ITA'],
            ['Ritomo','Miyata','JPN'],
            ['Jak','Crawford','USA'],
            ['Juan Manuel','Correa','USA'],
            ['Dino','Begonovic','SWE'],
            ['Kush','Maini','IND'],
            ['Gabriel','Bortoleto','BRA'],
            ['Dennis','Hauger','NOR'],
            ['Richard','Verschoor','NLD'],
            ['Franco','Colapinto','ARG'],
            ['Oliver','Goethe','DEU'],
            ['Enzo','Fittipaldi','BRA'],
            ['John','Bennett','GBR'],
            ['Rafael','Villagómez','MEX'],
            ['Amaury','Cordeel','BEL'],
            ['Paul','Aron','EST'],
            ['Isack','Hadjar','FRA'],
            ['Pepe','Martí','ESP'],
            ['Richard','Verschoor','NLD'],
            ['Max','Esterson','USA'],
            ['Roman','Staněk','CZE'],
            ['Christian','Mansell','AUS'],
            ['Joshua','Dürksen','PRY'],
            ['Taylor','Barnard','GBR'],
            ['Niels','Koolen','NLD'],
            ['Cian','Shields','GBR']            
        ];


        foreach ($worlds as $world) {
            foreach ($drivers as [$first, $last, $iso]) {
                $country = Country::where('iso_code', $iso)->first();
                if (!$country) continue;
                Driver::updateOrCreate(
                    [
                        'world_id' => $world->id,
                        'first_name' => $first,
                        'last_name' => $last,
                    ],
                    [
                        'country_id' => $country->id,
                    ]
                );
            }
        }
    }
}