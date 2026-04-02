Patch ini memperbaiki sinkronisasi sidebar + cache asset.

File yang diganti:
- app/views/layouts/main.php
- public/assets/js/theme.js
- public/assets/css/ui-shell-fix.css

Langkah:
1. Timpa 3 file di atas.
2. Hard refresh browser (Ctrl+F5).
3. Patch ini otomatis mengganti query version asset dan me-reset state sidebar lama yang kadang bikin sidebar tiba-tiba tersembunyi.
