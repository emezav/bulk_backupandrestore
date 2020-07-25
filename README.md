# Bulk backup and restore

This plugin allows site administrators to perform bulk course backup and restore. Each course backup/restore is performed on a separate AJAX request.

## Installation

Copy this folder into admin/tool and perform a site upgrage (via web or CLI).

Upon installation, go to Site Administration -> Courses -> Bulk backup and restore courses.

## Bulk backup of course categories

Select a category to backup, and a target directory (with write permissions for web user - apache, nginx).  All visible courses on this category (and all sub-categories) will be backed up.

When finished, a CSV file will be generated.

## Bulk restore

This plugin also allows performing restore from backup files stored on the server, using a CSV file where some parameters are defined:
- Target category
- Folder where the backup file is stored
- Backup file (.mbz)
- Restored  course full name
- Restored course short name
- Restore course Id number
- Restore users (1 = restore, 0 = do not restore)
- Restore blocks (1 = restore, 0 = do not restore)



