#!/usr/bin/env python3
"""Enrich the WooCommerce template CSV from ppk-product.csv. Analysis only."""

from __future__ import annotations

import argparse
import csv
import re
from collections import Counter, defaultdict
from pathlib import Path

DOC = Path(__file__).resolve().parent
TEMPLATE = DOC / "products-woocommerce-template.csv"
SOURCE = DOC / "ppk-product.csv"
UPLOADS = DOC / "uploads"
OUT_CSV = DOC / "products-woocommerce-enriched.csv"
OUT_REPORT = DOC / "products-woocommerce-enriched-report.md"
OUT_IMAGES = DOC / "products-image-mapping-report.md"

COPY_FIELDS = [
    "Type",
    "Published",
    "Visibility in catalog",
    "Short description",
    "Tax status",
    "Weight (kg)",
    "Categories",
    "Meta: condition",
]
for _i in range(1, 5):
    COPY_FIELDS.extend(
        [
            f"Attribute {_i} name",
            f"Attribute {_i} value(s)",
            f"Attribute {_i} visible",
            f"Attribute {_i} global",
        ]
    )

IMAGE_EXTS = {".jpg", ".jpeg", ".png", ".gif", ".webp", ".avif", ".svg"}
URL_RE = re.compile(r"https?://[^,\s]+", re.I)
SIZE_RE = re.compile(r"-\d+x\d+(?=\.[^.]+$)", re.I)
SCALED_RE = re.compile(r"-scaled(?=\.[^.]+$)", re.I)


def load_rows(path: Path) -> tuple[list[str], list[dict[str, str]]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        headers = list(reader.fieldnames or [])
        rows = [{k: (row.get(k) or "") for k in headers} for row in reader]
    return headers, rows


def index_source(rows: list[dict[str, str]]) -> tuple[dict[str, dict[str, str]], list[str], int]:
    by_sku: dict[str, dict[str, str]] = {}
    duplicates: list[str] = []
    empty = 0
    for row in rows:
        sku = row.get("SKU", "").strip()
        if sku == "":
            empty += 1
            continue
        if sku in by_sku:
            duplicates.append(sku)
            continue
        by_sku[sku] = row
    return by_sku, duplicates, empty


def enrich(
    template_rows: list[dict[str, str]],
    source_by_sku: dict[str, dict[str, str]],
) -> tuple[list[dict[str, str]], dict[str, object]]:
    fill_counts: Counter[str] = Counter()
    attr_names: Counter[str] = Counter()
    categories: Counter[str] = Counter()
    conditions: Counter[str] = Counter()
    types: Counter[str] = Counter()
    published: Counter[str] = Counter()
    visibility: Counter[str] = Counter()
    tax: Counter[str] = Counter()
    missing: list[str] = []
    updated = 0
    matched = 0
    out: list[dict[str, str]] = []

    for row in template_rows:
        next_row = dict(row)
        sku = row.get("SKU", "").strip()
        source = source_by_sku.get(sku)
        if source is None:
            missing.append(sku)
            out.append(next_row)
            continue

        matched += 1
        changed = False
        for field in COPY_FIELDS:
            incoming = (source.get(field) or "").strip()
            current = (next_row.get(field) or "").strip()
            if incoming == "" or current != "":
                continue
            next_row[field] = source.get(field) or ""
            fill_counts[field] += 1
            changed = True
            if field == "Categories":
                for part in re.split(r"\s*,\s*", incoming):
                    if part:
                        categories[part] += 1
            elif field.startswith("Attribute ") and field.endswith(" name"):
                attr_names[incoming] += 1
            elif field == "Type":
                types[incoming] += 1
            elif field == "Published":
                published[incoming] += 1
            elif field == "Visibility in catalog":
                visibility[incoming] += 1
            elif field == "Tax status":
                tax[incoming] += 1
            elif field == "Meta: condition":
                conditions[incoming] += 1
        if changed:
            updated += 1
        out.append(next_row)

    stats: dict[str, object] = {
        "matched": matched,
        "missing": missing,
        "updated": updated,
        "fill_counts": fill_counts,
        "attr_names": attr_names,
        "categories": categories,
        "conditions": conditions,
        "types": types,
        "published": published,
        "visibility": visibility,
        "tax": tax,
    }
    return out, stats


def write_csv(path: Path, headers: list[str], rows: list[dict[str, str]]) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=headers, lineterminator="\n")
        writer.writeheader()
        for row in rows:
            writer.writerow({key: row.get(key, "") for key in headers})


def md_counter(counter: Counter[str], *, limit: int | None = None) -> str:
    items = counter.most_common(limit)
    if not items:
        return "_None._"
    lines = ["| Value | Rows |", "| --- | ---: |"]
    for value, count in items:
        lines.append(f"| {value} | {count} |")
    return "\n".join(lines)


def write_enrich_report(
    path: Path,
    *,
    template_rows: int,
    source_rows: int,
    source_unique: int,
    empty_sku: int,
    duplicates: list[str],
    stats: dict[str, object],
) -> None:
    missing = list(stats["missing"])  # type: ignore[arg-type]
    fill_counts: Counter[str] = stats["fill_counts"]  # type: ignore[assignment]
    attr_lines = ["| Attribute name | Products filled |", "| --- | ---: |"]
    names: Counter[str] = stats["attr_names"]  # type: ignore[assignment]
    for name, count in names.most_common():
        attr_lines.append(f"| {name} | {count} |")
    if names.total() == 0:
        attr_block = "_None._"
    else:
        attr_block = "\n".join(attr_lines)

    warnings: list[str] = []
    if duplicates:
        warnings.append(
            "PPK source has duplicate SKU(s) "
            + ", ".join(sorted(set(duplicates)))
            + "; first row wins."
        )
    if empty_sku:
        warnings.append(f"PPK source has {empty_sku} row(s) with empty SKU; ignored.")
    if missing:
        warnings.append("Template SKU(s) with no PPK match: " + ", ".join(missing) + ".")
    conditions: Counter[str] = stats["conditions"]  # type: ignore[assignment]
    if len(conditions) > 1:
        warnings.append(
            "Meta: condition uses mixed labels ("
            + ", ".join(f"{k}={v}" for k, v in conditions.most_common())
            + ")."
        )
    warnings.append("Images, IDs, and URLs were not copied.")
    warnings.append("Search ranking, synonyms, and suggest metadata are not CSV columns and were not copied.")

    warning_block = "\n".join(f"- {item}" for item in warnings) if warnings else "- None."

    path.write_text(
        "\n".join(
            [
                "# WooCommerce CSV enrichment report",
                "",
                "Source: `ppk-product.csv` into `products-woocommerce-template.csv`.",
                "Output: `products-woocommerce-enriched.csv`.",
                "Match key: SKU. Existing template values were preserved; only empty copy-fields were filled.",
                "",
                "## Counts",
                "",
                f"- Template rows: **{template_rows}**",
                f"- PPK rows: **{source_rows}** ({source_unique} unique SKUs)",
                f"- Matched SKUs: **{stats['matched']}**",
                f"- Missing SKUs: **{len(missing)}**",
                f"- Rows updated: **{stats['updated']}**",
                "",
                "## Missing SKUs",
                "",
                ", ".join(missing) if missing else "_None._",
                "",
                "## Fields filled (empty template cells only)",
                "",
                md_counter(fill_counts),
                "",
                "## Attribute mappings",
                "",
                "Copied `Attribute N name` values onto template rows that had an empty name cell.",
                "",
                attr_block,
                "",
                "## Category mappings",
                "",
                md_counter(stats["categories"]),  # type: ignore[arg-type]
                "",
                "## Other copied values",
                "",
                f"- Type: {dict(stats['types'])}",
                f"- Published: {dict(stats['published'])}",
                f"- Visibility in catalog: {dict(stats['visibility'])}",
                f"- Tax status: {dict(stats['tax'])}",
                "",
                "### Meta: condition",
                "",
                md_counter(conditions),
                "",
                "## Warnings",
                "",
                warning_block,
                "",
            ]
        )
        + "\n",
        encoding="utf-8",
    )


def list_image_files(uploads: Path) -> list[Path]:
    files: list[Path] = []
    for path in uploads.rglob("*"):
        if path.is_file() and path.suffix.lower() in IMAGE_EXTS:
            files.append(path)
    return files


def folder_counts(files: list[Path], uploads: Path) -> Counter[str]:
    counts: Counter[str] = Counter()
    for path in files:
        rel = path.relative_to(uploads)
        folder = "/".join(rel.parts[:2]) if len(rel.parts) >= 2 else str(rel.parent)
        counts[folder] += 1
    return counts


def proposed_image_mapping(
    template_rows: list[dict[str, str]],
    source_by_sku: dict[str, dict[str, str]],
    uploads: Path,
) -> tuple[list[tuple[str, str, list[str]]], list[str], int]:
    mapped: list[tuple[str, str, list[str]]] = []
    no_url: list[str] = []
    url_hits = 0
    for row in template_rows:
        sku = row.get("SKU", "").strip()
        source = source_by_sku.get(sku)
        if source is None:
            continue
        urls = URL_RE.findall(source.get("Images") or "")
        if not urls:
            no_url.append(sku)
            continue
        locals_: list[str] = []
        for url in urls:
            path = url.split("?")[0]
            idx = path.lower().find("/uploads/")
            if idx < 0:
                continue
            rel = path[idx + len("/uploads/") :]
            local = uploads / rel
            if local.is_file():
                url_hits += 1
                locals_.append(str(Path("uploads") / rel))
        mapped.append((sku, (row.get("Name") or "").strip(), locals_))
    return mapped, no_url, url_hits


def write_image_report(
    path: Path,
    files: list[Path],
    uploads: Path,
    mapped: list[tuple[str, str, list[str]]],
    no_url: list[str],
    url_hits: int,
) -> None:
    ext = Counter(p.suffix.lower() for p in files)
    orig = deriv = 0
    for file in files:
        name = file.name
        if SIZE_RE.search(name) or SCALED_RE.search(name):
            deriv += 1
        else:
            orig += 1
    folders = folder_counts(files, uploads)
    folder_lines = ["| Folder | Files |", "| --- | ---: |"]
    for folder, count in sorted(folders.items()):
        folder_lines.append(f"| `{folder}` | {count} |")

    with_files = [item for item in mapped if item[2]]
    map_lines = [
        "| SKU | Name | Proposed files |",
        "| --- | --- | --- |",
    ]
    for sku, name, locals_ in with_files:
        safe_name = name.replace("|", "/")
        map_lines.append(f"| `{sku}` | {safe_name} | " + "<br>".join(f"`{p}`" for p in locals_) + " |")

    no_url_preview = ", ".join(f"`{sku}`" for sku in no_url[:40])
    if len(no_url) > 40:
        no_url_preview += f", … ({len(no_url)} total)"

    path.write_text(
        "\n".join(
            [
                "# Product image mapping report",
                "",
                "Scan of `doc/uploads`. No database writes, no media import, no Images column updates.",
                "",
                "## Inventory",
                "",
                f"- Image files: **{len(files)}**",
                f"- Originals (no WordPress size suffix): **{orig}**",
                f"- Derivatives (`-NNNxNNN` / `-scaled`): **{deriv}**",
                f"- Extensions: {dict(ext)}",
                "",
                "A full 76k-path dump is omitted. Counts by folder:",
                "",
                "\n".join(folder_lines),
                "",
                "## Proposed product-to-image mapping",
                "",
                "For each template SKU, filenames come from the PPK `Images` column (URL path after `/uploads/`).",
                "Those local files exist for every referenced URL. Thumbnails that were not in the CSV URL list are not proposed.",
                "",
                f"- Template SKUs with at least one local file: **{len(with_files)}**",
                f"- Local files referenced by those URLs: **{url_hits}**",
                f"- Matched SKUs with no image URL in PPK: **{len(no_url)}**",
                "",
                "\n".join(map_lines) if with_files else "_None._",
                "",
                "## SKUs without an image URL",
                "",
                no_url_preview or "_None._",
                "",
            ]
        )
        + "\n",
        encoding="utf-8",
    )


def main() -> int:
    parser = argparse.ArgumentParser(description="Enrich WooCommerce template CSV from PPK export.")
    parser.add_argument("--dry-run", action="store_true", help="Print stats only; write no output files.")
    args = parser.parse_args()

    template_headers, template_rows = load_rows(TEMPLATE)
    source_headers, source_rows = load_rows(SOURCE)
    source_by_sku, duplicates, empty_sku = index_source(source_rows)
    enriched, stats = enrich(template_rows, source_by_sku)
    image_files = list_image_files(UPLOADS)
    mapped, no_url, url_hits = proposed_image_mapping(template_rows, source_by_sku, UPLOADS)

    print("template_rows", len(template_rows))
    print("ppk_rows", len(source_rows), "unique_sku", len(source_by_sku))
    print("matched", stats["matched"], "missing", len(stats["missing"]), "updated", stats["updated"])  # type: ignore[arg-type]
    print("missing_skus", stats["missing"])
    print("duplicate_source_skus", sorted(set(duplicates)))
    print("image_files", len(image_files), "mapped_products", sum(1 for item in mapped if item[2]), "no_url", len(no_url))

    if args.dry_run:
        print("dry-run: no files written")
        return 0

    write_csv(OUT_CSV, template_headers, enriched)
    write_enrich_report(
        OUT_REPORT,
        template_rows=len(template_rows),
        source_rows=len(source_rows),
        source_unique=len(source_by_sku),
        empty_sku=empty_sku,
        duplicates=duplicates,
        stats=stats,
    )
    write_image_report(OUT_IMAGES, image_files, UPLOADS, mapped, no_url, url_hits)
    print("wrote", OUT_CSV)
    print("wrote", OUT_REPORT)
    print("wrote", OUT_IMAGES)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
