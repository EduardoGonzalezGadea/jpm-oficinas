<?php
echo "Original gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
ini_set('session.gc_maxlifetime', 7200);
echo "New gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
