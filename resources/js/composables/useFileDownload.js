import axios from 'axios'
import { ref } from 'vue'

export function useFileDownload() {
    const isDownloading = ref(false)
    const downloadError = ref(null)

    async function downloadFile(url, payload = {}, defaultFilename = 'download') {
        if (isDownloading.value) {
            return false
        }

        isDownloading.value = true
        downloadError.value = null

        try {
            const response = await axios.post(url, payload, {
                responseType: 'blob',
            })

            const filename = getFilenameFromHeaders(
                response.headers,
                defaultFilename
            )

            const blob = new Blob([response.data], {
                type: response.headers['content-type'] || 'application/octet-stream',
            })

            const blobUrl = window.URL.createObjectURL(blob)

            const link = document.createElement('a')
            link.href = blobUrl
            link.download = filename

            document.body.appendChild(link)
            link.click()

            document.body.removeChild(link)
            window.URL.revokeObjectURL(blobUrl)

            return true
        }
        catch (error) {
            console.error('File download failed:', error)

            const message = await getDownloadErrorMessage(error)

            downloadError.value = message

            return false
        }
        finally {
            isDownloading.value = false
        }
    }

    function getFilenameFromHeaders(headers, defaultFilename) {
        const disposition = headers['content-disposition']

        if (!disposition) {
            return defaultFilename
        }

        const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i)

        if (utf8Match?.[1]) {
            return decodeURIComponent(utf8Match[1])
        }

        const filenameMatch = disposition.match(/filename="?([^"]+)"?/i)

        if (filenameMatch?.[1]) {
            return filenameMatch[1]
        }

        return defaultFilename
    }

    async function getDownloadErrorMessage(error) {
        const fallbackMessage = 'Unable to download file.'

        const blob = error?.response?.data

        if (!(blob instanceof Blob)) {
            return fallbackMessage
        }

        try {
            const text = await blob.text()

            if (!text) {
                return fallbackMessage
            }

            const json = JSON.parse(text)

            return json.message || fallbackMessage
        }
        catch {
            return fallbackMessage
        }
    }

    return {
        downloadFile,
        isDownloading,
        downloadError,
    }
}