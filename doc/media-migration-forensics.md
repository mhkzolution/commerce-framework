# Media migration forensics

**Status:** Read-only. No code changes, media imports, deletes, or commits.  
**Date:** 2026-09-09  
**Command:** `php artisan product:attach-local-images` (after a clean dry-run)  
**Database:** `commerce_framework`

---

## Verdict

**Data issue**

All **87** failures are the same: mapped files exist on disk but are **1-byte placeholders** (ASCII space `0x20`), detected as `application/octet-stream`. `MediaUploadService` correctly rejects that MIME.

Not a mapping bug (paths resolve; dry-run `missing_files = 0`).  
Not an image-processing/GD bug (upload never persists these files).  
Command swallows the exception (no per-file log) — observability gap, not the cause.

---

## Expected vs actual

| Metric | Expected | Dry-run | Actual attach |
|---|---|---|---|
| Products attached | 1011 | 1011 | **996** |
| Products skipped | 0 | 0 | 0 |
| Missing products | 0 | 0 | 0 |
| Media created | 2497 | 0 | **2410** |
| Media reused | 12 | 0 | **12** |
| `product_media` | ~2509 | 2509 | **2422** |
| Missing files | 0 | 0 | 0 |
| Warnings | 0 | 0 | **87** |
| Errors | 0 | 0 | **87** |

Arithmetic:

- `2497 − 87 = 2410` unique media created  
- `2410 + 12 reused = 2422` pivots  
- `2509 − 87 = 2422`  
- `1011 − 996 = 15` SKUs with **zero** images (every listed file failed)  
- **1** SKU with a **partial** gallery (4 of 5)

87 errors and 87 warnings are the **same 87** `catch (Throwable)` events. Missing-file counter stayed 0 because `is_file()` is true.

Dry-run only checks that the path exists. It does **not** open bytes or check MIME, so it reported a perfect 2509/0.

---

## 1. Exact 87 failures

Exception (inferred; the command does not log the message):

```text
Commerce\Core\Exceptions\DomainException: File type [application/octet-stream] is not allowed.
```

`UploadedFile::getMimeType()` / `mime_content_type` on each failed file is `application/octet-stream`. Allowed list is jpeg/png/gif/webp/svg/pdf.

Every failed file:

| Property | Value |
|---|---|
| Size | **1 byte** |
| Contents | `0x20` (space) |
| Extension | `.jpeg` |
| Exists under `doc/uploads` | Yes |
| In mapping report | Yes |
| `media` row created | No |
| `product_media` row | No |

These look like WordPress/export stubs, not truncated JPEGs (a truncated JPEG would still usually sniff as `image/jpeg`).

---

## 2. Root-cause grouping

| Group | Count | Cause |
|---|---|---|
| 1-byte `application/octet-stream` `.jpeg` | **87 / 87** | Corrupt/placeholder source files |
| Missing path | 0 | — |
| Missing SKU | 0 | — |
| Shared-file / unique-constraint | 0 | 12 shared files reused as designed |
| GD / WebP variant generation | 0 | Never reached |

---

## 3. Affected SKUs

### Zero images (15) — all mapped files failed

| SKU | Name (mapping) | Failed files |
|---|---|---|
| `100271` | Baby Organic Cotton Rib Pants | 5 |
| `100272` | Baby Knit Denim Pants | 5 |
| `100273` | Boys Short Trousers | 6 |
| `100275` | Toddler Pull-On Shorts | 5 |
| `100277` | Ticking Stipe Shorts | 6 |
| `100282` | Baby Balloon Denim Jean | 7 |
| `100283` | Relaxed Fit Jeans | 6 |
| `100284` | Denim Joggers | 6 |
| `100285` | Baby Organic Cotton Jeans | 5 |
| `100286` | Baby Cotton Pants | 6 |
| `100287` | Shark Boys Swimwear | 8 |
| `100289` | Boys Swim Shirt | 5 |
| `100290` | Boys Swim Shirt | 6 |
| `100291` | Boys Swim Shirt | 4 |
| `100292` | Boys Swim Shirt | 6 |

Storefront will use `product.fallback_image_media_uuid` (currently unset).

### Partial gallery (1)

| SKU | Have / expected | Failed file | Primary |
|---|---|---|---|
| `100259` | Boys Swimming Trunks | 4 / 5 | Mapping position 4 failed (`2023/01/1185FE17-…-scaled.jpeg`). Positions 0–3 attached; **primary is still the first mapping file** (correct). Gallery is missing the 4th image only. |

---

## 4. Affected source files

All under `doc/uploads/`. **86** in `2023/02/`, **1** in `2023/01/`.

`2023/01/` (SKU `100259`):

- `2023/01/1185FE17-32EE-453E-B126-01B438B8E5C6-scaled.jpeg`

`2023/02/` (15 zero-image SKUs):

- `2023/02/001C6AA0-2C7E-4B30-B60E-7AD5BAC711F9-scaled.jpeg`
- `2023/02/0391AC4C-11F3-474A-8B63-3F5CD4920F2F-scaled.jpeg`
- `2023/02/0AB68502-295F-47ED-B9F4-06CD1884143A-scaled.jpeg`
- `2023/02/0B5FAD58-50F8-470D-84D9-0A08CC17C928-scaled.jpeg`
- `2023/02/12781C09-BC50-46AF-A459-2D756D71A013-scaled.jpeg`
- `2023/02/14CD940A-87A0-4FEC-A5CD-BA4A3E6727E9-scaled.jpeg`
- `2023/02/1697C74B-8337-4494-8691-C0D984364A21-scaled.jpeg`
- `2023/02/1AE4BDE9-F871-4119-8571-CF2CCA32AD69-scaled.jpeg`
- `2023/02/1BD3F9B9-03FC-4711-9151-98A0E48CC126-scaled.jpeg`
- `2023/02/1CF19290-F90B-4562-98FA-80B6C1AD06B7-scaled.jpeg`
- `2023/02/24CA71D5-58BF-4B10-97DC-75602E237AC8-scaled.jpeg`
- `2023/02/2EAE5431-B78F-4928-8B64-BFA881E8B547-scaled.jpeg`
- `2023/02/36178BAB-01F4-4446-A1B9-75592E7F58E9-scaled.jpeg`
- `2023/02/368BBC82-B390-4346-A660-DFA496FF8D10-scaled.jpeg`
- `2023/02/396BB97B-D522-439B-A792-645781C670BF-scaled.jpeg`
- `2023/02/3B28A3E2-D9EA-4A36-BD58-3F9FF6377B4B-scaled.jpeg`
- `2023/02/3E0EF38F-2592-4F27-8AE4-4230D65D7E8C-scaled.jpeg`
- `2023/02/406E2552-FA6A-488B-94E1-E11A6D61FACD-scaled.jpeg`
- `2023/02/443D3C09-2979-4378-9F9B-0D93AD7D6149-scaled.jpeg`
- `2023/02/478D0606-7596-4E28-86EA-5347B2614100-scaled.jpeg`
- `2023/02/4797C3AF-3030-47AB-BBAF-0965648CADF9-scaled.jpeg`
- `2023/02/490E1D50-F491-4627-9403-9C1996B7236D-scaled.jpeg`
- `2023/02/49BF13F6-C031-44CB-8933-A987EC2B0294-scaled.jpeg`
- `2023/02/4B1D41F3-263B-42FF-A059-AAF734E13CDB-scaled.jpeg`
- `2023/02/4B5CB22C-16EF-48DA-AB18-607EBB1C0F74-scaled.jpeg`
- `2023/02/4EF86F3C-D21F-407F-8939-D4104378EE39-scaled.jpeg`
- `2023/02/52D5B5EC-9D0E-4372-B41A-C4B581015F0F-scaled.jpeg`
- `2023/02/54B6ED75-2068-49C9-A62B-69CA48D39D35-scaled.jpeg`
- `2023/02/54B9BCD2-FA10-4FAC-BF23-BA7B351EF911-scaled.jpeg`
- `2023/02/555F356A-0B6B-4BFD-84E1-BC16F0CB83D0-scaled.jpeg`
- `2023/02/57FFCC6E-2E4A-49D2-971F-CF3A91C1564F-scaled.jpeg`
- `2023/02/58B9E309-87B6-465B-B68A-308F71A26259-scaled.jpeg`
- `2023/02/5A733449-CB83-41DE-A4D0-E37A73FBF944-scaled.jpeg`
- `2023/02/5E9FE2E6-A174-42EA-8910-C3869F3977F9-scaled.jpeg`
- `2023/02/5EDEB20F-1011-4664-88D0-EFE0698EDD9F-scaled.jpeg`
- `2023/02/5F4E3EA3-1D3D-4CC7-B66C-731BBD84BEFE-scaled.jpeg`
- `2023/02/61D91C49-66D3-41DA-A3A6-056E699803BA-scaled.jpeg`
- `2023/02/61E9F39C-D3B2-468D-A9EB-C953A23FAA6C-scaled.jpeg`
- `2023/02/636BDF0B-1FAD-40E7-8279-413CA4B1E4D9-scaled.jpeg`
- `2023/02/63A1E0EE-A7E0-404E-9C8C-C873955B5287-scaled.jpeg`
- `2023/02/69512609-50AF-4D96-9EAB-F6F17A147143-scaled.jpeg`
- `2023/02/7B71CA4E-4836-46BD-A3B9-1221D2D96FD0-scaled.jpeg`
- `2023/02/7D063A67-AB12-4840-B013-B9CC0776CBFD-scaled.jpeg`
- `2023/02/7F16AD6A-402F-478F-A3C0-79616119CBD8-scaled.jpeg`
- `2023/02/796FD11E-68F4-494A-A715-6F453A413ECF-scaled.jpeg`
- `2023/02/81BE9711-6AF9-49EB-85A1-5EDA0A24E1A7-scaled.jpeg`
- `2023/02/846920DF-E82C-4B6E-AB7E-BB5A02133BB2-scaled.jpeg`
- `2023/02/8681113A-CAF7-4A6F-86C9-CF3F0D1C4643-scaled.jpeg`
- `2023/02/8742AD48-457D-4C0A-A765-BC1553C35766-scaled.jpeg`
- `2023/02/8C37E351-D7BD-4245-99CF-861394372442-scaled.jpeg`
- `2023/02/8E931ADD-C8DF-485E-9A4D-5AF138D3F9DF-scaled.jpeg`
- `2023/02/90DFAEA5-F0D3-4F06-B299-555AC4455D49-scaled.jpeg`
- `2023/02/913C83DE-0304-4C30-95A7-9D5319FB780F-scaled.jpeg`
- `2023/02/914C0364-CAD8-42A0-A5B1-C38BD16B2850-scaled.jpeg`
- `2023/02/92273440-86D9-4965-A68F-D5C98FBD2464-scaled.jpeg`
- `2023/02/A0D4DE20-94BA-4B20-A864-A19A8D865DF0-scaled.jpeg`
- `2023/02/A2C3806F-F346-4914-8202-D9CB8363BAB6-scaled.jpeg`
- `2023/02/A3E1B321-51A3-4637-A6FF-F276F0BBA248-scaled.jpeg`
- `2023/02/B094F5FD-6054-4C04-8F8F-ECD2B16FEA82-scaled.jpeg`
- `2023/02/B2222850-C9D7-4071-8852-9160941E1FA7-scaled.jpeg`
- `2023/02/B264813A-38B2-434F-AAA5-2AFDC567CE10-scaled.jpeg`
- `2023/02/B3572F65-BD74-4C5B-8832-7E35AEA0C217-scaled.jpeg`
- `2023/02/B43A9212-A8E4-4907-9C89-0EEC3F403615-scaled.jpeg`
- `2023/02/B4EABF62-1A03-427B-8863-D5D77F2CC9C0-scaled.jpeg`
- `2023/02/B4F1678F-9EA3-4A6A-BBF6-3A7983A9D97C-scaled.jpeg`
- `2023/02/B7D0756F-FEE4-48E4-A8C3-BBA7986A1234-scaled.jpeg`
- `2023/02/C3A90EC2-EADA-4020-936B-98BAB5D8DC2D-scaled.jpeg`
- `2023/02/C50CFB60-A9D3-47DA-AE0F-ED9CBB50B995-scaled.jpeg`
- `2023/02/C67A3397-183C-42F3-9B87-63B92F181749-scaled.jpeg`
- `2023/02/CED52F9E-7819-4EAA-8A24-6C191B25733B-scaled.jpeg`
- `2023/02/CF709298-693F-49E0-9F98-F184A28149C4-scaled.jpeg`
- `2023/02/D12E57B6-54B1-4F11-A614-CA16ABB5EC42-scaled.jpeg`
- `2023/02/D936C7F9-F80D-479A-BF13-37E80DDD32FA-scaled.jpeg`
- `2023/02/E070B60A-9F87-463F-99A1-7AB49E251E2F-scaled.jpeg`
- `2023/02/E5634C16-4426-408A-B6BE-7A3650C1CC0A-scaled.jpeg`
- `2023/02/E66D77E7-02F5-4895-B821-D61DDAB8717A-scaled.jpeg`
- `2023/02/EB100217-357A-4190-A282-400F29065F4B-scaled.jpeg`
- `2023/02/EDAD9887-09F3-4A9C-863B-118443BF47A0-scaled.jpeg`
- `2023/02/F0141FA6-1583-4852-BB37-5F01CB3409C8-scaled.jpeg`
- `2023/02/F4E77756-BCFC-4AD3-AD2D-8F90C3DD4F70-scaled.jpeg`
- `2023/02/F620329A-4DA0-4AB4-AA38-4A433839D8B8-scaled.jpeg`
- `2023/02/F8B0E441-5B15-4072-B5CE-8D0E8ACB2B5B-scaled.jpeg`
- `2023/02/FA184A79-AC36-4AF8-B8C1-29A7DF41E0DA-scaled.jpeg`
- `2023/02/FE2EFB9C-1795-46AF-B2C3-02435F4A3075-scaled.jpeg`
- `2023/02/FFCE5BBB-DC11-41CE-91B7-9D8827BE2B59-scaled.jpeg`
- `2023/02/FFF58D90-C1EF-4D03-A098-D972F1B1F0D7-scaled.jpeg`

---

## 5. Recoverable?

**Only after replacing the 87 files with real images** (re-export from WordPress, or copy originals if they exist elsewhere in `doc/uploads` without the `-scaled` stub).

Until then, re-running the command cannot attach them. MIME rejection is correct.

The 15 empty SKUs will retry on a **non-force** re-run (they have no `product_media`). SKU `100259` will be **skipped** without `--force` because it already has 4 rows.

---

## 6. Would `--force` help?

**No, not with the current files.** `--force` deletes that SKU’s `product_media` and rebuilds. The 87 paths still throw.

| If you `--force` now | Effect |
|---|---|
| All 1011 mapped SKUs | Rebuilds **996** good galleries for no gain; 15 stay empty; `100259` still 4/5 |
| Only the 16 affected SKUs | Still 0/5 or 4/5 until files are replaced |

**Do not `--force` the whole catalog.** Shared UUID reuse and stamps are already consistent.

After real JPEGs are on disk: `--force` (or skip-existing for the 15 empty SKUs only) is the recovery path. For `100259`, `--force` is required to insert the 5th image in mapping order.

---

## 7. Is the database safe?

**Yes.** Keep the 996 / 2410 / 2422 state.

| Check | Result |
|---|---|
| Live `media` | 2410, all `meta.migration = ppk-images` |
| Unstamped / orphan `media` | **0** (reject happens before persist) |
| Shared files | 12 reused as designed |
| Unique `(product_id, media_uuid)` | Intact |
| Positions | Contiguous 0..n-1; `is_primary` on 0 |
| Rollback | Still `meta.migration = ppk-images` |

996 products are usable. 15 have no cover. `100259` is missing one gallery slot. That is acceptable to ship if those SKUs can wait for replacement files.

---

## Command notes (not the 87-failure cause)

`ProductLocalImageAttacher` catches `Throwable` with an empty body: no SKU, path, or exception text. `laravel.log` has no record of this run.

Dry-run should not be trusted as a MIME/size gate.

---

## Verdict line

| Option | Selected |
|---|---|
| Data issue | **Yes** — 87 one-byte stubs |
| Image processing issue | No |
| Command bug | Observability only (swallowed errors; dry-run false clean) |
| Mapping bug | No |
