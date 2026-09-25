<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard Admin - Pemkab Bombana'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #8B0000;
            --secondary-color: #DAA520;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            width: 250px;
            min-width: 250px;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            background-color: var(--primary-color);
            color: white;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            z-index: 1000;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        .flex-grow-1 {
            min-width: 0;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #660000;
            color: var(--secondary-color);
            border-left: 5px solid var(--secondary-color);
        }

        .topbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
        }

        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: white;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 1.05rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="d-flex">
