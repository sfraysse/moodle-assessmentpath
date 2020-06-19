UPGRADE
=======

# Versions history

## 3.5.0 (2018050800)
## 3.6.0 (2018112800)
- Moodle 3.6 upgrade from 3.5

## 3.5.1 (2018050801)
- Events & xAPI

## 3.5.2 (2018050802)
## 3.5.3 (2018050803)
- Multiple groups


# Database impact
No impact


# Tests

## Basic usage
- 3 units: 1 open, 1 auto, 1 closed - OK
- User 1 : failed > remedial - OK
- User 2 : passed - OK
- Admin : close step 1, open step 2, check progress block & P0 - OK
- User 2 : review step 1, passed step 2 - OK
- P3 - OK
    - Comments - OK
    - Export HTML - OK
    - Export CSV - OK
    - Export Excel - OK
    - Export details (Excel) - OK
- P4 - OK
    - Comments - OK
    - Modify scores - OK
    - Export HTML - OK
    - Export CSV - OK
    - Export Excel - OK
- P1 - OK - OK
    - Path comments - OK
    - Course comments - OK
    - Export HTML - OK
    - Export Excel - OK
- P2 - OK
    - Switch groups - OK
    - Comments - OK
    - Export HTML - OK
    - Export CSV - OK
    - Export Excel - OK
    - Export paths (Excel) - OK
    - Export users (Excel) - OK
- Add group 2
    - P3 switch comments & export - OK
    - P4 switch comments & export & modify scores - OK
    - P1 switch comments & export - OK
    - P2 switch comments & export - OK

## Settings
- Availability - OK
- Max time - OK
- Passing score - OK
- Display in - OK
- Display chronometer - OK
- Number of attempts - OK
- Scoring method - OK
- Prevent new attempts after success - OK
- Review access - OK
- Display close button - OK

## Quetzal statistics
OK

## Data privacy
- Check Admin > Users > Privacy and policies > Plugin privacy registry - OK
- Run CRON - OK
- Download and explore data - OK

## Operations
- Duplicate - OK
- Backup / Restore - OK
- Reset - OK

## Events
- Attempt completed
- Attempt failed
- Attempt initialized - OK
- Attempt launched - OK
- Attempt passed - OK
- Attempt terminated - OK
- Course module instance list viewed
- Course module viewed - OK
- SCO result forced - OK
- SCO result updated - OK

## xAPI

### Sync
- Attempt completed
- Attempt failed
- Attempt initialized - OK
- Attempt launched - OK
- Attempt passed - OK
- Attempt terminated - OK
- Course module completion updated
- Course module viewed - OK
- SCO result forced
- SCO result updated

### Async
Errors with Trax Logs which has not been upgraded yet !!!!!!!!!!!!!!!!!!!!!!

