<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Tahun</title>
    <style>
        /* Mengatur tampilan halaman agar form di bagian tengah */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #f0f2f5;
        }

        /* Mengatur card dengan shadow dan rounded corners */
        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            font-size: 16px;
            color: #555;
        }

        select {
            width: 100%;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #ead022;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #f5e65f;
        }

        /* Style untuk error messages */
        .error-messages {
            margin-top: 10px;
            color: red;
            font-size: 14px;
        }

        .error-messages ul {
            list-style-type: none;
            padding: 0;
        }

        .error-messages li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body style="background-image: url('{{ asset('gambar/background.jpg') }}');">
    <div class="card">
        <h1>Pilih Tahun Input Data</h1>
        <form action="{{ route('set.tahun') }}" method="POST">
            @csrf
            <label for="tahun">Pilih Tahun:</label>
            <select name="tahun" id="tahun">
                @for ($i = 2024; $i <= date('Y') + 5; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
            <button type="submit">Simpan</button>
        </form>

        @if ($errors->any())
            <div class="error-messages">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</body>
</html>
