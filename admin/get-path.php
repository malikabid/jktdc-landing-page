<?php
// This file helps you find the correct absolute path for .htpasswd
// Visit this file in your browser, copy the path, then delete this file

echo "<h2>Use this path in your .htaccess file:</h2>";
echo "<pre>" . __DIR__ . "/.htpasswd</pre>";
echo "<hr>";
echo "<h3>Update your .htaccess AuthUserFile line to:</h3>";
echo "<pre>AuthUserFile " . __DIR__ . "/.htpasswd</pre>";
?>
