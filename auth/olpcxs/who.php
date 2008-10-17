<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$dbh = new PDO('sqlite:' . $CFG->olpcxsdb);
$rs = $dbh->query('select * from laptops');

print_header('Who are you?');

print '<ul>';
foreach ($rs as $row) {

print '<li>';
print '<a href="' . $CFG->wwwroot . '/auth/olpcxs/grantaccess.php?nickname=' . s($row['nickname']) . '">';
p($row['nickname']);

print "</a></li>\n";
}
print '</ul>';

unset($rs);
unset($dbh);

print_footer();

?>