# CHANGES

## 4.3

- Set plugin version to `2023100400` and requires `2023100400`.
- `pix/monologo.png` and `pix/monologo.svg` taken from Moodle 4.5 IMS content package plugin.
- Removed dynamic properties (deprecated).
- Removed `classes/xapi` folder which is associated with TRAX Logs plugin.
- Changed `assessmentpath_get_file_info` PHP docs.
- Removed `FEATURE_GROUPMEMBERSONLY` feature support (deprecated).
- Replaced `print_error` by `throw new \moodle_exception` (deprecated).
- Renamed column `rank` of table `assessmentpath_steps` to `position` (https://github.com/salesagility/SuiteCRM/issues/6046).
- Changed CSS to maximize page width when editing the steps.
