<?php
echo "<h2>Informations Terminal</h2>";
echo "<p><strong>IP Address:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>";
echo "<p><strong>Host Name:</strong> " . gethostname() . "</p>";
echo "<p><strong>User Agent:</strong> " . $_SERVER['HTTP_USER_AGENT'] . "</p>";
echo "<p><strong>Server IP:</strong> " . $_SERVER['SERVER_ADDR'] . "</p>";

// Si derrière un proxy
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    echo "<p><strong>Real IP:</strong> " . $_SERVER['HTTP_X_FORWARDED_FOR'] . "</p>";
}
?>