# Position candidate resume search
Apply after previous resume patches; overwrite included files only.

Edit Position > Candidates now includes Search Resumes and an optional checkbox to use saved position skills. Search opens the resume database with the position selected so candidates can be added through the existing workflow.

Skills include inherited job-title skills and active custom skills. Each distinct skill name contributes one match when its text appears in the resume search text, ignoring case. Results matching at least one skill are ranked by match count, newest first for ties. This is text matching, not semantic/AI matching. Optional keywords still require all words. No skills means normal search. Save Skills changes before searching. Checkbox may be changed in the results and applied with Search.

Run:
php artisan optimize:clear
npm run build

No migration or new dependencies. Validation: 23 related tests passed (241 assertions); production build passed.
