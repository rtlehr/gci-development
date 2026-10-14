# Form save navigation fix

Updated seven existing update handlers to redirect back to the originating form after successful save, retaining the edit URL and its `section` query parameter when supplied in the browser Referer.

Only changed controllers are included. Unzip into the application root, preserving directories.

Check editing and saving People, Candidates, Positions and Organizations in both Portal and legacy screens. Confirm validation failures remain on the form.

Note: Back redirects rely on the browser Referer header; forms that explicitly navigate away in Vue success callbacks or save via a different endpoint may need separate handling.
