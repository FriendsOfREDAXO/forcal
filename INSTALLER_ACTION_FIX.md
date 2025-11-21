# Fix for "Failed to parse yaml from package.yml" Error

## Issue Description

When attempting to publish a release using the FriendsOfREDAXO/installer-action@v1, the following error occurs:

```
Error: TypeError: Cannot convert undefined or null to object
/home/runner/work/_actions/FriendsOfREDAXO/installer-action/v1/dist/index.js:35424
    return Object.values(packageYml.files).some(file => file.version === version);
                  ^
```

## Root Cause

The error occurs in the `versionExists` function within the installer-action when it attempts to check if a version already exists on redaxo.org. The function calls `Object.values(packageYml.files)` without first checking if the `files` property exists.

This can happen when:
- The addon is newly created on MyREDAXO but has no published versions yet
- The API returns incomplete data
- The addon package data doesn't include the `files` property

## The forcal package.yml is Correct

There is **nothing wrong** with the `package.yml` file in this repository. The file is properly formatted and contains all required fields. The issue is entirely within the installer-action code.

## Solution

A fix has been implemented in the installer-action repository on branch `fix/handle-undefined-files-property`:

### Changes Made:

1. **Modified `src/myRedaxo.ts`**: Added defensive check in `versionExists()` function
   ```typescript
   export function versionExists(packageYml: MyRedaxoPackage, version: string) {
       if (!packageYml.files || typeof packageYml.files !== 'object') {
           return false;
       }
       return Object.values(packageYml.files).some(file => file.version === version);
   }
   ```

2. **Added tests** in `__tests__/myRedaxo.test.ts`:
   - Test for undefined files property
   - Test for null files property
   - Test for empty files object

3. **Rebuilt distribution**: The `dist/index.js` bundle was rebuilt with the fix

## Status

✅ **Fix committed** to installer-action repository (branch: `fix/handle-undefined-files-property`)

### Required Actions for Resolution:

1. The fix needs to be merged into the installer-action main branch
2. A new release/tag needs to be created in the installer-action repository
3. The `v1` tag should be updated to point to the fixed version

Once these steps are completed, the forcal repository will automatically use the fixed version since it references `@v1` in its workflow.

## Files Modified in installer-action:

- `src/myRedaxo.ts` - Added defensive check
- `__tests__/myRedaxo.test.ts` - Added test coverage
- `dist/index.js` - Rebuilt distribution with fix

## References

- Installer Action Repository: https://github.com/FriendsOfREDAXO/installer-action
- Fix Branch: `fix/handle-undefined-files-property`
- Related Issue: Filed in FriendsOfREDAXO/forcal
