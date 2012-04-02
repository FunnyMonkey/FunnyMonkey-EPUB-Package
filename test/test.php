<?php

include_once(__DIR__ . '/../FunnyMonkey/EPUB/EPUBPackage.php');
use FunnyMonkey\EPUB\EPUBPackage as FMEPub;
$test = new FMEPub;
$test->metaSetDCMESElement('dc:identifier', 'FM test epub 2012-03-29');
$test->metaSetDCMESElement('dc:title', 'Funnymonkey Test Time');
$test->metaAddDCMESElement('dc:creator', 'Jeff Graham');

$contents = '<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="../css/bootstrap.css" rel="stylesheet" />
<link href="../css/bootstrap-responsive.css" rel="stylesheet" />
</head>
<body>
<div >
  <h2>
    FunnyMonkey Epub 3.0 test document.
  </h2>
  <img src="../images/services.png" />
  <div class="content clearfix">
    <div class="field field-name-body field-type-text-with-summary field-label-hidden"><div class="field-items"><div class="field-item even">
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
    </div>
  </div>
</div>
<div class="field field-name-field-fmpp-portfolio field-type-entityreference field-label-above">
<div class="field-label">Pages</div>
<div class="field-items">
<div class="field-item even"><a href="page-2.html">page 2</a></div>
<div class="field-item odd"><a href="page-3.html">page 3</a></div>
<div class="field-item even"><a href="page-4.html">page 4</a></div>
<div class="field-item odd"><a href="page-5.html">page 5</a></div>
</div>
</div>  </div>



</div>
</body>
</html>
';
$test->manifestAddItem('page1', 'xhtml/page-1.html', 'application/xhtml+xml', 'inline', $contents);
$test->manifestAddItem('page2', 'xhtml/page-2.html', 'application/xhtml+xml', 'inline', '<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="../css/bootstrap.css" rel="stylesheet" />
<link href="../css/bootstrap-responsive.css" rel="stylesheet" />
</head>
<body>This is page 2
<img src="../images/our-team.png" />
</body></html>');
$test->manifestAddItem('page3', 'xhtml/page-3.html', 'application/xhtml+xml', 'inline', '<!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><link href="../css/bootstrap.css" rel="stylesheet" /><link href="../css/bootstrap-responsive.css" rel="stylesheet" /><title>page 4</title></head><body>This is page 3<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p></body></html>');
$test->manifestAddItem('page4', 'xhtml/page-4.html', 'application/xhtml+xml', 'inline', '<!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><link href="../css/bootstrap.css" rel="stylesheet" /><link href="../css/bootstrap-responsive.css" rel="stylesheet" /><title>page 5</title></head><body>This is page 4<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p></body></html>');
$test->manifestAddItem('page5', 'xhtml/page-5.html', 'application/xhtml+xml', 'inline', '<!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><link href="../css/bootstrap.css" rel="stylesheet" /><link href="../css/bootstrap-responsive.css" rel="stylesheet" /><title>page 6</title></head><body>This is page 5<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p></body></html>');

$test->manifestAddItem('bootstrap', 'css/bootstrap.css', 'text/css', 'file', __DIR__ . '/bootstrap/css/bootstrap.css');
$test->manifestAddItem('bootstrap-responsive', 'css/bootstrap-responsive.css', 'text/css', 'file', __DIR__ . '/bootstrap/css/bootstrap-responsive.css');

$test->manifestAddItem('services', 'images/services.png', 'image/png', 'file', __DIR__ . '/services.png');
$test->manifestAddItem('our-team', 'images/our-team.png', 'image/png', 'file', __DIR__ . '/our-team.png');

$toc = '<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="../css/bootstrap.css" rel="stylesheet" />
<link href="../css/bootstrap-responsive.css" rel="stylesheet" />
</head>
<body>
<nav epub:type="toc" id="toc">
<h1 class="title">Table of Contents</h1>
<ol>
<li><a href="page-1.html">nav page 1</a></li>
<li><a href="page-2.html">nav page 2</a></li>
<li><a href="page-3.html">nav page 3</a></li>
<li><a href="page-4.html">nav page 4</a></li>
<li><a href="page-5.html">nav page 5</a></li>
</ol>
</nav>
</body>
</html>';
$test->manifestAddItem('nav', 'xhtml/nav.html', 'application/xhtml+xml', 'inline', $toc, NULL, array('nav'));

$test->spineAddItemRef('page1');
$test->spineAddItemRef('page2');
$test->spineAddItemRef('page3');
$test->spineAddItemRef('page4');
$test->spineAddItemRef('page5');

//$test->dump();
$test->validate();

$test->buildNCX();

//$test->dump();
$zipname = tempnam('/tmp', 'epub_test') . '.epub';
print $zipname . "\n";
try {
  $test->bundle($zipname);
}
catch (Exception $e) {
  print $e . "\n";
}

