# Resume Position Selection Fix

Apply after the existing resume patches. Copy the included files into your project root, preserving folders, then run:

```powershell
php artisan optimize:clear
npm run build
```

Refresh the Resume Database page.

The previous screen kept using the last searched position after the dropdown was changed. The screenshot showed QA-POS-SEL selected while the candidate section and assignment still used New-001. Changing a position now refreshes the search context immediately, clears old errors, and keeps the heading, duplicate indicators, and assignment target in sync. Add to Position submits the ID selected in the dropdown. It waits while a new position selection is being loaded.

Closed positions are excluded from the dropdown. A direct link containing a Closed position clears that position selection. Open and In Process positions remain available within the existing user's position permissions. Existing checks for closed/filled positions, active workflows, and duplicate candidates remain enforced.

No new migration or dependencies are needed.

Validation: 20 related tests passed with 164 assertions, including Closed-position filtering and assignment to a newly selected Open position after a Closed-position search. Production build and frontend lint passed.
