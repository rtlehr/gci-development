<?php

return [
    // Defaults preserve existing Windows/IIS/Linux behavior.
    'documents_disk' => env('INSITE_DOCUMENTS_DISK', 'local'),
    'imports_disk' => env('INSITE_IMPORTS_DISK', 'local'),
];
