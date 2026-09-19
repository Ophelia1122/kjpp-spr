<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Dua lembar kwitansi identik dalam satu halaman A4, dipisah garis
           putus-putus untuk digunting (2026-09-15, feedback user). Tinggi
           tiap lembar dikunci (.copy) supaya garis potong selalu jatuh di
           tengah halaman, berapa pun panjang nama klien/rekeningnya. */
        @page { margin: 22px 40px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 9.8px; color: #000; line-height: 1.35; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .underline { text-decoration: underline; }
        .small { font-size: 8.4px; }

        .copy { height: 516px; overflow: hidden; }

        .box { border: 1.1px solid #000; padding: 5px 7px; }
        .doc-title { font-size: 19px; font-weight: bold; text-align: center; }
        .doc-sub { font-size: 11.5px; font-style: italic; text-align: center; }

        .frame { border: 1.1px solid #000; margin-top: 9px; }
        .frame td { padding: 4px 9px; vertical-align: top; }
        .label-col { width: 130px; }
        .dots { border-bottom: 1px dotted #999; }
        .dots-row td { border-bottom: 1px dotted #999; }
        table.breakdown td { padding: 0 4px 0 0; }

        .amount-box { font-size: 18px; font-weight: bold; }
        .checkbox { display: inline-block; width: 8px; height: 8px; border: 1px solid #000; margin-right: 3px; vertical-align: middle; }

        /* Garis potong. Glyph gunting butuh DejaVu Sans (bawaan dompdf) —
           Helvetica tidak punya karakter itu. */
        .cut { position: relative; height: 26px; }
        .cut-line { position: absolute; top: 12px; left: -20px; right: -20px; border-top: 1px dashed #666; }
        .cut-label { position: absolute; top: 5px; left: 0; right: 0; text-align: center; }
        .cut-label span { background: #fff; padding: 0 6px; font-family: 'DejaVu Sans', sans-serif; font-size: 7px; color: #666; }
    </style>
</head>
<body>

    @include('pdf._kwitansi_copy')

    <div class="cut">
        <div class="cut-line"></div>
        <div class="cut-label"><span>&#9986; gunting di sini</span></div>
    </div>

    @include('pdf._kwitansi_copy')

</body>
</html>
