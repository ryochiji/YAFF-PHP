<?php
$debug = !empty($_GET['debug']);
define('DEBUG', $debug);
if ($debug) {
    header("Content-type: text/plain");
}
require_once('utils.inc.php');
require_once('SimpleDB.class.php');

$sdb = new SimpleDB('');

$r = $sdb->createDomain('UnitTest');
$list = $sdb->listDomains();
print_r($list);
Utils::printResult('CreateDomain', in_array('UnitTest',$list), $r);

$sdb->setDomain('UnitTest');

$a = array('name'=>'Bob', 'age'=>30);
$r = $sdb->putAttributesAssoc('bob', $a);
Utils::printResult('putAttributesAssoc', true, $r);

$r = $sdb->getAttributes('bob');
Utils::printResult('getAttributes', $r['name']=='Bob', $r);

$sdb->setSingleAttr('bob', 'foo', 'bar');
$r = $sdb->getAttributes('bob');
Utils::printResult('setSingleAttr', $r['foo']=='bar');


$a = array(
    'alice' => array('name'=>'Alice', 'age'=>50),
    'charlie' => array('name'=>'Charlie', 'age'=>20)
    );
$r = $sdb->batchPutAttributesAssoc($a);
Utils::printResult('batchPutAttributesAssoc', true, $r);

$r = $sdb->query("SELECT * FROM UnitTest WHERE age>'25'");
Utils::printResult('query', count($r)==2, $r);

$r = $sdb->singleAttrQuery('name', 'Alice');
Utils::printResult('singleAttrQuery - single val', count($r), $r);

$r = $sdb->singleAttrQuery('name', array('Alice','Bob'));
Utils::printResult('singleAttrQuery - multival', count($r)==2, $r);

$r = $sdb->deleteDomain('UnitTest');
$list = $sdb->listDomains();
Utils::printResult('DeleteDomain', !in_array('UnitTest',$list), $r);

echo '<pre>'.print_r($sdb->getLog(),1).'</pre>';

/*
$bi = new BookInfo();
$r = $bi->getBookInfoFromWS('055321246x');
if ($debug) var_dump($r);
Utils::printResult('ItemLookup',!empty($r['title']));

$r = $bi->searchBooks('walden');
if ($debug) var_dump($r);
Utils::printResult('search title',!empty($r));

$r = $bi->searchBooks('walden', 'thoreau');
if ($debug) var_dump($r);
Utils::printResult('search title & author', !empty($r));
*/


/*
$r = $bi->getBatchInfo(array('0553380958', '0393329127'));
print_r($r);
*/

?>
