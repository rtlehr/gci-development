# Insite Portal — optional Azure Blob Storage integration

## Backward compatibility

Default configuration remains local. Windows/XAMPP, Linux, and IIS installations do not require Azure, credentials, or a managed identity. Existing resume/import paths without a disk prefix still resolve to the local disk, and existing ticket screenshot paths still resolve to the public disk. Person attachments already store their disk name in each database record. No schema migration or automatic file migration is performed.

## Azure Web App application settings

```
INSITE_DOCUMENTS_DISK=azure_documents
INSITE_IMPORTS_DISK=azure_imports
AZURE_STORAGE_ACCOUNT=stinsitetest
AZURE_DOCUMENTS_CONTAINER=insite-documents
AZURE_IMPORTS_CONTAINER=insite-imports
```

Keep `FILESYSTEM_DISK` unchanged; do not globally redirect the Laravel framework's default disk. The adapter authenticates using the App Service system-assigned managed identity (`IDENTITY_ENDPOINT` / `IDENTITY_HEADER`) and uses no storage account keys. Grant this identity `Storage Blob Data Contributor` on the two private containers. Ensure network routing through `snet-appservice` is working.

## Deployment and testing

Deploy only files in this patch. After changing Azure settings, run `php artisan optimize:clear` followed by `php artisan config:cache` on the hosting environment. First test without application data and verify resume upload/view/download/delete, person attachments, ticket screenshots from portal and admin, Excel import upload/validation/rollback, and upload failure recovery. Do not enable the Azure disks before assigning container RBAC permissions.

Existing local files are **not migrated** between hosting machines. For data moving to Azure, perform a separately planned migration or keep the source file accessible on its original host. For development, omit the five Azure-specific environment variables.

## Implementation and limitations

- Managed-identity-only REST filesystem adapter with no new Composer dependencies.
- Azure uploads/downloads are buffered in PHP memory; evaluate chunked uploads for larger files and production load. Insite's upload limits are smaller (e.g., Excel 20 MB).
- New Azure path references for resumes, tickets, and imports are prefixed by `azure_documents:` / `azure_imports:` to preserve legacy local paths without changing tables. Attachments use their existing `disk` field.
- Temporary local copies are used solely while parsing an imported workbook; they are deleted afterward.
- Existing public ticket files remain accessible at their original storage URLs unless separately migrated. New Azure ticket screenshots are private and served through authorization checks.
- This patch has passed PHP syntax checks but **cannot be considered production validated** until application integration tests run with the dependencies and Azure runtime.
