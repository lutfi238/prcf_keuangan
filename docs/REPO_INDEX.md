# Repository Index

This index lists tracked files by directory, excluding uploads contents.

## [root]
- index.php
- README.md

## includes (2)
- includes\maintenance_config.php
- includes\config.php

## api (4)
- api\realtime_updates.php
- api\get_proposals.php
- api\get_place_codes.php
- api\api_notifications.php

## auth (6)
- auth\verify_otp.php
- auth\unauthorized.php
- auth\register.php
- auth\logout.php
- auth\login.php
- auth\forgot_password.php

## pages (27)
- pages\reports\view_report_sa.php
- pages\reports\view_report_pm.php
- pages\reports\view_report_fm.php
- pages\reports\view_report_dir.php
- pages\reports\view_report.php
- pages\reports\create_financial_report.php
- pages\reports\approve-report-sa.php
- pages\reports\approve-report-fm.php
- pages\reports\approve-report-dir.php
- pages\proposals\view_proposal.php
- pages\proposals\review_proposal_OLD_BACKUP.php
- pages\proposals\review_proposal_fm.php
- pages\proposals\review_proposal_dir.php
- pages\proposals\create_proposal.php
- pages\proposals\approve_proposal.php
- pages\books\export_piutang_excel.php
- pages\books\export_bank_excel.php
- pages\books\buku_piutang.php
- pages\books\buku_bank.php
- pages\projects\manage_projects.php
- pages\admin\manage_users.php
- pages\profile\profile.php
- pages\dashboards\dashboard_fm.php
- pages\dashboards\dashboard_sa.php
- pages\dashboards\dashboard_dir.php
- pages\dashboards\dashboard_pm.php
- pages\dashboards\dashboard_admin.php

## assets (6)
- assets\Under Construction 1.json
- assets\Maintenance web.json
- assets\other\02 - Bank Book IDR - RC01 - Year 3 - buku_bank.xls
- assets\other\05 - Advance Book - RC01 - Year 3 - buku_piutang.xls
- assets\js\realtime_notifications.js
- assets\js\currency_format.js

## tests (13)
- tests\check_ip_debug.php
- tests\CHECK_2STAGE_STATUS.php
- tests\test_email.php
- tests\EXAMPLE_UNDER_CONSTRUCTION_USAGE.php
- tests\check_session.php
- tests\test_maintenance_status.php
- tests\test_email_simple.php
- tests\test_notifications_api.php
- tests\test_otp_manual.php
- tests\test_redirect_methods.php
- tests\test_sse.php
- tests\test_session_write.php
- tests\test_redirect_target.php

## docs (11)
- docs\whatsapp_removal_summary.md
- docs\USER_GUIDE_NEW_FEATURES.md
- docs\STATUS_LABELS_ENGLISH_SUMMARY.md
- docs\sse_summary.md
- docs\sse_implementation.md
- docs\IMPLEMENTATION_SUMMARY.md
- docs\IMPLEMENTATION_PROGRESS.md
- docs\FINAL_IMPLEMENTATION_STATUS.md
- docs\DEPLOYMENT_CHECKLIST.md
- docs\COMPLETED_IMPLEMENTATIONS_SUMMARY.md
- docs\APPROVAL_WORKFLOW_SUMMARY.md

## sql (7)
- sql\migrations\add_notification_tracking.sql
- sql\migrations\add_director_approval_to_reports.sql
- sql\migrations\add_admin_role.sql
- sql\migrations\add_project_codes_tables.sql
- sql\migrations\QUICK_FIX_FOR_TESTING.sql
- sql\migrations\alter_proposal_2stage_approval.sql
- sql\dumps\prcf_keuangan_clean.sql

## public (2)
- public\maintenance.php
- public\under_construction.php

## scripts (1)
- scripts\batch\start_ngrok.bat

## database (1)
- database\prcf_keuangan.sql
