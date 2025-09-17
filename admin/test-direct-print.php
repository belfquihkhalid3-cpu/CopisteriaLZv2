<?php
// Test avec configuration forcée
$testContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj 4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Times-Roman>>endobj 5 0 obj<</Length 55>>stream\nBT /F1 12 Tf 100 700 Td (TEST 3 COPIES CONFIG) Tj ET\nendstream\nendobj xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n0000000125 00000 n \n0000000348 00000 n \n0000000576 00000 n \ntrailer<</Size 6/Root 1 0 R>>\nstartxref\n636\n%%EOF";

$testFile = realpath('../uploads/documents/') . '\config_test.pdf';
file_put_contents($testFile, $testContent);

$printerName = "Samsung M332x 382x 402x Series";
$acrobatPath = "C:\Program Files\Adobe\Acrobat DC\Acrobat\Acrobat.exe";

echo "<h3>Test Configuration Forcée</h3>";
echo "Fichier: $testFile<br><br>";

// Méthode 1: Boucle simple (3 copies séparées)
echo "<h4>Méthode 1: 3 impressions séparées</h4>";
for ($i = 1; $i <= 3; $i++) {
    $command = '"' . $acrobatPath . '" /t "' . $testFile . '" "' . $printerName . '"';
    echo "Copie $i: $command<br>";
    $output = shell_exec($command . " 2>&1");
    echo "Résultat $i: " . ($output ?: "Envoyé") . "<br>";
    
    if ($i < 3) {
        echo "Attente 8 secondes...<br>";
        sleep(8);
    }
}

echo "<br><h4>Méthode 2: PowerShell avec configuration d'imprimante</h4>";

$psScript = '
$printerName = "Samsung M332x 382x 382x Series"
$filePath = "' . $testFile . '"
$acrobatPath = "C:\Program Files\Adobe\Acrobat DC\Acrobat\Acrobat.exe"

try {
    # Obtenir l\'imprimante
    $printer = Get-WmiObject -Class Win32_Printer -Filter "Name=\'$printerName\'"
    
    if ($printer) {
        Write-Host "Configuration imprimante: $printerName"
        
        # Sauvegarder imprimante par défaut
        $defaultPrinter = Get-WmiObject -Class Win32_Printer | Where-Object {$_.Default -eq $true}
        
        # Définir comme imprimante par défaut
        $printer.SetDefaultPrinter()
        
        # Méthode A: 3 processus séparés
        Write-Host "Lancement 3 impressions séparées..."
        for ($i = 1; $i -le 3; $i++) {
            Write-Host "Impression $i/3"
            
            $process = Start-Process -FilePath $acrobatPath -ArgumentList "/t", $filePath, $printerName -WindowStyle Hidden -PassThru
            
            # Attendre que le processus démarre
            Start-Sleep -Seconds 2
            
            # Attendre qu\'il se termine (max 30s)
            $process.WaitForExit(30000)
            
            Write-Host "Impression $i terminée"
            
            if ($i -lt 3) {
                Write-Host "Pause 10 secondes..."
                Start-Sleep -Seconds 10
            }
        }
        
        # Restaurer imprimante par défaut
        if ($defaultPrinter) {
            $defaultPrinter.SetDefaultPrinter()
        }
        
        Write-Host "TERMINÉ: 3 copies envoyées"
        
    } else {
        Write-Host "ERREUR: Imprimante non trouvée"
    }
} catch {
    Write-Host "ERREUR: $($_.Exception.Message)"
}
';

$tempPs = sys_get_temp_dir() . '\config_test.ps1';
file_put_contents($tempPs, $psScript);

$command = "powershell -ExecutionPolicy Bypass -File \"$tempPs\"";
echo "PowerShell lancé...<br>";
$output = shell_exec($command . " 2>&1");
echo "Résultat:<br><pre>$output</pre>";

unlink($tempPs);

echo "<hr><p><strong>Vérifie si 3 pages se sont imprimées !</strong></p>";
?>