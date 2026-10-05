# Public / portal page widths
Based on the supplied layout.zip.

All PageContainer pages using the public/portal layout now use the centered max-w-7xl (1280px) container matching Edit Position, regardless of default/wide/full size. Covers resume search/upload/review/view, People and Positions lists, Position View, Candidates list, Job Title pages, and requirements pages. Person View and My Portal Dashboard have also been narrowed to the same width. Existing narrower content pages remain as-is. Responsive padding is preserved; tables retain their existing overflow behavior.

Admin page container sizes are unchanged: the width setting is provided only by PublicPortalLayout.

Copy the included files into your project, preserving paths. Run npm run build and refresh the browser. No migration or backend changes are needed.

Validation: production build passed with the latest supplied source. No live-browser layout review was performed.
