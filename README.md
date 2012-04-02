# FunnyMonkey EPUB packager

This repository hosts the EPUB 3.0 Packager as defined by [IDPF EPUB 3] (http://idpf.org/epub/30)

## Usage

  <?php
  // See example.php for this example and test.php for a more advanced
  // usage example
  include_once(__DIR__ . '/../FunnyMonkey/EPUB/EPUBPackage.php');

  use FunnyMonkey\EPUB\EPUBPackage as FMEPub;
  $epub = new FMEPub;

  // set required DCMES Elements
  // set the unique identifier for your EPUB document
  $epub->metaSetDCMESElement('dc:identifier', 'EPUB Example Doc 2012-04-02');

  // Set the title of your EPub document
  $epub->metaSetDCMESElement('dc:title', 'FMEpub Example Document');

  // Set the author of your EPUB document
  $epub->metaAddDCMESElement('dc:creator', 'Jeff Graham');

  $lorem=<<<LOREM
  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
  eiusmodtempor incididunt labore et dolore magna aliqua. Ut enim ad minim
  veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea
  commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit
  essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat
  nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.
  </p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
  eiusmodtempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
  veniam,quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea
  commodoconsequat. Duis aute irure dolor in reprehenderit in voluptate velit
  essecillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat
  nonproident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
  LOREM;

  $contents = '<!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="../css/bootstrap.css" rel="stylesheet" />
    <link href="../css/bootstrap-responsive.css" rel="stylesheet" />
    <title>Example Page %u</title>
  </head>
  <body>
  <p>This is example Page %u</p>' . $lorem . '
  </p>
  </body></html>';

  for ($i = 1; $i < 5; $i++) {
    $epub->manifestAddItem('page' . $i, 'xhtml/page-' . $i . '.html', 'application/xhtml+xml', 'inline', sprintf($contents, $i, $i));
    $epub->spineAddItemRef('page' . $i);
  }

  // make sure our document passes validation
  try {
    $epub->validate();
  }
  catch (Exception $e) {
    throw($e);
  }

  // provide forwards compatibility for EPUB 2.0 readoers
  $epub->buildNCX();

  //$test->dump();
  $zipname = tempnam('/tmp', 'epub_test') . '.epub';
  print $zipname . "\n";
  try {
    $epub->bundle($zipname);
  }
  catch (Exception $e) {
    print $e . "\n";
  }


## License
[GNU AFFERO GENERAL PUBLIC LICENSE] (http://www.gnu.org/licenses/agpl-3.0.html)

Copyright (C) 2012  Jeff Graham
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.