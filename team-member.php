<?php
$nama_matakuliah = "Pemrograman Web";
$kelas           = "E";

$anggota_kelompok = [
    [
        'name' => 'Ezra Mighty Lumentut',
        'nim'  => '240211060032' 
    ],
    [
        'name' => 'Daffa Mirza Johanson',
        'nim'  => '240211060030' 
    ],
    [
        'name' => 'Ezra Steve Simauw',
        'nim'  => '240211060003' 
    ],
    [
        'name' => 'Erick Orlando Keintjem',
        'nim'  => '240211060031' 
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anggota Kelompok - Inspire Lite</title>
    <style>
        :root {
            --bg-body: #f4f5f7;
            --inspire-red: #b91c1c; 
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .jumbotron-banner {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            color: #ffffff;
            width: 100%;
            max-width: 900px;
            padding: 30px 40px;
            border-radius: 16px;
            box-sizing: border-box;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 35px;
        }

        .jumbotron-banner h1 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .jumbotron-banner .meta-info {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 900px;
            box-sizing: border-box;
        }

        .mhs-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .mhs-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .avatar-circle {
            width: 55px;
            height: 55px;
            background-color: #fee2e2;
            color: var(--inspire-red);
            border-radius: 50%;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .mhs-name {
            font-size: 1.05rem;
            font-weight: 600;
            margin: 0 0 6px 0;
            color: var(--text-main);
        }

        .mhs-nim {
            font-size: 0.88rem;
            color: var(--text-muted);
            font-family: ui-monospace, SFMono-Regular, monospace;
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body>

    <div class="jumbotron-banner">
        <h1>Anggota Kelompok</h1>
        <div class="meta-info">
            Mata Kuliah: <?php echo htmlspecialchars($nama_matakuliah); ?> &bull; Kelas: <?php echo htmlspecialchars($kelas); ?>
        </div>
    </div>

    <div class="grid-container">
        <?php foreach ($anggota_kelompok as $mhs): 
            $words = explode(" ", $mhs['name']);
            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
        ?>
            <div class="mhs-card">
                <div class="avatar-circle">
                    <?php echo $initials; ?>
                </div>
                <div class="mhs-name"><?php echo htmlspecialchars($mhs['name']); ?></div>
                <div class="mhs-nim"><?php echo htmlspecialchars($mhs['nim']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
