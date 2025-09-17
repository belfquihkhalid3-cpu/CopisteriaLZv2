<?php
// Lister toutes les imprimantes disponibles
$command = 'wmic printer get name,status,default /format:list';
$output = shell_exec($command);
echo "<pre>Imprimantes disponibles:\n";
echo $output;
echo "</pre>";

// Alternative avec PowerShell
echo "<h3>Avec PowerShell:</h3>";
$command2 = 'powershell -Command "Get-Printer | Format-Table Name,PrinterStatus,Shared"';
$output2 = shell_exec($command2);
echo "<pre>$output2</pre>";
?>