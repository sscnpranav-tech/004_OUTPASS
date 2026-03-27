<!DOCTYPE html>
<html>
<head>
    <title>Outpass Slips</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 6 Sigma Scaled Typography with Maximum Compression */
        body {
            font-family: Arial, sans-serif !important;
            font-size: 13px !important;
            line-height: 1.1 !important;
            color: black;
            background: white;
            margin: 0;
        }
        .heading {
            font-size: 15px !important;
            font-weight: bold;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; }
            @page { margin: 10mm; size: A4 portrait; }
            .slip-card { break-inside: avoid; page-break-inside: avoid; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="max-w-4xl mx-auto p-0">

    @foreach($groupedOutpasses as $house => $houseResponses)
        <div class="text-center mb-2 mt-4 uppercase heading underline">House : {{ $house }}</div>

        <div class="grid grid-cols-2 gap-2">
            @foreach($houseResponses as $outpass)
                <div class="slip-card border-2 border-black rounded p-2 relative h-[200px]">

                    <div class="text-center mb-1 border-b border-black pb-1">
                        <div class="heading uppercase">{{ env('APP_NAME', 'SAINIK SCHOOL CHANDRAPUR') }}</div>
                        <div class="font-bold text-gray-700">Outpass {{ \Carbon\Carbon::parse($outpass->from_date)->format('d-M-Y') }}</div>
                    </div>

                    <div class="text-right font-bold mb-2 leading-tight">
                        <div>Date : {{ \Carbon\Carbon::parse($outpass->from_date)->format('d-M-Y') }}</div>
                        <div>Time : {{ \Carbon\Carbon::parse($outpass->from_time)->format('h:i A') }} to {{ \Carbon\Carbon::parse($outpass->to_time)->format('h:i A') }}</div>
                    </div>

                    <div class="space-y-1 mb-4">
                        <div><b>Roll No:</b> {{ $outpass->rollno }}</div>
                        <div class="uppercase"><b>Name:</b> {{ $outpass->name }}</div>
                        <div><b>Class:</b> {{ $outpass->class }}</div>
                        <div class="uppercase"><b>House:</b> {{ $outpass->house }}</div>
                    </div>

                    <div class="absolute bottom-2 right-2 text-center mt-2">
                        <div class="border-t border-black w-28 pt-1 font-bold text-[11px]">Auth Signature</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="page-break"></div>
    @endforeach

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>
