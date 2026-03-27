<!DOCTYPE html>
<html>
<head>
    <title>Notesheet</title>
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
            padding: 2px 4px !important; /* Maintained tight fit */
        }
        p {
            margin: 2px 0 !important; /* Maintained paragraph compression */
        }

        @media print {
            body { -webkit-print-color-adjust: exact; margin: 0; }
            @page { margin: 10mm; size: A4 portrait; }
        }
    </style>
</head>
<body class="max-w-4xl mx-auto p-0">

    <div class="text-center mb-1">
        <div class="heading uppercase">{{ env('APP_NAME', 'SAINIK SCHOOL CHANDRAPUR') }}</div>
        <div class="heading underline uppercase">NOTESHEET</div>
    </div>

    <div class="flex justify-between font-bold mb-1">
        <div>Date - {{ date('d-M-Y') }}</div>
        <div>Page - 1 of 1</div>
    </div>

    <table>
        <thead>
            <tr class="text-center bg-gray-50">
                <th class="w-12">Ref</th>
                <th>Subject / Title</th>
                <th class="w-12">PUC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td class="align-top border-x border-black" style="padding: 4px;">

                    <div class="font-bold text-center uppercase mb-2" style="font-size: 14px;">
                        FOR THE APPROVAL TO ISSUE OUTPASS FROM <br>
                        {{ \Carbon\Carbon::parse($schedule->from_date)->format('d-M-Y') }} TO {{ \Carbon\Carbon::parse($schedule->to_date)->format('d-M-Y') }} TO THE CADETS
                    </div>

                    <p>1. The file pertains to the list of cadets going for out-pass from <strong>{{ \Carbon\Carbon::parse($schedule->from_date)->format('d-M-Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($schedule->to_date)->format('d-M-Y') }}</strong>.</p>
                    <p>2. The class-wise count of the cadets is as follows:</p>

                    <div class="flex justify-center my-2">
                        @if(empty($houses))
                            <div class="italic text-center w-full">No application data available.</div>
                        @else
                            <table class="text-center w-3/4">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="uppercase">Class</th>
                                        @foreach($houses as $house)
                                            <th class="uppercase">{{ $house }}</th>
                                        @endforeach
                                        <th class="uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matrix as $row)
                                        <tr class="{{ $loop->last ? 'font-bold bg-gray-100' : '' }}">
                                            <td>{{ $row['class'] }}</td>
                                            @foreach($houses as $house)
                                                <td>{{ $row[$house] }}</td>
                                            @endforeach
                                            <td class="font-bold">{{ $row['Total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <p>3. Total <strong>{{ $final_grand }}</strong> cadets are moving on out-pass.</p>
                    <p>4. The list of cadets moving on out-pass is attached.</p>
                    <p class="mb-2">5. Submitted for approval and issue of out-pass to the cadets.</p>

                    <table class="border-none w-full mb-4">
                        @foreach($houses as $house)
                            <tr class="border-none">
                                <th class="text-left py-1 w-1/3 border-none">{{ strtoupper($house) }}</th>
                                <td class="w-1/3 border-none text-gray-500">HM1 Signature</td>
                                <td class="border-none text-gray-500">HM2 Signature</td>
                            </tr>
                        @endforeach
                    </table>

                    <div class="font-bold flex flex-col gap-8 mt-6 pl-2">
                        <div>Vice Principal</div>
                        <div>Principal</div>
                    </div>

                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
