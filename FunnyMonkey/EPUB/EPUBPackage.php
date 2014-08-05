<?php

/**
 * @file
 * Defines the fmEpubPackage class for creating EPUB 3.0 documents
 *
 * @author    Jeff Graham
 * @version   version 0.1
 * @date      2012
 * @copyright Copyright (C) 2012 Jeff Graham
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @todo 3.4.15. The bindings Element
 * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-bindings-elem
 *
 * @todo 3.4.16. The mediaType Element
 * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-mediaType-elem
 */
namespace FunnyMonkey\EPUB;

/**
 * Class implementor for http://idpf.org/epub/30/spec/epub30-publications.html
 *
 * This class is used to generate EPub Documents please review the example in
 * test/test.php for details on how to use this.
 *
 * Requires PHP > 5.3
 */
class EPUBPackage {
  // used to store manifest files content or locations keyed by href
  private $files = array();

  // convenience variables to access various points in our document
  private $package = NULL;
  private $metadata = NULL;
  private $manifest = NULL;
  private $spine = NULL;

  // convenience to run xpath queries against dom
  private $xpath = NULL;

  // pattern to use when title can't be derived from content
  private $titlePattern = 'Page %u';

  // Fullpath to zip executable
  private $zipExecutable = '/usr/bin/zip';

  // Success code for zip execution (unix success is 0)
  private $zipSuccess = 0;

  // Zip executable options. Defaults are for 'Info ZIP' zip 3.0
  //  !filename will be replaced with the zipfilename
  //  Assumed that all paths are relative to the CWD
  private $zipArgs = '-UN=UTF8 -r "!filename" .';

  // Similar to zipArgs, but for the mimetype file. This needs to have no
  // compression so it requirest a seperate add.
  //  !filename will be replaced with the zipfilename
  //  Assumed that all paths are relative to the CWD
  private $zipArgsMimetype = '-UN=UTF8 -0 "!filename" ./mimetype';

  /**
   * DOMDocument $dom stores the domdocument for content.opf
   */
  public $dom = NULL;


  /**
   * String where manifest items will be placed
   */
  protected $contentDir = 'OEBPS';

  /**
   * name of package document
   */
  protected $filename = 'content.opf';

  /**
   * Defines the unique identifier element Unique identifier
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#attrdef-package-unique-identifier
   */
  const FMEPUB_UNIQIDID = 'pub-id';

  /**
   * Defines the unique identifier value
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-package-metadata-identifiers
   */
  const FMEPUB_UNIQID = 'http://example.com/unique-identifier';

  /**
   * Defines the EPUB packge version
   */
  const FMEPUB_VERSION = '3.0';

  /**
   * Defines the default package language
   */
  const FMEPUB_LANG = 'en';

  /**
   * Defines a default title
   */
  const FMEPUB_TITLE = 'Default Title';

  /**
   * Defines a default identifier tag
   */
  const FMEPUB_EPUB_IDENTIFIER = 'fmEPub 0.1.x';

  public function __construct() {
    // create the initial stub for our package document
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $package = $dom->createElement('package');
    $package->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.idpf.org/2007/opf');
    $package->setAttribute('id', 'package');
    $package->setAttribute('version', $this::FMEPUB_VERSION);
    $package->setAttribute('xml:lang', $this::FMEPUB_LANG);
    $package->setAttribute('unique-identifier', $this::FMEPUB_UNIQIDID);
    $dom->appendChild($package);

    // create the metadata element
    $metadata = $dom->createElement('metadata');
    $metadata->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');

    $metadata->setAttribute('id', 'metadata');
    $package->appendChild($metadata);

    $identifier = $dom->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', $this::FMEPUB_UNIQID);
    $identifier->setAttribute('id', $this::FMEPUB_UNIQIDID);
    $metadata->appendChild($identifier);

    // @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-metadata-identifiers-pid
    //CCYY-MM-DDThh:mm:ssZ
    date_default_timezone_set('UTC');
    $modified = $dom->createElement('meta', date('Y-m-d\TH:i:s\Z'));
    $modified->setAttribute('property', 'dcterms:modified');
    $metadata->appendChild($modified);

    $title = $dom->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:title', $this::FMEPUB_TITLE);
    $metadata->appendChild($title);

    $language = $dom->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:language', $this::FMEPUB_LANG);
    $metadata->appendChild($language);

    $manifest = $dom->createElement('manifest');
    $manifest->setAttribute('id', 'manifest');
    $package->appendChild($manifest);

    $spine = $dom->createElement('spine');
    $spine->setAttribute('id', 'spine');
    $package->appendChild($spine);

    $this->dom = $dom;
    $this->package = $package;
    $this->metadata = $metadata;
    $this->manifest = $manifest;
    $this->spine = $spine;
    $this->xpath = new \DOMXpath($this->dom);
    $this->xpath->registerNamespace('opf', 'http://www.idpf.org/2007/opf');
    $this->xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
  }

  /*
   * 3.4.1. The package Element
   * http://idpf.org/epub/30/spec/epub30-publications.html#sec-package-elem
  */

  /**
  * Sets the package version. Defaults to 3.0
  *
  * Specifies the EPUB specification version to which the Publication conforms.
  *
  * The attribute must have the value 3.0 to indicate compliance with this
  * version of the specification.
  *
  * @param $version a string indicating the standard that this package conforms
  * to
  * @return bool indicating if the version was correctly set
  *
  */
  public function packageSetVersion($version) {
    if ($this->package->setAttribute('version', $version)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the current package version
   *
   * @return The value of the version attribute, or an empty string if no
   * version attribute is found.
   */
  public function packageGetVersion() {
    return $this->package->getAttribute('version');
  }

  /**
  * Sets the unique identifier id attribute and adjusts the document structure
  * accordingly.
  *
  * The Package Document's author is responsible for including a primary
  * identifier that is unique to one and only one particular EPUB Publication.
  * This Unique Identifier, whether chosen or assigned, must be stored in a
  * dc:identifier element in the Package metadata and be referenced as the
  * Unique Identifier in the package element unique-identifier attribute.
  *
  * Although not static, changes to the Unique Identifier for a Publication
  * should be made as infrequently as possible. New identifiers should not be
  * issued when updating metadata, fixing errata or making other minor changes
  * to the Publication.
  *
  * @param $identifier a string that is unique to this epub document
  * @return bool indicating if the unique identifier was successfully set
  */
  public function packageSetUniqueIdentifier($identifier) {
    // first update metadata dc:identifier's id value
    $dcidentifier = $this->xpath->query('//*[@id="' . $this->packageGetUniqueIdentifier() . '"]')->item(0);
    if (!$dcidentifier->setAttribute('id', $identifier)) {
      return FALSE;
    }

    // now update the package document identifier
    if ($this->package->setAttribute('unique-identifier', $identifier)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the current package unique-identifier
   *
   * @return The value of the unique-identifier attribute, or an empty string
   * if no unique-identifier attribute is found.
   */
  public function packageGetUniqueIdentifier() {
    return $this->package->getAttribute('unique-identifier');
  }

  /**
   * Set any additional prefix mappings not specified.
   *
   * The prefix attribute defines additional prefix mappings not reserved by
   * the specification.
   *
   * The prefix attribute must not be used to redefine the default vocabulary
   * or the predefined prefixes.
   *
   * The prefix '_' is reserved for future compatibility with RDFa [RDFa10]
   * processing, so must not be defined.
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-prefix-attr
   *
   * @param $prefix The following example shows prefixes for the Friend of a
   * Friend (foaf) and DBPedia (dbp) vocabularies being declared using the
   * prefix attribute. Note the new line separating prefixes.
   * "foaf: http://xmlns.com/foaf/spec/ \ndbp: http://dbpedia.org/ontology/"
   */
  public function packageSetPrefix($prefix) {
    if ($this->package->setAttribute('prefix', $prefix)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the current package prefix
   *
   * @return The value of the prefix attribute, or an empty string
   * if no prefix attribute is found.
   */
  public function packageGetPrefix() {
    return $this->package->getAttribute('prefix');
  }

  /**
   * Set the language used in contents and attribute values.
   *
   * Specifies the language used in the contents and attribute values of the
   * carrying element and its descendants, as defined in section 2.12 Language
   * Identification of [XML].
   *
   * @see http://www.w3.org/TR/REC-xml/#sec-lang-tag
   *
   * @param $lang language code conforming to http://www.w3.org/TR/REC-xml/#sec-lang-tag
   */
  public function packageSetLang($lang) {
    if ($this->package->setAttribute('xml:lang', $lang)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the current language prefix
   *
   * @return The value of the xml:lang attribute, or an empty string
   * if no xml:lang attribute is found.
  */
  public function packageGetLang() {
    return $this->package->getAttribute('xml:lang');
  }

  /**
   * Specifies the base text direction of the content and attribute values of
   * the carrying element and its descendants.
   *
   * Inherent directionality specified using [Unicode] takes precedence over
   * this attribute.
   *
   * @param $dir Allowed values are ltr (left-to-right) or rtl (right-to-left).
   * @throws Exception if $dir is invalid
   * @return bool indicating if direction is set correctly
   */
  public function packageSetDir($dir) {
    if (in_array($dir, array('ltr', 'rtl'))) {
      if ($this->package->setAttribute('dir', $dir)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      throw new \Exception('Invalid dir attribute');
    }
  }

  /**
   * Retrieves the current package dir
   *
   * @return The value of the dir attribute, or an empty string if no dir
   * attribute is found.
   */
  public function packageGetDir() {
    return $this->package->getAttribute('dir');
  }

  /*
   * 3.4.2. The metadata Element
   * http://idpf.org/epub/30/spec/epub30-publications.html#sec-metadata-elem
   */

  /**
   * Validate attribute values
   *
   * @param $attribute the attribute key to validate the supplied value for
   * @param @value the value to be validated.
   * @todo validate xml:lang as per requirements
   * @return bool TRUE indicating the supplied value is valid for the given attribute
   */
  public function metaValidateAttributeValue($attribute, $value) {
    switch($attribute) {
      case 'dir':
        if (in_array($value, array('ltr', 'rtl'))) {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;
      default:
        return TRUE;
    }
  }

  /**
   * Returns a list of all allowed DCMES tags and details.
   *
   * This returns the tagname and each tag's respective allowed attributes and
   * if they are required or not. If required is TRUE cardinality is 1 or many.
   * If required is FALSE cardinality is 0,1, or many
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-dcidentifier
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-dclanguage
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-dctitle
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-dcmes-optional
   * @return array of allowed DCMES Tags keyed by tagname with additional attributes
   */
  public function metaAllowedDCMESTags() {
    // most tags have the same options
    $defaults = array(
        'attributes' => array('id', 'xml:lang', 'dir'),
        'required' => FALSE,
    );

    $tags = array(
      'dc:identifier' => array(
        'attributes' => array('id'),
        'required' => TRUE,
      ),
      'dc:title' => array(
        'attributes' => array('id', 'xml:lang', 'dir'),
        'required' => TRUE,
      ),
      'dc:language' => array(
        'attributes' => array('id'),
        'required' => TRUE,
      ),
      'dc:contributor' => $defaults,
      'dc:coverage' => $defaults,
      'dc:creator' => $defaults,
      'dc:date' => $defaults,
      'dc:description' => $defaults,
      'dc:format' => $defaults,
      'dc:publisher' => $defaults,
      'dc:relation' => $defaults,
      'dc:rights' => $defaults,
      'dc:source' => $defaults,
      'dc:subject' => $defaults,
      'dc:type' => $defaults,
    );
    return $tags;
  }

  /**
   * Validates tags, attributes, and values
   */
  public function metaValidateTagAttributes($tag, $attributes = array(), $value = NULL) {
    $tags = $this->metaAllowedDCMESTags();
    if (!in_array($tag, array_keys($tags))) {
      throw new \Exception('Invalid DCMES tag');
    }

    if (!empty($attributes)) {
      foreach($attributes as $attribute => $value) {
        if (!in_array($attribute, $tags[$tag]['attributes'])) {
          throw new \Exception(sprintf('Invalid attribute for tag "%s"', $tag));
        }
        if (!$this->metaValidateAttributeValue($attribute, $value)) {
          throw new \Exception(sprintf('Invalid value "%s" supplied for attribute "%s" in tag "%s"', $value, $attribute, $element));
        }
      }
    }
  }


  /**
   * Add a DCMES element
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-opf-dcmes-optional
   *
   * @param $tag string an allowed DCMES tag
   *  @see EPUBPackage::metaAllowedDCMESTags()
   * @param $value string the value of the tag
   * @param $attributes array of values keyed by attribute name see metaAllowedDCMESTags
   *   to see what attributes are available for what tags. The use of id tags is
   *   recommended and will be required once meta marc:relators are implemented.
   * @return DOMNode of the newly added DCMES element
   */
  public function metaAddDCMESElement($tag, $value, $attributes = array()) {
    $tags = $this->metaAllowedDCMESTags();
    try {
      $this->metaValidateTagAttributes($tag, $attributes);
      $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
      // now add our element and attributes
      $new_element = $this->dom->CreateElementNS('http://purl.org/dc/elements/1.1/', $tag, $value);
      if (!empty($attributes)) {
        foreach($attributes as $attribute => $value) {
          $new_element->setAttribute($attribute, $value);
        }
      }

      return $metadata->appendChild($new_element);
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Ensure there is exactly one element of the given name and it is set to the
   * given value.
   *
   * @param $tag a valid DCMES element tagname
   * @param $value the value of the new node
   * @param $attributes array of valid attributes and values for the element.
   *   note that if $element is dc:identifier the id attribute will be forced
   * @return DOMNode of the new DCMES Element
   */
  public function metaSetDCMESElement($tag, $value, $attributes = array()) {
    $tags = $this->metaAllowedDCMESTags();
    try {
      // force dc:identifier id value to the packageUniqueIdentifier
      if ($tag == 'dc:identifier') {
        $attributes['id'] = $this->packageGetUniqueIdentifier();
      }

      $this->metaValidateTagAttributes($tag, $attributes);

      /// remove all tags (required tags will leave one element)
      $this->metaRemoveDCMESElementsByTagName($tag);

      if ($tags[$tag]['required']) {
        $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
        $new_element = $this->dom->CreateElementNS('http://purl.org/dc/elements/1.1/', $tag, $value);
        if (!empty($attributes)) {
          foreach ($attributes as $attribute => $value) {
            $new_element->setAttribute($attribute, $value);
          }
        }

        $current_element = $this->metaGetDCMESElementsByTagName($tag)->item(0);
        if (empty($current_element)) {
          return $metadata->appendChild($new_element);
        }
        else {
          if ($metadata->replaceChild($new_element, $current_element)) {
            // replaceChild returns the node replaced we return the new node
            // for consistency
            return $new_element;
          }
        }
      }
      else {
        return $this->metaAddDCMESElement($tag, $value, $attributes);
      }
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Return all DCMES tags of the given element name
   *
   * @param $tag string element name to match
   * @throws Exception if supplied $element is an invalid DCMES tag.
   * @return DOMNodeList of matching elements.
   */
  public function metaGetDCMESElementsByTagName($tag) {
    if (in_array($tag, array_keys($this->metaAllowedDCMESTags()))) {
      $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
      return $this->xpath->query('//' . $tag, $metadata);
    }
    else {
      throw new \Exception('Invalid DCMES tag');
    }
  }

  /**
   * Return matching DCMES tag if found.
   *
   * @param $id string the id attribute value of the element in question
   * @return DOMNode matching id or NULL if any number other than 1 element
   *  matches id.
   */
  public function metaGetDCMESElementById($id) {
    $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
    $result = $this->xpath->query('//*[@id="' . $id . '"]', $metadata);

    // id should be unique if result > 1 return NULL
    if ($result->length == 1) {
      return $result->item(0);
    }
    else {
      return NULL;
    }
  }

  /**
   * Remove all allowed metadata DOM Elements of given type
   *
   * If the given tag is required we leave the first element in
   * place and remove all subsequent elements.
   *
   * @param $tag a DCMES tagname to remove tags for
   * @return array an array DOMNodes removed from the DOM.
   */
  public function metaRemoveDCMESElementsByTagName($tag) {
    $return = array();
    $tags = $this->metaAllowedDCMESTags();
    if (!in_array($tag, array_keys($tags))) {
      throw new \Exception('Invalid DCMES tag');
    }

    // if tag is required leave the first element in place
    $limit = ($tags[$tag]['required']) ? 1 : 0;

    $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
    $elements = $this->xpath->query('//' . $tag, $metadata);
    // count down as length will change
    $i = $elements->length;
    while ($i > $limit) {
      $return[] = $this->dom->removeChild($elements->item(($i-1)));
      $i--;
    }
    return $return;
  }

  /**
   * Remove a child node by id
   *
   * @param $id string id of element to remove
   * @throws Exception if id corresponds to  package unique-identifier element
   * @return DOMNode removed or NULL if element does not exist
   */
  public function metaRemoveDCMESElementById($id) {
    if ($id == $this->packageGetUniqueIdentifier()) {
      throw new \Exception('Attempt to remove required unique-identifier element');
    }

    $element = $this->metaGetDCMESElementById($id);
    if ($element === NULL) {
      return NULL;
    }
    else {
      return $this->dom->removeChild($element);
    }
  }

  /**
   * Return a concatenated string of all DCMES elements matching the given tag.
   *
   * @param $tag string the tagname to return a string for.
   * @param $seperator string to use when concatenating elements together
   * @return string with all matching elements concatenated togheter.
   */
  public function metaGetDCMESElementString($tag, $seperator = ' ') {
    $elements = $this->metaGetDCMESElementsByTagName($tag);
    $elementpieces = array();
    if (!empty($elements)) {
      foreach($elements as $item) {
        $elementpieces[] = $item->nodeValue;
      }
      return implode($seperator, $elementpieces);
    }
    else {
      return '';
    }
  }

  // @todo meta tags as per http://idpf.org/epub/30/spec/epub30-publications.html#sec-meta-elem
  //      marc:relators

  // @todo link elements as per http://idpf.org/epub/30/spec/epub30-publications.html#sec-link-elem

  /**
   * Defines all core media types for epub 3.0
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#tbl-core-media-types
   * @return array of allowed core Media types as defined by epub 3.0.
   */
  public function coreMediaTypes() {
    return array(
      'image/gif',
      'image/jpeg',
      'image/png',
      'image/svg+xml',
      'application/xhtml+xml',
      'application/x-dtbncx+xml',
      'application/vnd.ms-opentype',
      'application/font-woff',
      'application/smil+xml',
      'application/pls+xml',
      'audio/mpeg',
      'audio/mp4',
      'text/css',
      'text/javascript',
    );
  }

  /**
   * Return a list of allowed manifest Item properties
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-item-property-values
   * @return array of allowed manifest item properties.
   */
  public function manifestItemProperties() {
    return array(
      'cover-image',      // zero or one
      'mathml',           // zero or more
      'nav',              // exactly one
      'remote-resources', // zero or more
      'scripted',         // zero or more
      'svg',              // zero or more
      'switch',           // zero or more
    );
  }

  /**
   * Add an item to the manifest.
   *
   * @param $id a unique identifier for the element. This will be used to
   *   reference the item later via the spine & derivative nav elements.
   * @param $href a unique relative href locator for the element. This will be
   *   used when building the file components and will be used as the final
   *   location of the
   * @param $mediatype the mediatype value will be used to set media-type
   * @param type string indicating if contents are 'inline' or 'file' if file
   *  $contents should be the filepath. If inline $contents should be the value.
   * @param $contents contents of the file (will be used in binary safe mode)
   *   or a filename with the location of the file.
   * @param fallback required if mediatype is not a core content type. This
   *   should begin a fallback chain that terminates in an EPub core content type
   * @param $properties string additional properties to set.
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-item-elem
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-item-property-values
   * @return DOMNode of the newly added manifest item
   */
  public function manifestAddItem($id, $href, $mediatype,  $type = 'inline', $contents, $fallback = NULL, $properties = array()) {
    $manifest = $this->xpath->query('//*[@id="manifest"]')->item(0);

    if (!in_array($mediatype, $this->coreMediaTypes()) && empty($fallback)) {
      // non-core media types are required to provide fallback
      throw new \Exception('No fallback provided for non core media type: ' . $mediatype);
    }

    // @todo check if audio/video are provided remotely and force fallback if remote
    //   see also: http://idpf.org/epub/30/spec/epub30-publications.html#sec-resource-locations

    $new_item = $this->dom->createElement('item');
    $new_item->setAttribute('id', $id);
    $new_item->setAttribute('href', $href);
    $new_item->setAttribute('media-type', $mediatype);

    if (!empty($fallback)) {
      // check and make sure this is a valid id then add it
      $element = $this->xpath->query('//*[@id="' . $fallback . '"]', $manifest)->item(0);
      if (!empty($element)) {
        $new_item->setAttribute('fallback', $fallback);
      }
      else {
        // invalid fallback identifier
        throw new \Exception('Invalid fallback identifier for non-core media type');
      }
    }

    if (!empty($properties)) {
      $check = array_intersect($properties, $this->manifestItemProperties());
      if (count($check) === count($properties)) {
        $new_item->setAttribute('properties', implode(' ', $properties));
      }
      else {
        // invalid property in property list
        throw new \Exception('Invalid property in property list');
      }
    }

    if ($type == 'file' && !is_file($contents)) {
      throw new \Exception('Manifest type set to "file" but could not find file: "' . $contents . '"');
    }

    $this->files[$href]['type'] = $type;
    $this->files[$href]['contents'] = $contents;
    return $manifest->appendChild($new_item);
  }

  /**
   * Remove an item from the manifest
   *
   * @param $id the id value of the item element to remove
   * @return DOMNode returns the removed item
   */
  public function manifestRemoveItemById($id) {
    $manifest = $this->xpath->query('//*[@id="manifest"]')->item(0);
    $item = $this->xpath->query('//*[@id="' . $id . '"]', $manifest)->item(0);
    try {
      return $manifest->removeChild($item);
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Return all manifest items.
   *
   * @return DOMNodeList of all item elements in the manifest.
   */
  public function manifestGetItems() {
    $manifest = $this->xpath->query('//*[@id="manifest"]')->item(0);
    return $this->xpath->query('//item', $manifest);
  }

  /**
   * Return a specific manifest item by id.
   *
   * @param $id string the id of the manifest item to return.
   * @return DOMNode
   */
  public function manifestGetItem($id) {
    $manifest = $this->xpath->query('//*[@id="manifest"]')->item(0);
    return $this->xpath->query('//item[@id="' . $id . '"]', $manifest)->item(0);
  }

  /**
   * Get a list of available item properties.
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-itemref-property-values
   * @return array of allowed Spine Itemref properties.
   */
  public function spineItemProperties() {
    return array(
      'page-spread-left', // zero or more (exclusive to page-spread-right)
      'page-spread-right', // zero or more (exclusive to page-spread-left)
    );
  }

  /**
   * Add an existing manifest item to the spine
   *
   * @see http://idpf.org/epub/30/spec/epub30-publications.html#sec-itemref-elem
   * @param $idref a valid id string pointing to an item element in the manifest
   * @param $linear default is yes, indicating whether or not this is a primary
   * navigation item and should be rendered as part of the navigation structure.
   * @param $id string indicating the id of this element.
   * @param $properties additional properties to indicate split page spreads.
   * @return DOMNode of the newly added spine itemref
   */
  public function spineAddItemRef($idref, $linear = 'yes', $id = NULL, $properties = array()) {
    $spine = $this->xpath->query('//*[@id="spine"]')->item(0);
    $itemref = $this->dom->createElement('itemref');

    $manifest = $this->xpath->query('//*[@id="manifest"]')->item(0);

    $item = $this->xpath->query('//item[@id="' . $idref . '"]');
    if (!empty($item)) {
      $itemref->setAttribute('idref', $idref);
      $itemref->setAttribute('linear', $linear);

      if (!empty($id)) {
        $itemref->setAttribute('id', $id);
      }

      if (!empty($properties)) {
        $check = array_interset($properties, $this->spineItemProperties());
        if (count($check) === count($properties)) {
          if (!in_array('page-spread-right') && !in_array('page-spread-left')) {
            $itemref->setAttribute('properties', implode(' ', $properties));
          }
          else {
            throw new \Exception('cannot set both page-spread-left and page-spread-right on the same item');
          }
        }
        else {
          // invalid property in property list
          throw new \Exception('Invalid property in the spine property list');
        }
      }

      return $spine->appendChild($itemref);
    }
    else {
      // invalid idref
      throw new \Exception('Invalid idref provided');
    }
  }

  /**
   * Return all itemrefs in the spine
   *
   * @return DOMNodeList of matching itemrefs in the spine.
   */
  public function spineGetItems() {
    $spine = $this->xpath->query('//*[@id="spine"]')->item(0);
    return $this->xpath->query('//itemref', $spine);
  }

// @todo set mediaoverlay http://idpf.org/epub/30/spec/epub30-mediaoverlays.html


  /**
   * Validate the build so far and make sure all requirements are met.
   */
  public function validate() {
   $this->dom->normalizeDocument();

    // verify exactly one element with id package:unique-identifier
    if (!$this->package->hasAttribute('unique-identifier')) {
      throw new \Exception('No unique-identifier found in package element.');
    }
    elseif ($this->xpath->query('//*[@id="' . $this->packageGetUniqueIdentifier() . '"]')->length > 1) {
      throw new \Exception('Could not find item matching unique-identifier ' . $this->packageGetUniqueIdentifier() . ' in manifest.');
    }

    // verify package:version is present (and valid)
    if (!$this->package->hasAttribute('version')) {
      throw new \Exception('No version found in package element.');
    }

    // verify metadata is first child of package
    if ($this->package->childNodes->item(0)->tagName != 'metadata') {
      throw new \Exception('Metadata is not first child of package');
    }

    // verify metadata->dc:identifier is present
    $metadata = $this->xpath->query('//*[@id="metadata"]')->item(0);
    if ($metadata->getElementsByTagName('identifier')->length < 1) {
      throw new \Exception('No dc:identifier present in metadata element');
    }

    // verify metadata->dc:title is present
    if ($metadata->getElementsByTagName('title')->length < 1) {
      throw new \Exception('No dc:title present in metadata element');
    }

    // verify metadata->dc:language is present
    if ($metadata->getElementsByTagName('language')->length < 1) {
      throw new \Exception('No dc:language present in metadata element');
    }
    else {
      // @todo verify dc:language conforms to RFC5646
    }

    // verify manifest is the second child of package
    if ($this->package->childNodes->item(1)->tagName != 'manifest') {
      throw new \Exception('Manifest is not second child of package');
    }

    // verify manifest contains at least one item element
    if ($this->xpath->query('//item', $this->manifest)->length < 1) {
      throw new \Exception('Manifest does not contain any item elements');
    }

    // verify each item element has
      // id (at least one of which is 'nav')
      // href
      // fallback (where necessary)

    $navcount = 0;
    foreach($this->manifest->childNodes as $node) {
      if ($node->hasAttribute('id')) {
        if ($node->getAttribute('id') == 'nav') {
          $navcount++;
        }
      }
      else {
        throw new \Exception('Manifest item missing id attribute');
      }

      if (!$node->hasAttribute('href')) {
        throw new \Exception('Manifest item(' .$node->getAttribute('id') . ') missing href attribute');
      }

      if (!$node->hasAttribute('media-type')) {
        throw new \Exception('Manifest item(' .$node->getAttribute('id') . ') missing media-type attribute');
      }
      else {
        if (!in_array($node->getAttribute('media-type'), $this->coreMediaTypes())) {
          // non-core media type it must provide a fallback
          if (!$node->hasAttribute('fallback')) {
            throw new \Exception('Manifest item(' .$node->getAttribute('id') . ') is non-core media-type but does not provide fallback');
          }

          // @todo follow the fallback chain and verify it terminates in a coreMediaType
        }
      }
    }

    // verify spine is the third child of package
    if ($this->dom->documentElement->childNodes->item(2)->tagName != 'spine') {
      throw new \Exception('Spine is not third child of package');
    }

    // verify toc matches the ncx document and is present exactly once
  }

  /**
   * Build the forwards compatible NCX document and add to the manifest.
   *
   * This is used primarily for forwards compatability for epub readers prior
   * to 3.0.
   */
  public function buildNCX() {
    try {
      $this->validate();
    } catch (Exception $e) {
      throw $e;
    }

    $ncxDoc = new \DOMDocument('1.0', 'utf-8');

    $root = $ncxDoc->createElementNS('http://www.daisy.org/z3986/2005/ncx/', 'ncx');
    $root->setAttribute('version', '2005-1');
    $root->setAttribute('xml:lang', $this->packageGetLang());

    $ncxDoc->appendChild($root);

    // add head and required meta elements
    $head = $ncxDoc->createElement('head');
    $meta = $ncxDoc->createElement('meta');
    $meta->setAttribute('content', $this->metaGetDCMESElementById($this->packageGetUniqueIdentifier())->nodeValue);
    $meta->setAttribute('name', 'dtb:uid');
    $head->appendChild($meta);

    // @todo: review which of the following meta elements are required and which
    // are part of best practice.
    $meta = $ncxDoc->createElement('meta');
    $meta->setAttribute('name', 'epub-creator');
    $meta->setAttribute('content', $this::FMEPUB_EPUB_IDENTIFIER);
    $head->appendChild($meta);
    $meta = $ncxDoc->createElement('meta');
    $meta->setAttribute('name', 'dtb:depth');
    $meta->setAttribute('content', '2');
    $head->appendChild($meta);
    $meta = $ncxDoc->createElement('meta');
    $meta->setAttribute('name', 'dtb:totalPageCount');
    $meta->setAttribute('content', '0');
    $head->appendChild($meta);
    $meta = $ncxDoc->createElement('meta');
    $meta->setAttribute('name', 'dtb:maxPageNumber');
    $meta->setAttribute('content', '0');
    $head->appendChild($meta);
    $root->appendChild($head);

    $titletext = $ncxDoc->createElement('text', $this->metaGetDCMESElementString('dc:title'));
    $title = $ncxDoc->createElement('docTitle');
    $title->appendChild($titletext);
    $root->appendChild($title);

    $authortext = $ncxDoc->createElement('text', $this->metaGetDCMESElementString('dc:creator'));
    $author = $ncxDoc->createElement('docAuthor');
    $author->appendChild($authortext);
    $root->appendChild($author);


    // create NavMap as copy of spine
    $navMap = $ncxDoc->createElement('navMap');
    $i = 1; // page counter for naming elements
    foreach ($this->spineGetItems() as $node) {
      if ($node->hasAttribute('idref')) {
        $page = $this->manifestGetItem($node->getAttribute('idref'));
        if ($page->hasAttribute('href') && $page->hasAttribute('media-type') && $node->hasAttribute('linear')) {
          $title = '';
          if ($node->getAttribute('linear') == 'yes') {
            $navPoint = $ncxDoc->createElement('navPoint');
            $navPoint->setAttribute('id', 'navpoint-'. $i);
            $navPoint->setAttribute('playOrder', $i);
            $href = $page->getAttribute('href');

            // Attempt to find the document title.
            $pageDom = new \DOMDocument('1.0', 'utf-8');
            @$pageDom->loadHTML($this->files[$href]['contents']);
            $xpath = new \DOMXpath($pageDom);
            $match = $xpath->query("//title");
            if ($match->length === 1) {
              $navText = $ncxDoc->createElement('text', $match->item(0)->nodeValue);
            }
            else {
              // fallback to generic title
              $navText = $ncxDoc->createElement('text', $this->getGenericTitle($i));
            }
            $navLabel = $ncxDoc->createElement('navLabel');
            $content = $ncxDoc->createElement('content');
            $content->setAttribute('src', $page->getAttribute('href'));

            $navLabel->appendChild($navText);
            $navPoint->appendChild($navLabel);
            $navPoint->appendChild($content);
            $navMap->appendChild($navPoint);
            $i++;
          }
        }
      }
      else {
        throw new \Exception('Invalid spine item found while trying to build NCX.');
      }
    }
    $root->appendChild($navMap);

    // add ncx to manifest
    $this->manifestAddItem('ncx', 'toc.ncx', 'application/x-dtbncx+xml', 'inline', $ncxDoc->saveXML());

    // add ncx as toc to spine element
    $this->spine->setAttribute('toc', 'ncx');
  }

  /**
   * Helper function used to get generic title when building NCX
   *
   * we may not be able to derive a proper title for a document, but we will
   * need to display something.
   *
   * @return string A generic title with a provided index.
   */
  protected function getGenericTitle($i) {
    return sprintf($this->getTitlePattern(), $i);
  }

  /**
   * Get title pattern used to replace generic titles
   *
   * @return string current title pattern.
   */
  public function getTitlePattern() {
    return $this->titlePattern;
  }

  /**
   * Set a new title pattern.
   *
   * This should conform to sprintf() convention and will be called with a
   * single unsigned integer '%u'.
   * @see http://us2.php.net/manual/en/function.sprintf.php
   */
  public function setTitlePattern($newPattern) {
    $this->titlePattern = $newPattern;
  }

  /**
   * Debugging tool for helping diagnose issues in the build
   */
  public function dump() {
    print $this->dom->saveXML();
    foreach($this->manifestGetItems() as $item) {
      print "id:         " . $item->getAttribute('id') . "\n";
      print "href: " . $item->getAttribute('href') . "\n";
      print "media-type: " . $item->getAttribute('media-type') . "\n";
      print "properties: " . $item->getAttribute('properties') . "\n";
      print "#######################################################\n";
      if (!empty($this->files[$item->getAttribute('href')]['contents'])) {
        print "type: " . $this->files[$item->getAttribute('href')]['type'] . "\n";
        print "contents: " . $this->files[$item->getAttribute('href')]['contents'] . "\n";
      }
      print "#######################################################\n\n";
    }
  }

  /**
   * Packages the epub and zips it up in the given location.
   *
   * This function currently needs a stub.zip file to help properly build the
   * epub file. This is because the standard ZipArchive library does not allow
   * specifying adding files without compression. Once this is resolved we can
   * manually add the file ourself. In the meantime we have a helper stub.zip
   * file.
   *
   * stub.zip was created by adding 'application/epub+zip' to file mimetype
   * and then via Zip 3.0 (July 5th 2008), by Info-ZIP on ubuntu
   *  zip -0 -X stub.zip mimetype
   *
   * @param string $zipname path where to build the zip, makesure this is writable.
   * @param string $method method to use 'PHP' (default) or 'OS'. If using OS
   *   You may need to call setZipExecutable('PATH/TO/ZIP'), default is /usr/bin/zip.
   * @see http://idpf.org/epub/30/spec/epub30-ocf.html#sec-zip-container-mime
   * @see https://bugs.php.net/bug.php?id=41243
   */
  public function bundle($zipname, $method = 'PHP') {
    switch ($method) {
      case 'OS':
        $this->bundleOS($zipname);
        break;
      case 'PHP':
        try {
          $this->bundlePHP($zipname);
        }
        catch (Exception $e) {
          throw $e;
        }
        break;
    }
  }

  /**
   * Set the Zip executable path name. For use with bundle method = 'OS'.
   *
   * @param  string $string The full path to the zip executable to use. Default
   *  '/usr/bin/zip'
   */
  public function setZipExecutable($string) {
    $this->zipExecutable = $string;
  }

  /**
   * Set the Zip executable arguments for adding all files. For use with bundle
   * method = 'OS'.
   *
   * @param string $string
   *   The zip executable arguments to use. Default '-UN=UTF8 -r !filename .'
   */
  public function setZipArgs($string) {
    $this->zipArgs = $string;
  }

  /**
   * Set the zip arguments for the non-compressed mimetype file.
   *
   * @param string  $string The zip executable arguments to use when adding the
   *   non-compressed mimetype epub entry. default: '-UN=UTF8 -0 !filename ./mimetype'
   */
  public function setZipArgsMimetype($string) {
    $this->zipArgsMimetype = $string;
  }

  /**
   * Sucess code to check for successful zip execution.
   * default is 0 which is standard Unix success code. Note that this uses a
   * strict !== comparison.
   *
   * @param mixed $success
   *   The success code to check zip execution for. Note that this will be
   *   compared to the $return parameter from exec().
   */
  public function setZipSuccess($success) {
    $this->zipSuccess = $success;
  }

  /**
   * Use the OS provided bundler. This is used may be used to avoid incompatibilities
   * with PHP ZipArchive and UTF-8 entries or to provide better compression
   * performance.
   *
   * @param string $zipname The zip filename to create.
   */
  private function bundleOS($zipname) {
    if (!is_file($this->zipExecutable)) {
      throw new \Exception('Zipexecutable ' . $this->zipExecutable . 'is not a file.');
    }
    if (!is_executable($this->zipExecutable)) {
      throw new \Exception('Zipexecutable ' . $this->zipExecutable . 'is not executable.');
    }

    // PHP doesn't have a tempdir func we use tempnam, which unfortunately
    // creates a file.
    $buildDir = tempnam(sys_get_temp_dir(), 'FMEPUB');
    if (file_exists($buildDir)) {
      unlink($buildDir);
    }
    if (!mkdir($buildDir)) {
      throw new \Exception('Unable to create zip build directory: "' . $buildDir . '"');
    }

    // mimetype
    $this->OSWriteFile($buildDir . '/mimetype', 'application/epub+zip');

    // container
    if (!mkdir($buildDir . '/META-INF')) {
      $this->cleanup_files(array($buildDir));
      throw new \Exception('Unable to create META-INF build directory in : "' . $buildDir . '"');
    }
    $container = $this->buildContainer();
    $this->OSWriteFile($buildDir . '/META-INF/container.xml', $container->saveXML());

    // content.opf
    if (!mkdir($buildDir . '/' . $this->contentDir)) {
      $this->cleanup_files(array($buildDir));
      throw new \Exception('Unable to create "' . $this->contentDir . '" directory in : "' . $buildDir . '"');
    }
    $this->OSWriteFile($buildDir . '/' . $this->contentDir . '/' . $this->filename, $this->dom->saveXML());

    // manifest items
    foreach($this->manifestGetItems() as $item) {
      $href = $item->getAttribute('href');
      if (!empty($this->files[$href]['type'])) {
        if ($this->files[$href]['type'] == 'file') {
          if (is_file($this->files[$href]['contents']) && is_readable($this->files[$href]['contents'])) {
            // Copy file
            $dest = $buildDir . '/' .  $this->contentDir . '/' . $href;
            $dirname = dirname($dest);
            if (!is_dir($dirname)) {
              if (!mkdir($dirname, 0755, TRUE)) {
                $this->cleanup_files(array($buildDir));
                throw new \Exception('Unable to create directory for ' . $dirname);
              }
            }
            if (copy($this->files[$href]['contents'], $dest) === FALSE) {
              $this->cleanup_files(array($buildDir));
              throw new \Exception('Unable to copy "' . $this->files[$href]['contents'] . '" to "' . $dest . '"');
            }
          }
          else {
            $this->cleanup_files(array($buildDir));
            throw new \Exception('Source file "' . $this->files[$href]['contents'] . '" does not exist or is not readable.');
          }
        }
        else {
          // Write new file.
          $dest = $buildDir . '/' . $this->contentDir . '/' . $href;
          $this->OSWriteFile($dest, $this->files[$href]['contents']);
        }
      }
    }

    // return the the cwd before leaving this function.
    $cwd = getcwd();
    if (!chdir($buildDir)) {
      $this->cleanup_files(array($buildDir));
      throw new \Exception('Unable to change dir to "' . $buildDir . '"');
    }

    // zip (mimetype receives no compression)
    $exec = $this->zipExecutable . ' ' . str_replace('!filename', $zipname, $this->zipArgs);
    exec($exec, $output, $return);
    if ($return !== $this->zipSuccess) {
      $this->cleanup_files(array($buildDir));
      chdir($cwd);
      throw new \Exception('Error adding to zipfile(' . $exec . '): ' . $output);
    }

    // now add the mimetype file.
    $exec = $this->zipExecutable . ' ' . str_replace('!filename', $zipname, $this->zipArgsMimetype);
    exec($exec, $output, $return);
    if ($return !== $this->zipSuccess) {
      $this->cleanup_files(array($buildDir));
      chdir($cwd);
      throw new \Exception('Error adding to zipfile(' . $exec . '): ' . $output);
    }
    chdir($cwd);
  }

  private function OSWriteFile($filename, $content) {
    $dirname = dirname($filename);
    if (!is_dir($dirname)) {
      if (!mkdir($dirname, 0755, TRUE)) {
        throw new \Exception('OS writefile unable to create directory for ' . $dirname);
      }
    }

    $fhandle = fopen($filename, 'w');
    if ($fhandle === FALSE) {
      throw new \Exception('Unable to open file "' . $filename . '" for writing');
    }

    if (fwrite($fhandle, $content) === FALSE) {
      throw new \Exception('Unable to write contents to file "' . $filename . '"');
    }

    if (fclose($fhandle) === FALSE) {
      throw new \Exception('Unable to close file "' . $filename . '"');
    }
  }

  private function bundlePHP($zipname) {
    if (copy(__DIR__ . '/' . 'stub.zip', $zipname)) {
      $zip = new \ZipArchive;
      $result = $zip->open($zipname);

      if ($result === TRUE) {
        /*
        Ideally we would add our mimetype file directly, but it needs to be uncompressed
        // https://bugs.php.net/bug.php?id=41243
        if (!$zip->addFromString('mimetype', 'application/epub+zip')) {
          throw new \Exception('Unable to add mimetype file to package.');
        }
        */

        // add container.xml
        $container = $this->buildContainer();
        if (!$zip->addFromString('META-INF/container.xml', $container->saveXML())) {
          throw new \Exception('Unable to add container.xml');
        }

        // add content.opf
        if (!$zip->addFromString($this->contentDir . '/' . $this->filename, $this->dom->saveXML())) {
          throw new \Exception('Unable to add content.opf');
        }

        // add manifest items
        foreach($this->manifestGetItems() as $item) {
          $href = $item->getAttribute('href');
          if (!empty($this->files[$href]['type'])) {
            if ($this->files[$href]['type'] == 'file') {
              if (is_file($this->files[$href]['contents']) && is_readable($this->files[$href]['contents'])) {
                if (!$zip->addFile($this->files[$href]['contents'], $this->contentDir . '/' . $href)) {
                  throw new \Exception('Unable to add ' . $href . ' as direct file' . $contents);
                }
              }
              else {
                throw new \Exception('Target file "' . $this->files[$href]['contents'] . '" does not exist or is not readable.');
              }
            }
            else {
              if (!$zip->addFromString($this->contentDir . '/' . $href, $this->files[$href]['contents'])) {
                throw new \Exception('Unable to add ' . $href . ' as inline');
              }
            }
          }
        }

        if (!$zip->close()) {
          throw new \Exception('Unable to properly close zip archive');
        }
      }
      else {
        throw new \Exception('Unable to open zip code: ' . $result);
      }
    }
    else {
      throw new \Exception(sprintf('Unable to copy stub.zip file to %s', $zipname));
    }
  }

  /**
   * Helper function to cleanup temporary working directories.
   *
   * Note that since this does recursive deletion of the passed paths, this has a
   * safety check to ensure the path resides in the temp directory.
   *
   * @param array $paths
   *   An array of paths and/or files to delete recursively.
   */
  private function cleanup_files($paths) {
    // Remove our extract directory
    $tempdir = sys_get_temp_dir();

    foreach ($paths as $cleanup) {
      // Safety check to ensure that the path is in temp dir.
      if (strpos($cleanup, $tempdir) === 0) {
        if (is_file($cleanup)) {
          unlink($cleanup);
        }
        else {
          // directory, use recursive delete.
          foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cleanup, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isFile() ? unlink($path) : rmdir($path);
          }
        }

        // top-level directories will need to be cleared out after contents.
        if (is_dir($cleanup)) {
          rmdir($cleanup);
        }
      }
    }
  }


  private function buildContainer() {
    $container = new \DOMDocument('1.0', 'utf-8');
    $root = $container->createElementNS('urn:oasis:names:tc:opendocument:xmlns:container', 'container');
    $root->setAttribute('version', '1.0');
    $container->appendChild($root);
    $rootfiles = $container->createElement('rootfiles');
    $rootfile = $container->createElement('rootfile');
    $rootfile->setAttribute('full-path', $this->contentDir . '/' . $this->filename);
    $rootfile->setAttribute('media-type', 'application/oebps-package+xml');
    $rootfiles->appendChild($rootfile);
    $root->appendChild($rootfiles);
    return $container;
  }
}
