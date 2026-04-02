Patch audit fix for journal print feature.
Files included:
- app/helpers/journal_helper.php
- app/modules/journals/JournalController.php
- app/modules/journals/JournalModel.php
- app/modules/journals/views/index.php
- app/modules/journals/views/detail.php
- app/routes/web.php

Main fixes:
1. Merge journal print routes with user-account routes to avoid regression.
2. Use all accounting periods in journal list filters, not only open periods.
3. Block receipt printing until required receipt metadata is complete.
4. Validate print-list date filters before rendering.
