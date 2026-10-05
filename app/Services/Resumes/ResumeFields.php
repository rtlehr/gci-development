<?php

namespace App\Services\Resumes;

class ResumeFields
{
    public const SCALARS = ['name', 'location', 'labor_category', 'requirement_id', 'tickets', 'other_information'];

    public const REPEATING = [
        'education' => ['date', 'degree', 'major', 'institution', 'city', 'state'],
        'certifications' => ['date', 'certification', 'institution'],
        'languages' => ['language', 'reading_score', 'writing_score', 'evaluation_date'],
        'technologies' => ['technology'],
    ];

    public static function defaults(): array
    {
        return ['name' => 'Name', 'location' => 'Home Location', 'labor_category' => 'Proposed Labor Category',
            'requirement_id' => 'Requirement ID', 'tickets' => 'Tickets', 'education' => 'Education',
            'certifications' => 'Certification|Certifications', 'languages' => 'Language(s)|Languages',
            'technologies' => 'Technologies', 'other_information' => 'Other Information'];
    }
}
