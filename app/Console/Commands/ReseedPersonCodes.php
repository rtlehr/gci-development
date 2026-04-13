<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Person;

class ReseedPersonCodes extends Command
{
    protected $signature = 'people:reseed-codes';
    protected $description = 'Reseed person_code with unique 7-digit numbers';

    public function handle()
    {
        $this->info('Reseeding person codes...');

        $start = 1000001;

        Person::orderBy('id')->chunk(100, function ($people) use (&$start) {
            foreach ($people as $person) {
                $person->person_code = $start++;
                $person->save();
            }
        });

        $this->info('Done!');
    }
}
