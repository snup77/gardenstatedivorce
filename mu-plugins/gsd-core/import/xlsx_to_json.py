#!/usr/bin/env python3
"""
Converts the "Data Fields" attorney-directory spreadsheet (.xlsx) into a single
JSON file consumed by the `wp gsd import-data` WP-CLI command.

Uses only the Python standard library (xlsx is just a zip of XML files), so no
openpyxl/pandas install is required.

Usage:
    python3 xlsx_to_json.py "/path/to/Data Fields.xlsx" attorneys.json
"""

import json
import re
import sys
import xml.etree.ElementTree as ET
import zipfile

NS = {'a': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}

# field_name -> coercion type, per sheet. Anything not listed defaults to str.
FIELD_TYPES = {
    'firms': {
        'firm_id': 'int',
    },
    'offices': {
        'office_id': 'int',
        'firm_id': 'int',
        'gbp_rating': 'float',
        'gbp_review_number': 'int',
    },
    'attorneys': {
        'attorney_id': 'int',
        'license_year': 'int',
        'firm_id': 'int',
        'primary_office_id': 'int',
        'nj_matrimonial_cert': 'bool',
        'aaml_fellowship': 'bool',
        'av_preeminent': 'bool',
        'super_lawyers': 'bool',
        'best_lawyers': 'bool',
        'best_lawyers_start_year': 'int',
        'chambers': 'bool',
        'avvo_rating': 'float',
        'avvo_review_number': 'int',
        'avv_stars': 'float',
    },
    'education': {
        'attorney_id': 'int',
        'graduation_year': 'int',
    },
    'associations': {
        'attorney_id': 'int',
        'start_year': 'int',
        'end_year': 'int',
    },
    'attorney_locations': {
        'attorney_id': 'int',
    },
}

SHEET_KEYS = {
    'Attorneys': 'attorneys',
    'Attorney_Locations': 'attorney_locations',
    'Education': 'education',
    'Associations': 'associations',
    'Firms': 'firms',
    'Offices': 'offices',
}


def col_to_num(col):
    num = 0
    for c in col:
        num = num * 26 + (ord(c) - ord('A') + 1)
    return num


def cell_ref(ref):
    m = re.match(r'([A-Z]+)(\d+)', ref)
    return m.group(1), int(m.group(2))


def coerce(value, type_name):
    if value == '' or value is None:
        return None
    if type_name == 'int':
        return int(float(value))
    if type_name == 'float':
        return float(value)
    if type_name == 'bool':
        return value in ('1', 1, 'true', 'True')
    return str(value).strip()


def load_shared_strings(zf):
    if 'xl/sharedStrings.xml' not in zf.namelist():
        return []
    root = ET.fromstring(zf.read('xl/sharedStrings.xml'))
    shared = []
    for si in root.findall('a:si', NS):
        text = ''.join(t.text or '' for t in si.findall('.//a:t', NS))
        shared.append(text)
    return shared


def load_sheet_names(zf):
    root = ET.fromstring(zf.read('xl/workbook.xml'))
    rid_ns = {'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'}
    sheets = []
    for sheet in root.findall('.//a:sheets/a:sheet', NS):
        rid = sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
        sheets.append((sheet.get('name'), rid))

    rels_root = ET.fromstring(zf.read('xl/_rels/workbook.xml.rels'))
    rel_ns = {'p': 'http://schemas.openxmlformats.org/package/2006/relationships'}
    rid_to_target = {
        rel.get('Id'): rel.get('Target')
        for rel in rels_root.findall('p:Relationship', rel_ns)
    }

    return [(name, rid_to_target[rid]) for name, rid in sheets]


def parse_sheet(zf, target, shared):
    root = ET.fromstring(zf.read(f'xl/{target}'))
    rows_out = []
    max_col = 0
    for row in root.findall('.//a:sheetData/a:row', NS):
        row_data = {}
        for c in row.findall('a:c', NS):
            col, _ = cell_ref(c.get('r'))
            colnum = col_to_num(col)
            max_col = max(max_col, colnum)
            t = c.get('t')
            v = c.find('a:v', NS)
            val = v.text if v is not None else ''
            if t == 's' and val != '':
                val = shared[int(val)]
            row_data[colnum] = val
        rows_out.append(row_data)

    table = []
    for row_data in rows_out:
        table.append([row_data.get(cn, '') for cn in range(1, max_col + 1)])
    return table


def sheet_to_records(table, type_map):
    if not table:
        return []
    header = [h for h in table[0] if h]
    records = []
    for row in table[1:]:
        if not any(cell != '' for cell in row[:len(header)]):
            continue
        record = {}
        for i, field_name in enumerate(header):
            raw = row[i] if i < len(row) else ''
            record[field_name] = coerce(raw, type_map.get(field_name, 'str'))
        records.append(record)
    return records


def main():
    if len(sys.argv) != 3:
        print('Usage: python3 xlsx_to_json.py <input.xlsx> <output.json>', file=sys.stderr)
        sys.exit(1)

    input_path, output_path = sys.argv[1], sys.argv[2]

    with zipfile.ZipFile(input_path) as zf:
        shared = load_shared_strings(zf)
        sheet_names = load_sheet_names(zf)

        data = {}
        for name, target in sheet_names:
            key = SHEET_KEYS.get(name)
            if not key:
                continue
            table = parse_sheet(zf, target, shared)
            data[key] = sheet_to_records(table, FIELD_TYPES.get(key, {}))

    with open(output_path, 'w') as f:
        json.dump(data, f, indent=2)

    counts = ', '.join(f'{k}={len(v)}' for k, v in data.items())
    print(f'Wrote {output_path} ({counts})')


if __name__ == '__main__':
    main()
