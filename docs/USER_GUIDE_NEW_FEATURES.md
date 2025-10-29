# User Guide - New Features

## Table of Contents
1. [For Project Managers (PM)](#for-project-managers-pm)
2. [For Staff Accountants (SA)](#for-staff-accountants-sa)
3. [For Finance Managers (FM)](#for-finance-managers-fm)
4. [For Directors](#for-directors)
5. [For Administrators](#for-administrators)

---

## For Project Managers (PM)

### Creating a Proposal
1. Navigate to "Create Proposal" from your dashboard
2. **New Feature**: Your name is auto-filled in the "Applicant Name" field (read-only)
3. Fill in all required fields
4. Upload necessary documents (TOR, Budget)
5. Submit for FM review

### Creating a Financial Report
1. Navigate to "Create Financial Report" from your dashboard
2. **New Feature**: The "Report Date" field is read-only and auto-filled with current date
3. Fill in the general information (Activity Name, Project, Location, etc.)
4. Add expense items:
   - **New Feature - Place Code Autocomplete**:
     - Start typing in the "Kode Tempat" field
     - Suggestions will appear as you type
     - Click on a suggestion to auto-fill both Place Code and Expense Code
     - Example: Type "20208" to see all codes starting with "20208"
   - **New Feature - Currency Formatting**:
     - Enter amounts normally (e.g., 400000)
     - The system will automatically format with thousand separators (400.000)
5. Upload receipts for each expense item
6. Submit for SA validation

### Viewing Report Status
1. Check your PM dashboard to see all your reports
2. **New Status Badges**:
   - 🟡 **Pending SA Validation** - Waiting for Staff Accountant
   - 🟢 **Validated by SA** - SA has validated, waiting for FM
   - 🔵 **Approved by FM (1/2)** - FM approved, waiting for Director
   - 🟣 **Approved Final** - Fully approved by Director
   - 🟠 **Needs Revision** - Requires your attention
   - 🔴 **Rejected** - Not approved

### Handling Revision Requests
1. If a report needs revision, you'll see the "Needs Revision" status
2. Click on the report to view the revision notes from FM/Director
3. Make necessary changes
4. **New Feature**: When you resubmit, previous revision notes are automatically cleared
5. The report re-enters the approval workflow

---

## For Staff Accountants (SA)

### Validating Financial Reports
1. Navigate to your SA dashboard
2. Click on a report with "Pending Validation" status
3. Review all expense items:
   - Check receipts by clicking the **Preview** button
   - **New Feature**: Receipts open in a modal overlay (no new tabs!)
   - Close modal by clicking "Close", pressing ESC, or clicking outside
4. Verify calculations:
   - Actual Cost = Unit Total × Unit Cost
   - Balance = Requested - Actual
5. **New Feature**: Exchange rate is displayed from PM's original input
6. Click "Validate Report" to approve or "Request Revision" to send back to PM

### Viewing Receipts
- Click the **Preview** button on any expense item
- **For Images**: Opens in full-screen modal with zoom capability
- **For PDFs**: Opens embedded PDF viewer in modal
- Close with ESC key, "Close" button, or click outside the modal

---

## For Finance Managers (FM)

### Reviewing Proposals
1. Navigate to "Proposal Masuk" tab on your dashboard
2. Click on a proposal to review
3. **New Feature - Request Revision**:
   - Click "Request Revision" button
   - Notes field appears automatically
   - Button label changes to "Cancel" (click again to hide notes)
   - "Approve" button changes to "Send to PM"
   - Enter revision notes and click "Send to PM"
4. **New Feature - Staged Approval**:
   - Approval button now shows **"Approve (Stage 1/2)"**
   - This indicates FM is the first approval stage
   - After approval, proposal goes to Director for Stage 2/2

### Reviewing Financial Reports
1. Navigate to "Laporan Keuangan" tab on your dashboard
2. **New Feature**: See ALL reports, not just pending ones
3. **Status Badges**:
   - Draft, Pending SA, Validated, Approved FM, Approved Final, Needs Revision, Rejected
4. **Smart Navigation**:
   - Click on any row to open the report
   - Already approved reports open in "View" mode
   - Pending reports open in "Approve" mode
5. **Receipt Preview**:
   - Click **Preview** button on receipts
   - Opens in modal overlay instead of new tab
6. **Staged Approval Button**: Shows **"Approve (Stage 1/2)"**

### Dashboard Improvements
- **Proposals Tab**: Shows all proposals awaiting your review
- **Laporan Keuangan Tab**: Shows ALL financial reports with status filters
- Clickable rows for quick navigation
- Color-coded status badges for easy scanning

---

## For Directors

### Dashboard Overview
**New Structured Stats**:

#### Proposals Overview
- **Incoming for Approval**: Proposals awaiting your final approval (Stage 2/2)
- **Approved (Final)**: Proposals you've finalized

#### Financial Reports Overview
- **Submitted & Verified**: Reports ready for your final approval
- **Approved (Final)**: Reports you've finalized

#### Projects Overview
- **Total Projects**: All projects in the system
- **Active Projects**: Currently ongoing projects
- **Completed Projects**: Finished projects

### Approving Proposals
1. Click on a proposal from "Proposal Masuk" tab
2. Review all details
3. **New Feature - Staged Approval**:
   - Approval button shows **"Approve (Stage 2/2)"**
   - This is the final approval stage
   - After your approval, proposal is fully approved
4. **Request Revision** works the same as FM (with dynamic notes field)

### Approving Financial Reports
1. Click on a report from "Laporan Keuangan" tab
2. Review the full report
3. **Viewing Receipts**:
   - Click **Preview** to open receipts in modal
   - Supports both images and PDFs
4. **Final Approval (Stage 2/2)**:
   - Button shows **"Approve (Stage 2/2)"**
   - Confirmation dialog emphasizes this is final approval
   - After approval, report status becomes "Approved Final"

---

## For Administrators

### Admin Dashboard
**New Admin Panel** - Access via: `pages/dashboards/dashboard_admin.php`

#### System Statistics
- **Total Users**: All registered users
- **Total Projects**: All projects in system
- **Total Proposals**: All proposals submitted
- **Total Reports**: All financial reports created

#### Users by Role
- Visual breakdown of users per role:
  - Admin, Direktur, Finance Manager, Staff Accountant, Project Manager

#### Recent Users Table
- View the 10 most recently created users
- See name, email, phone, role, and creation date

#### Quick Actions
1. **User Management**: Create, edit, delete users (placeholder - needs implementation)
2. **Project Management**: Manage projects and project codes
3. **System Settings**: Configure system-wide settings

### Managing Project Codes
**Database Structure** (to be managed via admin interface in future):

1. **Categories** (Top Level):
   - Example: "1 - Forest Governance", "2 - Forest Protection"

2. **Subcategories** (Second Level):
   - Example: "101 - Forest Management Institution", "202 - Area Management"

3. **Place Codes** (Third Level):
   - Example: "20208-PR-01" (presentation/seminar)
   - Example: "20208-NJ-01" (document notarization)

**Current Setup**:
- Project codes are pre-populated via SQL migration
- Each project can have its own set of codes
- Codes follow hierarchical structure for organization

**Future Enhancement**:
- Admin web interface for adding/editing project codes per project
- Bulk import/export of code structures

---

## Common Features for All Users

### Modal Receipt Preview
**How it Works**:
1. Click the **Preview** button on any receipt/attachment
2. Modal opens with the file displayed:
   - **Images**: Full-screen preview with scaling
   - **PDFs**: Embedded PDF viewer
3. **Close Methods**:
   - Click the "Close" button
   - Press ESC key on keyboard
   - Click outside the modal (on the dark overlay)

**Benefits**:
- No more cluttered browser tabs
- Faster viewing experience
- Easier to compare multiple receipts

### Currency Formatting
**Automatic Formatting**:
- Enter: `400000`
- Displays as: `400.000`
- Applies to all amount fields in:
  - Proposals (budget amounts)
  - Financial reports (requested, actual, balance)
- Format is visual only - database stores numeric values

### Staged Approval Indicators
**Understanding the Labels**:
- **Stage 1/2**: First approval level (FM reviews)
- **Stage 2/2**: Final approval level (Director reviews)

**Workflows**:
- **Proposals**: PM → FM (1/2) → Director (2/2)
- **Reports**: PM → SA validates → FM (1/2) → Director (2/2)

---

## Troubleshooting

### Place Code Autocomplete Not Working
- **Solution**: Ensure the project has codes assigned in the database
- Contact your administrator to populate project codes

### Modal Not Closing
- **Solution**: Try these methods:
  1. Click the "Close" button
  2. Press ESC key
  3. Click on the dark area outside the modal

### Receipt Not Displaying in Modal
- **For PDFs**: Some browsers may block embedded PDFs
- **Solution**: Download the file using the browser's download option

### Currency Format Changes When Editing
- **Expected Behavior**: When you click in a formatted field, it may temporarily remove separators
- **Solution**: Continue editing normally; format will reapply on blur (clicking outside the field)

---

## Tips & Best Practices

### For Project Managers
1. Always upload clear, legible receipts (images or PDFs)
2. Use the place code autocomplete to ensure consistency
3. Double-check amounts before submitting (thousand separators help with readability!)
4. Respond promptly to revision requests to speed up approval

### For Approvers (SA, FM, Director)
1. Use the modal preview to quickly review receipts without opening multiple tabs
2. Check the status badges to prioritize which reports/proposals to review first
3. Use revision requests with clear notes to guide PMs on what needs fixing
4. Remember the staged approval workflow to know your role in the process

### For Administrators
1. Keep project codes organized and well-documented
2. Regularly review user activity and system statistics
3. Ensure database migrations are run in production before users access new features
4. Create admin users sparingly and only for trusted personnel

---

## Quick Reference - Status Meanings

### Proposal Statuses
| Status | Meaning | Next Action By |
|--------|---------|----------------|
| Draft | Saved but not submitted | PM (submit) |
| Submitted | Awaiting FM review | FM (approve/revise) |
| Approved | Approved by FM, awaiting Director | Director (final approval) |
| Revision Requested | Needs changes | PM (revise & resubmit) |
| Rejected | Not approved | - |

### Report Statuses
| Status | Meaning | Next Action By |
|--------|---------|----------------|
| Draft | Saved but not submitted | PM (submit) |
| Submitted | Awaiting SA validation | SA (validate) |
| Verified | Validated by SA, awaiting FM | FM (approve/revise) |
| Approved FM | Approved by FM (1/2), awaiting Director | Director (final approval) |
| Approved | Fully approved by Director (2/2) | - |
| Revision Requested | Needs changes | PM (revise & resubmit) |
| Rejected | Not approved | - |

---

**Questions or Issues?**  
Contact your system administrator or refer to the IMPLEMENTATION_SUMMARY.md for technical details.

**Last Updated**: 2025-10-29

