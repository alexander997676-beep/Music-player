<?php
// Configuration
$jsonFile = 'songs.json';
$uploadDir = 'uploads/';

// Create uploads folder if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? 'Unknown Title';
    $artist = $_POST['artist'] ?? 'Unknown Artist';
    
    // File Upload Handling
    $songFile = $_FILES['song_file'];
    $artFile = $_FILES['art_file'];
    
    if ($songFile['error'] === UPLOAD_ERR_OK) {
        $songName = time() . '_' . basename($songFile['name']);
        $songPath = $uploadDir . $songName;
        
        // Handle Album Art (Optional)
        $artPath = 'https://i.supaimg.com/c195cd6a-f837-4a7d-aceb-82d207dc69a7.jpg'; // Default
        if ($artFile['error'] === UPLOAD_ERR_OK) {
            $artName = time() . '_' . basename($artFile['name']);
            move_uploaded_file($artFile['tmp_name'], $uploadDir . $artName);
            $artPath = $uploadDir . $artName;
        }

        // Upload Song
        if (move_uploaded_file($songFile['tmp_name'], $songPath)) {
            // Read existing JSON
            $currentData = file_get_contents($jsonFile);
            $arrayData = json_decode($currentData, true);
            if (!$arrayData) $arrayData = [];

            // Add new song
            $newSong = [
                'title' => htmlspecialchars($title),
                'artist' => htmlspecialchars($artist),
                'url' => $songPath,
                'albumArt' => $artPath
            ];

            array_unshift($arrayData, $newSong); // Add to top
            
            // Save back to JSON
            file_put_contents($jsonFile, json_encode($arrayData, JSON_PRETTY_PRINT));
            $message = "<div class='text-green-400 mb-4'>Song Uploaded Successfully!</div>";
        } else {
            $message = "<div class='text-red-400 mb-4'>Failed to move audio file.</div>";
        }
    } else {
        $message = "<div class='text-red-400 mb-4'>Please select a valid song file.</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $index = $_GET['delete'];
    $currentData = file_get_contents($jsonFile);
    $arrayData = json_decode($currentData, true);
    
    if (isset($arrayData[$index])) {
        // Optional: Delete actual files from server
        // unlink($arrayData[$index]['url']); 
        
        array_splice($arrayData, $index, 1);
        file_put_contents($jsonFile, json_encode($arrayData, JSON_PRETTY_PRINT));
        header("Location: admin.php");
        exit;
    }
}

// Fetch Songs for List
$songs = json_decode(file_get_contents($jsonFile), true);
if (!$songs) $songs = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Music Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #09090b; color: #fff; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        input[type="text"], input[type="file"] { background: rgba(0,0,0,0.3); border: 1px solid #333; color: white; }
    </style>
</head>
<body class="p-5 md:p-10">

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-[#DB2777]">Music Admin Panel</h1>

        <!-- Upload Form -->
        <div class="glass-card p-6 rounded-2xl mb-8">
            <h2 class="text-xl font-semibold mb-4">Upload New Song</h2>
            <?php echo $message; ?>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Song Title</label>
                        <input type="text" name="title" required class="w-full p-2 rounded-lg focus:outline-none focus:border-[#DB2777]">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Artist Name</label>
                        <input type="text" name="artist" required class="w-full p-2 rounded-lg focus:outline-none focus:border-[#DB2777]">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Audio File (MP3)</label>
                        <input type="file" name="song_file" accept="audio/*" required class="w-full p-2 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Cover Art (Image)</label>
                        <input type="file" name="art_file" accept="image/*" class="w-full p-2 rounded-lg">
                    </div>
                </div>

                <button type="submit" class="bg-[#DB2777] hover:bg-pink-600 text-white px-6 py-2 rounded-lg font-bold transition w-full md:w-auto">
                    Upload Song
                </button>
            </form>
        </div>

        <!-- Song List -->
        <div class="glass-card p-6 rounded-2xl">
            <h2 class="text-xl font-semibold mb-4">Existing Songs (<?php echo count($songs); ?>)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-400">
                    <thead class="text-xs uppercase bg-white/5 text-gray-200">
                        <tr>
                            <th class="px-4 py-3">Art</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Artist</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songs as $index => $song): ?>
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="px-4 py-3">
                                <img src="<?php echo $song['albumArt']; ?>" class="w-10 h-10 rounded object-cover">
                            </td>
                            <td class="px-4 py-3 text-white font-medium"><?php echo $song['title']; ?></td>
                            <td class="px-4 py-3"><?php echo $song['artist']; ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="?delete=<?php echo $index; ?>" onclick="return confirm('Are you sure?')" class="text-red-400 hover:text-red-300">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(empty($songs)): ?>
                    <p class="text-center py-4">No songs uploaded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
