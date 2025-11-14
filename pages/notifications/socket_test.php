<?php
$s = 'webmail.atgroup.es';
$p = 465;
$timeout = 10;
$fp = @fsockopen('ssl://' . $s, $p, $errno, $errstr, $timeout);
if ($fp) {
    echo "OK socket SSL connect to {$s}:{$p}\n";
    fclose($fp);
    exit(0);
}
echo "Failed socket: {$errno} - {$errstr}\n";
exit(2);
