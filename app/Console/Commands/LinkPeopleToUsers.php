<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Person;
use App\Models\User;

class LinkPeopleToUsers extends Command
{
    protected $signature = 'people:link-users';
    protected $description = 'Link people rows to users by matching person_code to users.name';

    public function handle()
    {
        $this->info('Linking people to users...');

        $matched = 0;
        $unmatched = 0;

        Person::whereNotNull('person_code')->chunk(100, function ($people) use (&$matched, &$unmatched) {
            foreach ($people as $person) {
                $user = User::where('name', $person->person_code)->first();

                if ($user) {
                    $person->user_id = $user->id;
                    $person->save();
                    $matched++;
                } else {
                    $unmatched++;
                    $this->warn("No matching user for person_code: {$person->person_code}");
                }
            }
        });

        $this->info("Done. Matched: {$matched}. Unmatched: {$unmatched}.");
    }
}