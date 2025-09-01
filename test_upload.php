<?php
// Fichier de test pour vérifier l'upload
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <h2>Test Upload Direct</h2>
    
    <!-- Info session -->
    <div>
        <h3>Session Info:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <!-- Test upload form -->
    <form action="api/upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="files" accept=".pdf,.doc,.docx,.txt">
        <button type="submit">Test Upload</button>
    </form>
    
    <!-- Test permissions -->
    <div>
        <h3>Permissions uploads/:</h3>
        <?php
        $uploadDir = 'uploads/documents/';
        if (is_dir($uploadDir)) {
            echo "✅ Dossier exists<br>";
            echo "Writable: " . (is_writable($uploadDir) ? "✅ Yes" : "❌ No") . "<br>";
            echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";
        } else {
            echo "❌ Dossier uploads/documents/ n'existe pas<br>";
            if (mkdir($uploadDir, 0755, true)) {
                echo "✅ Dossier créé<br>";
            } else {
                echo "❌ Impossible de créer le dossier<br>";
            }
        }
        ?>
    </div>
    
    <!-- Test base données -->
    <div>
        <h3>Database Test:</h3>
        <?php
        try {
            require_once 'config/database.php';
            $test = fetchOne("SELECT COUNT(*) as count FROM files");
            echo "✅ Database connection OK<br>";
            echo "Files in DB: " . $test['count'] . "<br>";
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "<br>";
        }
        ?>
    </div>
    
</body>
</html>