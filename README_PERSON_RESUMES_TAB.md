# Edit Person Resumes Tab

Apply this follow-up after the Resume Database patch. Copy the included new and changed files into your project root, preserving folders.

Run:

```powershell
php artisan optimize:clear
npm run build
php artisan test tests/Feature/PersonResumeTabTest.php tests/Feature/ResumeDatabaseTest.php
```

No new migration or dependencies are needed.

Open a Person's Edit page and select **Resumes** in the left navigation, just above Attachments. For example: `/portal/people/2/edit?section=resumes`.

The tab provides:

- Format selection and DOCX upload directly on Edit Person.
- Automatic linking to the current Person; the review screen locks that selection.
- Review of parsed fields before the resume becomes searchable.
- A list of that person's saved resumes and the current uploader's pending drafts.
- View Resume, Edit Resume or Review Import, Download DOCX, and Delete actions.
- Return to the same person's Resumes tab after saving, discarding, or deleting.

Resume uploads are independent of the Person Details form and do not require Save Changes on that form. Save any pending person-detail edits before leaving the page to review an upload.

The tab requires view_resumes. Upload/edit/delete also require manage_resumes. The Edit Person page and its person-specific resume routes retain update_people and access_portal requirements. Existing Owner/Admin resume permissions continue to apply.

Person routes reject a resume ID belonging to a different Person. Other users' draft imports are hidden. Saved resumes remain searchable through the Resume Database and retain the existing candidate integration.

Validation: 18 tests passed with 142 assertions across the person resume tab and resume database suites. Production Vite build passed. No new TypeScript errors were reported for these screens; the existing project-wide type errors remain.
