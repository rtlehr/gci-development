import { usePage } from '@inertiajs/vue3'

export function useAppLabels() {

    const page = usePage()

    function label(key) {

        const configured =
            page.props.appLabels?.[key]

        if (configured) {
            return configured
        }

        // fallback formatting
        return key
            .replace(/_/g, ' ')
            .replace(/\b\w/g, char => char.toUpperCase())
    }

    return {
        label,
    }
}