<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\World;
use App\Models\Driver;
use App\Models\Country;

class WEC2024DriverSeeder extends Seeder
{
    public function run()
    {
        $worlds = World::all();
        if (!$worlds) return;

        $drivers = [

            ['Earl','Bamber','NZL'],
            ['Alex','Lynn','GBR'],
            ['Sébastien','Bourdais','FRA'],
            ['Álex','Palou','ESP'],
            ['Matt','Campbell','AUS'],
            ['Michael','Christensen','DNK'],
            ['Frédéric','Makowiecki','FRA'],
            ['Kévin','Estre','FRA'],
            ['André','Lotterer','DEU'],
            ['Laurens','Vanthoor','BEL'],
            ['Kamui','Kobayashi','JPN'],
            ['Nyck','de Vries','NLD'],
            ['Mike','Conway','GBR'],
            ['José María','López','ARG'],
            ['Sébastien','Buemi','CHE'],
            ['Brendon','Hartley','NZL'],
            ['Ryō','Hirakawa','JPN'],
            ['Carl','Bennett','NZL'],
            ['Antonio','Giovinazzi','ITA'],
            ['Jean-Karl','Vernay','FRA'],
            ['Callum','Ilott','GBR'],
            ['Will','Stevens','GBR'],
            ['Norman','Nato','FRA'],
            ['Jenson','Button','GBR'],
            ['Phil','Hanson','GBR'],
            ['Oliver','Rasmussen','DNK'],
            ['Raffaele','Marciello','CHE'],
            ['Dries','Vanthoor','BEL'],
            ['Marco','Wittmann','DEU'],
            ['Robin','Frijns','NLD'],
            ['René','Rast','DEU'],
            ['Sheldon','van der Linde','ZAF'],
            ['Paul-Loup','Chatin','FRA'],
            ['Ferdinand','Habsburg','AUT'],
            ['Jules','Gounon','FRA'],
            ['Charles','Milesi','FRA'],
            ['Mick','Schumacher','DEU'],
            ['Matthieu','Vaxivière','FRA'],
            ['Nicolas','Lapierre','FRA'],
            ['Antonio','Fuoco','ITA'],
            ['Miguel','Molina','ESP'],
            ['Nicklas','Nielsen','DNK'],
            ['James','Calado','GBR'],
            ['Antonio','Giovinazzi','ITA'],
            ['Alessandro','Pier Guidi','ITA'],
            ['Robert','Kubica','POL'],
            ['Robert','Shwartzman','ISR'],
            ['Yifei','Ye','CHN'],
            ['Mirko','Bortolotti','ITA'],
            ['Daniil','Kvyat','RUS'],
            ['Edoardo','Mortara','CHE'],
            ['André','Caldarelli','ITA'],
            ['Mikkel','Jensen','DNK'],
            ['Nico','Müller','CHE'],
            ['Jean-Éric','Vergne','FRA'],
            ['Paul','di Resta','GBR'],
            ['Loïc','Duval','FRA'],
            ['Stoffel','Vandoorne','BEL'],
            ['Julien','Andlauer','FRA'],
            ['Neel','Jani','CHE'],
            ['Harry','Tincknell','GBR'],
            ['Ian','James','GBR'],
            ['Daniel','Mancinelli','ITA'],
            ['Alex','Riberas','ESP'],
            ['Erwan','Bastard','FRA'],
            ['Marco','Sørensen','DNK'],
            ['Clément','Mateu','FRA'],
            ['Satoshi','Hoshino','JPN'],
            ['Augusto','Farfus','BRA'],
            ['Sean','Gelael','IDN'],
            ['Darren','Leung','GBR'],
            ['Ahmad','Al Harthy','OMN'],
            ['Maxime','Martin','BEL'],
            ['Valentino','Rossi','ITA'],
            ['Francesco','Castellacci','ITA'],
            ['Thomas','Flohr','CHE'],
            ['Davide','Rigon','ITA'],
            ['François','Hériau','FRA'],
            ['Simon','Mann','GBR'],
            ['Alessio','Rovera','ITA'],
            ['Nicolas','Costa','BRA'],
            ['James','Cottingham','GBR'],
            ['Grégoire','Saucy','CHE'],
            ['Nico','Pino','CHL'],
            ['Marino','Sato','JPN'],
            ['Josh','Caygill','GBR'],
            ['Hiroshi','Hamaguchi','JPN'],
            ['Claudio','Schiavoni','ITA'],
            ['Franck','Perera','FRA'],
            ['Matteo','Cairoli','ITA'],
            ['Sarah','Bovy','BEL'],
            ['Michelle','Gatting','DNK'],
            ['Doriane','Pin','FRA'],
            ['Rahel','Frey','CHE'],
            ['Ben','Barker','GBR'],
            ['Ryan','Hardwick','USA'],
            ['Zacharie','Robichon','CAN'],
            ['Dennis','Olsen','NOR'],
            ['Mikkel','O. Pedersen','DNK'],
            ['Giorgio','Roda','ITA'],
            ['Christian','Ried','DEU'],
            ['Ben','Keating','USA'],
            ['Gianmarco','Levorato','ITA'],
            ['Arnold','Robin','FRA'],
            ['Kelvin','van der Linde','ZAF'],
            ['Timur','Boguslavskiy','RUS'],
            ['Clemens','Schmid','AUT'],
            ['Ritomo','Miyata','JPN'],
            ['Conrad','Laursen','DNK'],
            ['Takeshi','Kimura','JPN'],
            ['Esteban','Masson','FRA'],
            ['José María','López','ARG'],
            ['Jack','Hawksworth','GBR'],
            ['Rui','Andrade','AGO'],
            ['Charlie','Eastwood','IRL'],
            ['Tom','Van Rompuy','BEL'],
            ['Sébastien','Baud','FRA'],
            ['Daniel','Juncadella','ESP'],
            ['Hiroshi','Koizumi','JPN'],
            ['Richard','Lietz','AUT'],
            ['Morris','Schuring','NLD'],
            ['Yasser','Shahin','AUS'],
            ['Klaus','Bachler','AUT'],
            ['Alex','Malykhin','BLR'],
            ['Joel','Sturm','DEU']
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