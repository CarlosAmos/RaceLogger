<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\World;
use App\Models\Driver;
use App\Models\Country;

class Drivers2017 extends Seeder
{
    public function run()
    {
        $worlds = World::all();
        if (!$worlds) return;

        $drivers = 
        [
        ['Garry','Jacobson','AUS'],
        ['Josh','Kean','AUS'],
        ['Richie','Stanaway','NZL'],
        ['Anton','De Pasquale','AUS'],
        ['Bryce','Fullwood','AUS'],
        ['Matt','Palmer','AUS'],
        ['Gerard','McLeod','AUS'],
        ['Brodie','Kostecki','AUS'],
        ['Jack','Perkins','AUS'],
        ['Renee','Gracie','AUS'],
        ['Jordan','Boys','AUS'],
        ['Andrew','Jones','AUS'],
        ['Macauley','Jones','AUS'],
        ['Jack','Smith','AUS'],
        ['Adam','Marjoram','AUS'],
        ['Todd','Hazelwood','AUS'],
        ['Matt','Chahda','AUS'],
        ['Will','Brown','AUS'],
        ['Nathan','Morcom','AUS'],
        ['Paul','Dumbrell','AUS'],
        ['Richard','Muscat','AUS'],
        ['Mason','Barbera','AUS'],
        ['Kurt','Kostecki','AUS'],
        ['Jake','Kostecki','AUS'],
        ['Shae','Davies','AUS'],
        ['Jack','Le Brocq','AUS'],
        ['Chris','Pither','NZL'],
        ['Ash','Samadi','AUS'],
        ['Grant','Denyer','AUS'],
        ['Greg','Taylor','AUS'],
        ['Adrian','Deitz','AUS'],
        ['Cameron','McConville','AUS'],
        ['Tony','Quinn','GBR'],
        ['Mike','Whiddett','NZL'],
        ['Jonny','Reid','NZL'],
        ['Andrew','Waite','AUS'],
        ['Shane','van Gisbergen','NZL'],
        ['Tony',"D'Alberto",'AUS'],
        ['Max','Twigg','AUS'],
        ['Craig','Baird','NZL'],
        ['Scott','Taylor','AUS'],
        ['Mark','Griffith','AUS'],
        ['Jake','Camilleri','AUS'],
        ['Dominik','Baumann','AUT'],
        ['Scott','Hockey','AUS'],
        ['Neil','Foster','AUS'],
        ['Jonny','Reid','NZL'],
        ['John','Udy','AUS'],
        ['Matt','Whittaker','AUS'],
        ['Andrew','Bagnall','NZL'],
        ['Matt','Halliday','NZL'],
        ['Roger','Lago','AUS'],
        ['David','Russell','AUS'],
        ['Andrew','Fawcett','AUS'],
        ['Gene','Rollinson','AUS'],
        ['Jim','Manolios','AUS'],
        ['Ryan','Miller','AUS'],
        ['Glen','Wood','NZL'],
        ['Peter','Major','AUS'],
        ['Justin','McMillan','AUS'],
        ['Andrew','MacPherson','AUS'],
        ['Brad','Shiels','AUS'],
        ['Fraser','Ross','NZL'],
        ['Warren','Luff','AUS'],
        ['Álvaro','Parente','PRT'],
        ['Peter','Hackett','AUS'],
        ['Dominic','Storey','NZL'],
        ['Geoff','Emery','AUS'],
        ['Garth','Tander','AUS'],
        ['Kelvin','van der Linde','ZAF'],
        ['Jaxon','Evans','NZL'],
        ['Tim','Miles','AUS'],
        ['Peter','Edwards','AUS'],
        ['Graeme','Smyth','AUS'],
        ['Steven','Richards','NZL'],
        ['James','Bergmuller','AUS'],
        ['Dylan',"O'Keeffe",'AUS'],
        ['Sam','Fillmore','NZL'],
        ['Danny','Stutterd','AUS'],
        ['Daniel','Gaunt','NZL'],
        ['Matt','Stoupas','AUS'],
        ['John','Martin','AUS'],
        ['Liam','Talbot','AUS'],
        ];


        foreach ($worlds as $world) {
            foreach ($drivers as [$first, $last, $iso]) {
                $country = Country::where('iso_code', $iso)->first();
                if (!$country) continue;
                Driver::updateOrCreate(
                    [
                        'world_id' => 3,
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