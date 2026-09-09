# CMS Image Width/Align Final Review Fix

## Verification

Command:

`php artisan test --filter='EditorPipelineTest|CmsImageLayoutCssTest|CmsAdminTest'`

Observed:

`17 tests passed with 83 assertions.`

Command:

`php artisan test --filter=test_page_content_round_trips_through_save_and_edit`

Observed:

`1 test passed with 15 assertions.`

Command:

`git diff --check`

Observed:

`Passed with no whitespace errors.`
