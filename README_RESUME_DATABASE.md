# Insite Portal — Resume Database and Format Builder

This patch contains only new and changed source files. Copy the contents into the root of your existing Insite Portal project, preserving the folders. It is based on the supplied resume.zip.

## Install

From your project directory, with your database running:

```powershell
php artisan migrate
php artisan optimize:clear
npm run build
php artisan test tests/Feature/ResumeDatabaseTest.php
```

No new Composer or npm dependencies are required. PHP ZIP, DOM/XML, and mbstring must be enabled. For the 20 MB upload limit, configure PHP upload_max_filesize to at least 20M and post_max_size above 20M (for example, 24M). Apply matching upload limits on IIS if needed.

The migration creates the resume tables, the GCI Standard Resume format, and three permissions. Existing Owner and Admin roles receive these permissions automatically. The seeders preserve them on fresh installations. You do not need to reseed existing data.

## Where to go

- **Resumes** in the portal navigation: `/portal/resumes`.
- **Resume Formats** in Admin: `/admin/resume-formats`.
- **Find Candidates** on a Position details page opens search with that position selected.
- **Resumes** and **Upload Resume** on a Person details page open the person's resumes and carry that person into import review.
- The mobile Workforce menu also includes Resumes.

## Upload and review

1. Choose Upload Resume and select an active format/version.
2. Upload a DOCX. It is stored privately as a draft and does not appear in searches yet.
3. Review parser warnings. Download the original document to compare if needed.
4. Select the correct existing Person. Missing people must be created through the existing People screen first. Importing does not create a Person or change their name/contact details.
5. Correct individual fields and repeating education, certification, language, and technology records; add or remove entries as needed.
6. Select Save Reviewed Resume. The resume is now searchable.

Drafts are visible only to their uploader. They can be resumed from the search screen or discarded from review. A new upload creates a new resume record, preserving previous uploads. Search results identify each source filename and upload date. Saved resumes can be edited, downloaded, or deleted by appropriately authorized users. Deletion removes the original file and child records; it does not delete the Person or any Candidate.

## Create another format

The initial parser supports **two-column Word tables**, with a document label on the left and its value on the right. Different row ordering, capitalization, spacing, alternative labels, and multiple rows for the same field are supported.

Create a format with a name, description, availability flag, and label mappings. Separate aliases with `|`, for example `Technologies|Technical Skills|Skills / Tools`. Leave unused field mappings blank. A document label cannot map to two standard fields.

The builder also lets you reorder the comma-separated columns in repeating sections. List every supported column exactly once. The data still saves into the same standard fields for searching.

Default repeating layouts:

| Section | Default column order |
| --- | --- |
| Education | date, degree, major, institution, city, state |
| Certifications | date, certification, institution |
| Languages | language, reading_score, writing_score, evaluation_date |
| Technologies | technology |

Use a separate Word paragraph or line for each repeating entry. Quote any CSV value that contains a comma. Technologies can also use commas or semicolons to separate skills. Languages may use `Spanish (3/2)` followed by `Evaluation Date: 07/25` on the same or next line. If Proposed Labor Category contains a final comma-separated Requirement ID, the parser separates it and asks the reviewer to confirm.

Changing the format name, label mappings, or column order saves a new version. Previous imports keep their complete original format snapshot. Older versions remain selectable until you deactivate them. Description and availability changes update the selected version. Arbitrary freeform resumes, heading-based documents, and nested data tables require a future parser extension; the builder does not design a Word document or auto-detect formats.

A synthetic DOCX example matching the supplied screenshot is included at `tests/Fixtures/Resumes/gci-standard.docx`. It contains fictional Alex Morgan data for testing.

## Search and candidate creation

Space-separated keywords must all match the resume's searchable data. Advanced filters combine with AND and cover location, labor category, requirement ID, education, certifications, languages, technologies, tickets, and other information.

Choose a position and click Search, or enter from Find Candidates. Select an active candidate workflow and click Add to Position. This uses the existing candidate creation flow, creates a Submitted candidate, logs the action, and opens that position's candidate section.

The endpoint enforces existing position visibility rules, prevents duplicate assignments through this flow, and blocks closed or currently filled positions. The current schema represents filled positions through active assignments, rather than a literal Filled status. Concurrent resume assignments are serialized by locking the selected position.

## Permissions

| Permission | Capability |
| --- | --- |
| view_resumes | Search/view saved resumes and download original files |
| manage_resumes | Upload, review, edit, and delete resumes; requires view_resumes |
| manage_resume_formats | Create, version, and deactivate formats in Admin; requires view_admin |

Portal screens also require access_portal. Adding a candidate requires create_candidates and portal_view_positions plus access to the selected position under the existing workforce rules. Grant these through the existing role/direct-permission editor to recruiters or managers who need the feature. Resume search permission grants access to the shared saved resume database, not just the current person's resume.

Upload, save, download, deletion, format changes, and candidate creation use existing event logging. Search text is ordinary searchable database content and is not application-level encrypted; the original file uses protected local storage.

## Validation

- 21 tests passed (148 assertions): the new resume suite plus existing candidate management and portal position suites, using an isolated SQLite database.
- Production Vite build passed.
- PHP syntax and formatting checks passed for new PHP code.
- ESLint passed for the new resume screens and updated Admin/navigation definitions.
- The full project TypeScript check still reports pre-existing issues in existing portal components and generated Dashboard actions. No errors were reported in the new resume screens or format builder.

Manual acceptance: upload the included sample, link a Person, review/save, search for Java and Spanish together, select an accessible open position, and add the person. Confirm their existing Candidate Workflow opens and that a second attempt cannot create a duplicate. Create another format with a different Technologies label and verify an upload using that label. Deactivate it and confirm it disappears from the upload selector.
