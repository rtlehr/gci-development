// Import Inertia's usePage composable so we can access
// globally shared Laravel/Inertia page props
import { usePage } from '@inertiajs/vue3'

/**
 * Global application label helper.
 *
 * This composable allows Vue components to retrieve
 * centralized labels from:
 *
 * config/app_labels.php
 *
 * which are shared through Inertia page props.
 */
export function useAppLabels() {

    // Access the current Inertia page object
    // including all shared props
    const page = usePage()

    /**
     * Retrieve a label by key.
     *
     * Example:
     * label('person_code')
     *
     * Will attempt to return:
     * page.props.appLabels.person_code
     */
    function label(key) {

        // Attempt to retrieve the configured label
        // from the globally shared Inertia props
        const configured =
            page.props.appLabels?.[key]

        // If a configured label exists,
        // return it immediately
        if (configured) {
            return configured
        }

        /**
         * Fallback formatting
         *
         * If the label does not exist in:
         * config/app_labels.php
         *
         * automatically convert:
         *
         * person_code
         *
         * into:
         *
         * Person Code
         */

        return key

            // Replace underscores with spaces
            .replace(/_/g, ' ')

            // Capitalize the first letter of each word
            .replace(/\b\w/g, char => char.toUpperCase())
    }

    // Return public functions
    // available to Vue components
    return {
        label,
    }
}