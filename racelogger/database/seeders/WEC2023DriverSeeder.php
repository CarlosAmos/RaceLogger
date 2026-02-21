<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\World;
use App\Models\Driver;
use App\Models\Country;

class WEC2023DriverSeeder extends Seeder
{
    public function run()
    {
        $worlds = World::all();
        if (!$worlds) return;

        $drivers = [

            /*
            |--------------------------------------------------------------------------
            | HYPERCAR
            |--------------------------------------------------------------------------
            */

            // Toyota
            ['Mike','Conway','GBR'],
            ['Kamui','Kobayashi','JPN'],
            ['José María','López','ARG'],
            ['Sébastien','Buemi','CHE'],
            ['Brendon','Hartley','NZL'],
            ['Ryo','Hirakawa','JPN'],

            // Ferrari
            ['Alessandro','Pier Guidi','ITA'],
            ['James','Calado','GBR'],
            ['Antonio','Giovinazzi','ITA'],
            ['Antonio','Fuoco','ITA'],
            ['Miguel','Molina','ESP'],
            ['Nicklas','Nielsen','DNK'],

            // Porsche Penske
            ['Kévin','Estre','FRA'],
            ['André','Lotterer','DEU'],
            ['Laurens','Vanthoor','BEL'],
            ['Dane','Cameron','USA'],
            ['Frédéric','Makowiecki','FRA'],
            ['Michael','Christensen','DNK'],

            // Cadillac
            ['Earl','Bamber','NZL'],
            ['Alex','Lynn','GBR'],
            ['Richard','Westbrook','GBR'],
            ['Sébastien','Bourdais','FRA'],
            ['Renger','van der Zande','NLD'],
            ['Scott','Dixon','NZL'],

            // Peugeot
            ['Paul','di Resta','GBR'],
            ['Mikkel','Jensen','DNK'],
            ['Jean-Éric','Vergne','FRA'],
            ['Loïc','Duval','FRA'],
            ['Gustavo','Menezes','USA'],
            ['Nico','Müller','CHE'],

            // Glickenhaus
            ['Romain','Dumas','FRA'],
            ['Olivier','Pla','FRA'],
            ['Ryan','Briscoe','AUS'],
            ['Franck','Mailleux','FRA'],

            // Vanwall
            ['Jacques','Villeneuve','CAN'],
            ['Tom','Dillmann','FRA'],
            ['Esteban','Guerrieri','ARG'],

            /*
            |--------------------------------------------------------------------------
            | LMP2 (Full Season + Le Mans)
            |--------------------------------------------------------------------------
            */

            ['Phil','Hanson','GBR'],
            ['Will','Stevens','GBR'],
            ['Gabriel','Aubry','FRA'],
            ['Oliver','Jarvis','GBR'],
            ['Tom','Blomqvist','GBR'],
            ['Louis','Delétraz','CHE'],
            ['Robert','Kubica','POL'],
            ['Rui','Andrade','AGO'],
            ['Filipe','Albuquerque','PRT'],
            ['Ben','Keating','USA'],
            ['Matthieu','Vaxivière','FRA'],
            ['André','Negrão','BRA'],
            ['Charles','Milesi','FRA'],
            ['Paul-Loup','Chatin','FRA'],
            ['Mirko','Bortolotti','ITA'],
            ['Giedo','van der Garde','NLD'],
            ['Job','van Uitert','NLD'],
            ['Bent','Viscaal','NLD'],
            ['Clément','Novalak','FRA'],
            ['Juan Pablo','Montoya','COL'],
            ['Pietro','Fittipaldi','BRA'],
            ['Mathias','Beche','CHE'],
            ['Dorian','Boccolacci','FRA'],
            ['Reshad','de Gerus','FRA'],
            ['Albert','Costa','ESP'],
            ['François','Perrodo','FRA'],
            ['Nicklas','Nielsen','DNK'], // appears in both classes
            ['Ben','Hanley','GBR'],
            ['Matthias','Kaiser','CHE'],
            ['Roman','Rusynov','RUS'],

            /*
            |--------------------------------------------------------------------------
            | LMGTE AM (Full Season + Le Mans)
            |--------------------------------------------------------------------------
            */

            ['Ahmad','Al Harthy','OMN'],
            ['Valentino','Rossi','ITA'],
            ['Daniel','Serra','BRA'],
            ['Ben','Barnicoat','GBR'],
            ['Claudio','Schiavoni','ITA'],
            ['Christophe','Ullrich','FRA'],
            ['Sarah','Bovy','BEL'],
            ['Rahel','Frey','CHE'],
            ['Michelle','Gatting','DNK'],
            ['Doriane','Pin','FRA'],
            ['Nick','Tandy','GBR'],
            ['Marco','Sørensen','DNK'],
            ['Ben','Barker','GBR'],
            ['Julien','Andlauer','FRA'],
            ['Ricky','Taylor','USA'],
            ['Nicky','Catsburg','NLD'],
            ['Davide','Rigon','ITA'],
            ['Miguel','Ramos','PRT'],
            ['Gianmaria','Bruni','ITA'],
            ['Francesco','Castellacci','ITA'],
            ['Thomas','Flohr','CHE'],
            ['Francesco','Pizzi','ITA'],
            ['Harry','Tincknell','GBR'],
            ['Darren','Turner','GBR'],
            ['Alex','Riberas','ESP'],
            ['Marcos','Gomes','BRA'],
            ['Rui','Águas','PRT'],
            ['Riccardo','Pera','ITA'],
            ['Alessio','Picariello','BEL'],
            ['Antonio','García','ESP'],

            ['Filip','Ugran','ROU'],
            ['Andrea','Caldarelli','ITA'],
            ['Juan Manuel','Correa','USA'],
            ['Daniil','Kvyat','RUS'],
            ['Ryan','Cullen','IRL'],
            ['Frederick','Lubin','GBR'],
            ['Josh','Pierson','USA'],
            ['David Heinemeier','Hansson','DNK'],
            ['Oliver','Rasmussen','DNK'],
            ['Robin','Frijns','NLD'],
            ['Sean','Gelael','IDN'],
            ['Ferdinand','Habsburg','AUT'],
            ['Fabio','Scherer','CHE'],
            ['Jakub','Śmiechowski','POL'],
            ['Olli','Caldwell','GBR'],
            ['Andre','Negrao','BRA'],
            ['Memo','Rojax','MEX'],
            ['Julien','Canal','FRA'],

            ['Simon','Mann','USA'],
            ['Ulysse','De Pauw','BEL'],
            ['Kei','Cozzolino','JPN'],
            ['Stefano','Costantini','ITA'],
            ['Diego','Alessi','ITA'],
            ['Julien','Piguet','FRA'],
            ['Hiroshi','Koizumi','JPN'],
            ['Franck','Dezoteux','FRA'],
            ['Francesco','Castellacci','ITA'],
            ['Thomas','Flohr','CHE'],
            ['Davide','Rigon','ITA'],
            ['Luis','Pérez Companc','ARG'],
            ['Alessio','Rovera','ITA'],
            ['Lilou','Wadoux','FRA'],
            ['Ahmad','Al Harthy','OMN'],
            ['Michael','Dinan','USA'],
            ['Charlie','Eastwood','IRL'],
            ['Tomonobu','Fujii','JPN'],
            ['Casper','Stevenson','GBR'],
            ['Satoshi','Hoshino','JPN'],
            ['Liam','Talbot','AUS'],
            ['Nicky','Catsburg','NLD'],
            ['Ben','Keating','USA'],
            ['Nicolás','Varrone','ARG'],
            ['Matteo','Cairoli','ITA'],
            ['P. J.','Hyett','USA'],
            ['Gunnar','Jeannette','USA'],
            ['Guilherme','Oliveira','PRT'],
            ['Miguel','Ramos','PRT'],
            ['Erin','Castro','DOM'],
            ['Takeshi','Kimura','JPN'],
            ['Scott','Huffaker','USA'],
            ['Daniel','Serra','BRA'],
            ['Kei','Cozzolino','JPN'],
            ['Ritomo','Miyata','JPN'],
            ['Esteban','Masson','FRA'],
            ['Matteo','Cressoni','ITA'],
            ['Alessio','Picariello','BEL'],
            ['Claudio','Schiavoni','ITA'],
            ['Sarah','Bovy','BEL'],
            ['Rahel','Frey','CHE'],
            ['Michelle','Gatting','DNK'],
            ['Julien','Andlauer','FRA'],
            ['Christian','Ried','DEU'],
            ['Mikkel','O. Pedersen','DNK'],
            ['Harry','Tincknell','GBR'],
            ['Ryan','Hardwick','USA'],
            ['Zacharie','Robichon','CAN'],
            ['Jonas','Ried','DEU'],
            ['Don','Yount','USA'],
            ['Ben','Barker','GBR'],
            ['Riccardo','Pera','ITA'],
            ['Michael','Wainwright','GBR'],
            ['Paul','Dalla Lana','CAN'],
            ['Axcil','Jefferies','ZWE'],
            ['Nicki','Thiim','DNK'],
            ['Ian','James','GBR'],
            ['Daniel','Mancinelli','ITA'],
            ['Alex','Riberas','ESP']
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