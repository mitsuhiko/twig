<?php
require '../Twig.php';


$loader = new Twig_Loader('templates', 'cache');

$index = $loader->getTemplate('index.html');
$index->display(array('seq' => array(1, 2, 3, 4, '<foo>')));
