<?
session_start();
require_once("site_engine/engine.php");
require_once("site_engine/ucp.php");

$page = 'UCP';

$engine = new engine;

$body = $engine->compile_body($page);
$head = $engine->compile_head();

echo '<!DOCTYPE html>';
echo '<html lang="ru">';
echo '<head>';
echo $head;
echo '</head>';
echo '<body>';
echo $body;
echo '</body>';
echo '</html>';

?>