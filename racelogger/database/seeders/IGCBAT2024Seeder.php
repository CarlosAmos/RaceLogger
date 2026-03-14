<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\World;
use App\Models\Driver;
use App\Models\Country;

class IGCBAT2024Seeder extends Seeder
{
    public function run()
    {
        $worlds = World::all();
        if (!$worlds) return;

        $drivers = 
        [
            ['Ricardo','Feller','CHE'],
            ['Brad','Schumacher','AUS'],
            ['Markus','Winkelhock','DEU'],
            ['Marc','Cini','AUS'],
            ['Dean','Fiore','AUS'],
            ['Lee','Holdsworth','AUS'],
            ['Christopher','Haase','DEU'],
            ['Kelvin','van der Linde','ZAF'],
            ['Liam','Talbot','AUS'],
            ['Bastian','Buus','DNK'],
            ['Joel','Eriksson','SWE'],
            ['Jaxon','Evans','AUS'],
            ['Ian','James','GBR'],
            ['Ross','Gunn','GBR'],
            ['Alex','Riberas','ESP'],
            ['Sheldon','van der Linde','ZAF'],
            ['Dries','Vanthoor','BEL'],
            ['Charles','Weerts','BEL'],
            ['Raffaele','Marciello','CHE'],
            ['Maxime','Martin','BEL'],
            ['Valentino','Rossi','ITA'],
            ['Sergio','Pires','AUS'],
            ['Brad','Shiels','AUS'],
            ['Luke','Youlden','AUS'],
            ['Marcel','Zalloua','LBN'],
            ['James','Koundouris','AUS'],
            ['Theo','Koundouris','AUS'],
            ['David','Russell','AUS'],
            ['Jonathon','Webb','AUS'],
            ['Jack','Le Brocq','AUS'],
            ['Justin','McMillan','AUS'],
            ['Garth','Walden','AUS'],
            ['Glen','Wood','AUS'],
            ['Jules','Gounon','ROU'],
            ['Kenny','Habul','AUS'],
            ['Luca','Stolz','DEU'],
            ['Maximilian','Götz','DEU'],
            ['Daniel','Juncadella','ESP'],
            ['Jayden','Ojeda','AUS'],
            ['Prince','Jefri Ibrahim','MYS'],
            ['Jordan','Love','AUS'],
            ['Jamie','Whincup','AUS'],
            ['Will','Brown','AUS'],
            ['Broc','Feeney','AUS'],
            ['Mikaël','Grenier','CAN'],
            ['Tony',"D'Alberto",'AUS'],
            ['Adrian','Deitz','AUS'],
            ['Grant','Denyer','AUS'],
            ['David','Wall','AUS'],
            ['Maro','Engel','DEU'],
            ['Felipe','Fraga','BRA'],
            ['David','Reynolds','AUS'],
            ['Craig','Lowndes','AUS'],
            ['Thomas','Randle','AUS'],
            ['Cam','Waters','AUS'],
            ['Harry','King','GBR'],
            ['Alessio','Picariello','BEL'],
            ['Yasser','Shahin','AUS'],
            ['Matt','Campbell','AUS'],
            ['Ayhancan','Güven','TUR'],
            ['Laurens','Vanthoor','BEL'],

            ['Daniel','Bilski','HKG'],
            ['Adam','Christodoulou','GBR'],
            ['Mark','Griffith','AUS'],
            ['Paul','Buccini','AUS'],
            ['Owen','Hizzey','GBR'],
            ['Colin','White','GBR'],
            ['Aaron','Zerefos','AUS'],
            ['Jesse','Bryan','AUS'],
            ['Marcos','Flack','AUS'],
            ['Chaz','Mostert','AUS'],
            ['Tom','Hayman','AUS'],
            ['Tom','McLennan','AUS'],
            ['Elliot','Schutte','AUS'],

            ['Cameron','Hill','AUS'],
            ['John','Holinger','AUS'],
            ['Nick','Percat','AUS'],
            ['Adam','Hargraves','AUS'],
            ['Daniel','Jilesen','NLD'],
            ['Cédric','Sbirrazzuoli','MCO'],
            ['David','Crampton','AUS'],
            ['Trent','Harrison','AUS'],
            ['Laura','Kraihamer','AUT'],
            ['Keith','Kassulke','PNG'],
            ['Cameron','McLeod','AUS'],
            ['Hadrian','Morrall','AUS'],
            ['Tim','Slade','AUS'],

            ['Darren','Currie','AUS'],
            ['Axle','Donaldson','AUS'],
            ['Rylan','Gray','AUS'],
            ['Lionel','Amrouche','FRA'],
            ['Julien','Bolot','FRA'],
            ['Philippe','Bonnel','FRA'],
            ['Geoff','Emery','AUS'],
            ['Daniel','Stutterd','AUS'],
            ['Paul','Tracy','CAN'],
            ['Max','Twigg','AUS'],
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