const XLSX = require('xlsx');

const inputPath = process.argv[2];

if (!inputPath) {
  console.error('Usage: node scripts/spip_xlsx_to_json.cjs <xlsx-path>');
  process.exit(1);
}

const workbook = XLSX.readFile(inputPath, { cellDates: false });

function clean(value) {
  if (value === undefined || value === null) return null;
  const text = String(value).replace(/\r\n/g, '\n').trim();
  return text === '' ? null : text;
}

function rows(sheetName) {
  const sheet = workbook.Sheets[sheetName];
  if (!sheet) return [];
  return XLSX.utils.sheet_to_json(sheet, {
    header: 1,
    defval: null,
    raw: false,
  });
}

const users = rows('Users').slice(1)
  .filter((row) => clean(row[0]))
  .map((row) => ({
    user_id: clean(row[0]),
    name: clean(row[1]),
    password_pm: clean(row[2]),
    role: clean(row[3]) || 'User',
    allowed_satker: clean(row[4]),
    password_pk: clean(row[5]),
    status_pk: clean(row[6]) || 'Tidak Aktif',
    link_download: clean(row[7]),
    spreadsheet_url: clean(row[8]),
    gid: clean(row[9]),
    edit_url: clean(row[11]),
  }));

const sub_unsurs = rows('SubUnsur').slice(1)
  .filter((row) => clean(row[3]) && clean(row[3]) !== 'Kode SubUnsur')
  .map((row) => ({
    kode: clean(row[0]),
    sub_unsur: clean(row[1]),
    nomor: clean(row[2]),
    kode_sub_unsur: clean(row[3]),
    uraian_parameter: clean(row[4]),
    spip: clean(row[5]),
    mri: clean(row[6]),
    iepk: clean(row[7]),
  }));

const kertas_kerja = [];
for (const sheetName of workbook.SheetNames) {
  if (!sheetName.toLowerCase().startsWith('hasil pengujian_')) continue;

  const userId = sheetName.substring('Hasil Pengujian_'.length);
  rows(sheetName).slice(1)
    .filter((row) => clean(row[0]) && clean(row[1]))
    .forEach((row) => {
      kertas_kerja.push({
        user_id: clean(userId),
        kode_sub_unsur: clean(row[0]),
        grade: clean(row[1]),
        kriteria: clean(row[2]),
        penjelasan: clean(row[3]),
        cara_pengujian: clean(row[4]),
        uraian_hasil_pengujian: clean(row[5]),
        grade_pm: clean(row[6]),
        grade_pk: clean(row[7]),
        kluster_aoi: clean(row[8]),
        uraian_aoi: clean(row[9]),
        kluster_penyebab: clean(row[10]),
        uraian_penyebab: clean(row[11]),
      });
    });
}

const klusters = rows('Kluster').slice(1)
  .filter((row) => clean(row[0]) || clean(row[1]))
  .map((row) => ({
    kluster_aoi: clean(row[0]),
    kluster_penyebab: clean(row[1]),
  }));

process.stdout.write(JSON.stringify({
  users,
  sub_unsurs,
  kertas_kerja,
  klusters,
}));
