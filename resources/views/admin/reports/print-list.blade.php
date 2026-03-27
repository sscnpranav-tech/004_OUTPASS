<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 6 Sigma Scaled Typography with Maximum Compression */
        body {
            font-family: Arial, sans-serif !important;
            font-size: 13px !important;
            line-height: 1.1 !important;
            color: black;
            background: white;
        }
        .heading {
            font-size: 15px !important;
            font-weight: bold;
        }
        table {
            font-size: 13px !important;
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 2px 4px !important; /* Extreme tight fit */
        }

        @media print {
            body { -webkit-print-color-adjust: exact; margin: 0; }
            @page { margin: 10mm; size: A4 portrait; }
        }
    </style>
</head>
<body class="max-w-5xl mx-auto p-0 text-left">

    <div class="text-center mb-2 uppercase leading-tight">
        <div class="heading">{{ env('APP_NAME', 'SAINIK SCHOOL CHANDRAPUR') }}</div>
        <div class="heading underline">{{ $title }}</div>
    </div>

    <div class="flex justify-between font-bold mb-1">
        <div>Date - {{ date('d-M-Y') }}</div>
    </div>

    <table>
        <thead class="bg-gray-200">
            <tr>
                <th class="text-center w-12">Ser</th>
                <th>Roll Number</th>
                <th>Name of Cadet</th>
                <th class="text-center">Class</th>
                <th class="text-center">Gender</th>
                <th>House</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($outpasses as $house => $houseResponses)
                <tr class="bg-gray-100">
                    <td colspan="6" class="font-bold">
                        House: {{ strtoupper($house) }}
                        <span class="float-right">[ {{ $houseResponses->count() }} cadets ]</span>
                    </td>
                </tr>
                @foreach($houseResponses as $index => $outpass)
                    <tr>
                        <td class="text-center">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                        <td class="font-bold">{{ $outpass->rollno }}</td>
                        <td class="uppercase">{{ $outpass->name }}</td>
                        <td class="text-center">{{ $outpass->class }}</td>
                        <td class="text-center uppercase">{{ $outpass->gender }}</td>
                        <td>{{ strtoupper($outpass->house) }}</td>
                    </tr>
                    @php $grandTotal++; @endphp
                @endforeach
            @endforeach
            <tr class="bg-gray-800 text-white">
                <td colspan="6" class="font-bold text-right border-black">
                    Total: {{ $grandTotal }} cadets
                </td>
            </tr>
        </tbody>
    </table>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>
