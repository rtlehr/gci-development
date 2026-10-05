# Add to Position Button Fix

Copy the included files into your project, preserving folders. Apply after the resume database and Edit Person Resumes tab patches.

Run:

```powershell
php artisan optimize:clear
npm run build
```

Refresh the Resume Database page. Select a position, click Search, choose a workflow, and click Add to Position beside the resume.

The button no longer silently disables based on the position eligibility flag. The server checks eligibility on submission and displays a specific message for a closed or filled position. Existing permissions, active workflow requirements, and duplicate candidate checks remain enforced. Already associated people show Already a Candidate.

No new migration or dependencies are required. The resume and person resume tab suites passed: 18 tests, 142 assertions. Production build passed.
